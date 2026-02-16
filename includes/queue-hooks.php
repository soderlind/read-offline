<?php
/**
 * Queue-aware wrapper hooks for Read Offline's public REST export route.
 *
 * This layer is intentionally decoupled from internal classes so it can live
 * outside the exporter/controller and remain 100% backwards compatible.
 *
 * What it provides:
 * - Short-circuit to a background queue via `read_offline_pre_export`
 * - Capability gate via `read_offline_can_export` (default: Editor+)
 * - Lifecycle signals: requested/completed/failed
 * - Dedupe lock + cache key filters
 * - Response shaping via `read_offline_rest_response`
 *
 * @package ReadOffline
 */

declare(strict_types=1);

namespace Read_Offline\Queue_Hooks;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route matcher for read-offline's export endpoint.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return bool
 */
function is_read_offline_export_route( $request ): bool {
	if ( ! $request instanceof WP_REST_Request ) {
		return false;
	}
	// Documented route: GET /wp-json/read-offline/v1/export.
	$route = $request->get_route();
	return ( '/read-offline/v1/export' === $route );
}

/**
 * Parse commonly used params from the request.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return array{post_id:int,format:string,args:array}
 */
function parse_request( WP_REST_Request $request ): array {
	$post_id = (int) $request->get_param( 'id' );
	$format  = (string) ( $request->get_param( 'format' ) ?? 'pdf' );

	// Collect remaining params as $args (excluding id/format for clarity).
	$params = $request->get_params();
	unset( $params[ 'id' ], $params[ 'format' ] );

	return array(
		'post_id' => $post_id,
		'format'  => $format,
		'args'    => is_array( $params ) ? $params : array(),
	);
}

/**
 * Intercept dispatch to support queue short-circuiting, capability checks, and dedupe.
 *
 * Hook: rest_pre_dispatch
 *
 * @param mixed           $result
 * @param \WP_REST_Server $server
 * @param WP_REST_Request $request
 * @return mixed
 */
add_filter(
	'rest_pre_dispatch',
	function ( $result, $server, $request ) {
		if ( ! is_read_offline_export_route( $request ) ) {
			return $result;
		}

		$parsed          = parse_request( $request );
		$post_id         = $parsed[ 'post_id' ];
		$format          = $parsed[ 'format' ];
		$args            = $parsed[ 'args' ];
		$current_allowed = current_user_can( 'edit_others_posts' ); // Editors+ on posts by default.
	
		// Lifecycle: request observed.
		do_action( 'read_offline_export_requested', $post_id, $format, $args, $request );

		// Allow integrators to take over and enqueue to a queue, returning a 202 etc.
		$short = apply_filters(
			'read_offline_pre_export', // return WP_REST_Response|WP_Error to short-circuit, or null to continue.
			null,
			$post_id,
			$format,
			$args,
			$request
		);
		if ( null !== $short ) {
			return $short;
		}

		// Capability filter (overrideable per site).
		$allowed = (bool) apply_filters(
			'read_offline_can_export',
			$current_allowed,
			$post_id,
			$format,
			$args,
			$request
		);
		if ( ! $allowed ) {
			return new WP_Error(
				'read_offline_forbidden',
				__( 'Not allowed to export.', 'read-offline' ),
				array( 'status' => 403 )
			);
		}

		// Optional dedupe lock (5 min) to keep callers honest if they spam the route.
		$lock_key = apply_filters(
			'read_offline_export_lock_key',
			"read_offline:lock:$format:$post_id",
			$post_id,
			$format,
			$args
		);
		if ( $lock_key && ! is_wp_error( $lock_key ) ) {
			if ( get_transient( $lock_key ) ) {
				return new WP_Error(
					'read_offline_busy',
					__( 'An export is already running for this post/format.', 'read-offline' ),
					array( 'status' => 409 )
				);
			}
			set_transient( $lock_key, 1, MINUTE_IN_SECONDS * 5 );

			// Clean up after the request finishes (success or error).
			add_action(
				'rest_request_after_callbacks',
				function () use ($lock_key) {
				delete_transient( $lock_key );
			},
				9999
			);
		}

		return $result; // Allow Read Offline’s internal controller to handle the export.
	},
	10,
	3
);

/**
 * Shape the success response and emit completion signal.
 *
 * Hook: rest_post_dispatch
 *
 * @param WP_REST_Response|\WP_HTTP_Response|\WP_Error $response
 * @param \WP_REST_Server                              $server
 * @param WP_REST_Request                              $request
 * @return mixed
 */
add_filter(
	'rest_post_dispatch',
	function ( $response, $server, $request ) {
		if ( ! is_read_offline_export_route( $request ) ) {
			return $response;
		}

		// Don't interfere with short-circuited 202 responses etc.
		if ( $response instanceof WP_Error ) {
			$parsed = parse_request( $request );
			do_action( 'read_offline_export_failed', $parsed[ 'post_id' ], $parsed[ 'format' ], $parsed[ 'args' ], $response );
			return $response;
		}

		$code = method_exists( $response, 'get_status' ) ? (int) $response->get_status() : 200;
		if ( $code >= 400 ) {
			$parsed = parse_request( $request );
			do_action( 'read_offline_export_failed', $parsed[ 'post_id' ], $parsed[ 'format' ], $parsed[ 'args' ], $response );
			return $response;
		}

		// Normalize and allow shaping of success payloads.
		$parsed  = parse_request( $request );
		$data    = method_exists( $response, 'get_data' ) ? $response->get_data() : null;
		$payload = array(
			'status'  => 'ok',
			'format'  => $parsed[ 'format' ],
			'post_id' => $parsed[ 'post_id' ],
			'export'  => is_array( $data ) ? array_intersect_key( $data, array_flip( array( 'file', 'path', 'url', 'size', 'filename' ) ) ) : $data,
		);

		$payload = apply_filters(
			'read_offline_rest_response',
			$payload,
			$parsed[ 'post_id' ],
			$parsed[ 'format' ],
			$parsed[ 'args' ],
			$request
		);

		// Update response and emit completion signal.
		if ( $response instanceof WP_REST_Response ) {
			$response->set_data( $payload );
		} else {
			$response = new WP_REST_Response( $payload, $code );
		}

		do_action( 'read_offline_export_completed', $parsed[ 'post_id' ], $parsed[ 'format' ], $parsed[ 'args' ], $payload[ 'export' ] ?? null );

		return $response;
	},
	50,
	3
);

/**
 * Optional: expose cache key and TTL helpers for exporters that cache artifacts.
 * Export implementations may consult these (no-op by default).
 */
if ( ! has_filter( 'read_offline_cache_key' ) ) {
	add_filter(
		'read_offline_cache_key',
		function ( $key, $post_id, $format, $args ) {
			return "read_offline:$format:$post_id:" . md5( wp_json_encode( $args ) );
		},
		10,
		4
	);
}
if ( ! has_filter( 'read_offline_cache_ttl' ) ) {
	add_filter(
		'read_offline_cache_ttl',
		function ( $ttl, $post_id, $format, $args ) {
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			return 12 * HOUR_IN_SECONDS;
		},
		10,
		4
	);
}
