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
	// Support both 'postId' (current) and 'id' (legacy) parameter names.
	$post_id = (int) ( $request->get_param( 'postId' ) ?? $request->get_param( 'id' ) ?? 0 );
	$format  = (string) ( $request->get_param( 'format' ) ?? 'pdf' );

	// Collect remaining params as $args (excluding postId/id/format for clarity).
	$params = $request->get_params();
	unset( $params[ 'postId' ], $params[ 'id' ], $params[ 'format' ] );

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

		$parsed  = parse_request( $request );
		$post_id = $parsed[ 'post_id' ];
		$format  = $parsed[ 'format' ];
		$args    = $parsed[ 'args' ];

		// Lifecycle: request observed.
		do_action( 'read_offline_export_requested', $post_id, $format, $args, $request );

		// Respect the rest_public setting from the plugin configuration.
		$options         = get_option( 'read_offline_settings_general', array() );
		$rest_public     = ! empty( $options[ 'rest_public' ] );
		$current_allowed = $rest_public ? true : current_user_can( 'edit_others_posts' );

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
			set_transient( $lock_key, 1, 30 );

			// Note: Lock is cleared in rest_post_dispatch when export completes (success/failure).
			// 30-second TTL serves as safety net if something crashes.
		}

		return $result; // Allow Read Offline's internal controller to handle the export.
	},
	10,
	3
);

/**
add_filter(
	'rest_post_dispatch',
	function ( $response, $server, $request ) {
		if ( ! is_read_offline_export_route( $request ) ) {
			return $response;
		}

		// Debug at priority 10 - very early.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Read Offline] rest_post_dispatch PRIORITY 10 - Response: ' . get_class( $response ) );
			if ( method_exists( $response, 'get_data' ) ) {
				error_log( '[Read Offline] rest_post_dispatch PRIORITY 10 - Data: ' . print_r( $response->get_data(), true ) );
			}
		}

		return $response;
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
			// Clear lock on failure so user can retry immediately
			$lock_key = apply_filters(
				'read_offline_export_lock_key',
				"read_offline:lock:{$parsed[ 'format' ]}:{$parsed[ 'post_id' ]}",
				$parsed[ 'post_id' ],
				$parsed[ 'format' ],
				$parsed[ 'args' ]
			);
			if ( $lock_key && ! is_wp_error( $lock_key ) ) {
				delete_transient( $lock_key );
			}
			do_action( 'read_offline_export_failed', $parsed[ 'post_id' ], $parsed[ 'format' ], $parsed[ 'args' ], $response );
			return $response;
		}

		$code = method_exists( $response, 'get_status' ) ? (int) $response->get_status() : 200;
		if ( $code >= 400 ) {
			$parsed = parse_request( $request );
			// Clear lock on failure so user can retry immediately
			$lock_key = apply_filters(
				'read_offline_export_lock_key',
				"read_offline:lock:{$parsed[ 'format' ]}:{$parsed[ 'post_id' ]}",
				$parsed[ 'post_id' ],
				$parsed[ 'format' ],
				$parsed[ 'args' ]
			);
			if ( $lock_key && ! is_wp_error( $lock_key ) ) {
				delete_transient( $lock_key );
			}
			do_action( 'read_offline_export_failed', $parsed[ 'post_id' ], $parsed[ 'format' ], $parsed[ 'args' ], $response );
			return $response;
		}

		// Normalize and allow shaping of success payloads.
		$parsed = parse_request( $request );
		$data   = method_exists( $response, 'get_data' ) ? $response->get_data() : null;
		if ( is_array( $data ) ) {
			// Extract known export fields (url, file, path, size, filename).
			$export_keys = array( 'url', 'file', 'path', 'size', 'filename' );
			foreach ( $export_keys as $key ) {
				if ( isset( $data[ $key ] ) ) {
					$export_data[ $key ] = $data[ $key ];
				}
			}
		}

		$payload = array(
			'status'  => 'ok',
			'format'  => $parsed[ 'format' ],
			'post_id' => $parsed[ 'post_id' ],
			'export'  => ! empty( $export_data ) ? $export_data : $data,
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
		// Clear lock on success - PDF is ready
		$lock_key = apply_filters(
			'read_offline_export_lock_key',
			"read_offline:lock:{$parsed[ 'format' ]}:{$parsed[ 'post_id' ]}",
			$parsed[ 'post_id' ],
			$parsed[ 'format' ],
			$parsed[ 'args' ]
		);
		if ( $lock_key && ! is_wp_error( $lock_key ) ) {
			delete_transient( $lock_key );
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
