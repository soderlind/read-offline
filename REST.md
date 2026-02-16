# Queue-Aware REST Hooks

Read Offline 2.2.7 introduces a comprehensive hook system for the REST API export endpoint (`/wp-json/read-offline/v1/export`). These hooks enable background processing, custom capability checks, lifecycle monitoring, and response customization.

## Overview

The queue-aware hooks layer sits between WordPress REST API dispatch and Read Offline's internal export controller. It provides:

- **Background Processing**: Short-circuit synchronous exports to enqueue jobs
- **Capability Control**: Override default permission checks (Editor+)
- **Lifecycle Signals**: Monitor export requests, completions, and failures
- **Deduplication**: Prevent concurrent exports of the same post/format
- **Response Shaping**: Customize API response payloads
- **Cache Helpers**: Filters for cache key and TTL customization

All hooks are **100% backwards compatible** and optional—Read Offline works normally without any integration.

---

## Available Hooks

### Filters

| Hook | Purpose | Default Behavior |
|------|---------|-----------------|
| `read_offline_pre_export` | Short-circuit export to background queue | `null` (continue synchronous) |
| `read_offline_can_export` | Gate export permission | Editor+ (`edit_others_posts`) |
| `read_offline_export_lock_key` | Customize dedupe lock key | `read_offline:lock:{format}:{post_id}` |
| `read_offline_rest_response` | Shape success response payload | Standard format |
| `read_offline_cache_key` | Cache key for export artifacts | `read_offline:{format}:{post_id}:{args_hash}` |
| `read_offline_cache_ttl` | Cache lifetime in seconds | 12 hours |

### Actions

| Hook | When Fired | Parameters |
|------|-----------|------------|
| `read_offline_export_requested` | Export endpoint called | `$post_id, $format, $args, $request` |
| `read_offline_export_completed` | Export succeeded (200 response) | `$post_id, $format, $args, $export_data` |
| `read_offline_export_failed` | Export failed (error/4xx/5xx) | `$post_id, $format, $args, $response` |

---

## Examples

### 1. Short-Circuit to Background Queue

Replace synchronous export with a queued job returning HTTP 202 (Accepted).

```php
add_filter( 'read_offline_pre_export', function( $result, $post_id, $format, $args, $request ) {
    // Enqueue to your background job system (Action Scheduler, WP Cron, etc.)
    as_enqueue_async_action( 'my_read_offline_export_job', array(
        'post_id' => $post_id,
        'format'  => $format,
        'args'    => $args,
    ), 'read-offline-exports' );

    // Return 202 Accepted with job reference
    return new WP_REST_Response( array(
        'status'  => 'queued',
        'message' => 'Export queued for background processing',
        'post_id' => $post_id,
        'format'  => $format,
        'job_id'  => as_get_scheduled_actions( array(
            'hook'   => 'my_read_offline_export_job',
            'status' => 'pending',
            'args'   => array( 'post_id' => $post_id, 'format' => $format ),
        ), 'ids' )[0] ?? null,
    ), 202 );
}, 10, 5 );

// Handle the background job
add_action( 'my_read_offline_export_job', function( $job_args ) {
    // Process export in background
    $exporter = Read_Offline_Export::get_instance();
    $result = $exporter->export_single( $job_args['post_id'], $job_args['format'], $job_args['args'] );
    
    // Store result, send notification, etc.
    update_post_meta( $job_args['post_id'], '_read_offline_export_url', $result['url'] );
    
    // Email user when ready
    wp_mail(
        get_the_author_meta( 'user_email', get_post_field( 'post_author', $job_args['post_id'] ) ),
        'Export Ready',
        sprintf( 'Your %s export is ready: %s', $job_args['format'], $result['url'] )
    );
}, 10, 1 );
```

---

### 2. Custom Capability Check

Restrict exports to administrators only or check custom capabilities.

```php
// Admin-only exports
add_filter( 'read_offline_can_export', function( $allowed, $post_id, $format, $args, $request ) {
    return current_user_can( 'manage_options' );
}, 10, 5 );

// Author-only exports (users can only export their own posts)
add_filter( 'read_offline_can_export', function( $allowed, $post_id, $format, $args, $request ) {
    $post = get_post( $post_id );
    return current_user_can( 'edit_post', $post_id ) && ( get_current_user_id() === (int) $post->post_author );
}, 10, 5 );

// Custom capability per post type
add_filter( 'read_offline_can_export', function( $allowed, $post_id, $format, $args, $request ) {
    $post_type = get_post_type( $post_id );
    
    return match( $post_type ) {
        'page'    => current_user_can( 'export_pages' ),
        'book'    => current_user_can( 'export_books' ),
        'article' => current_user_can( 'export_articles' ),
        default   => current_user_can( 'edit_others_posts' ),
    };
}, 10, 5 );
```

---

### 3. Lifecycle Monitoring

Track export usage, log analytics, or integrate with external systems.

```php
// Log all export requests
add_action( 'read_offline_export_requested', function( $post_id, $format, $args, $request ) {
    error_log( sprintf(
        '[Read Offline] Export requested: Post #%d, Format: %s, User: %d, IP: %s',
        $post_id,
        $format,
        get_current_user_id(),
        sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' )
    ) );
}, 10, 4 );

// Track successful exports in analytics
add_action( 'read_offline_export_completed', function( $post_id, $format, $args, $export_data ) {
    // Send to analytics service
    if ( function_exists( 'amplitude_track' ) ) {
        amplitude_track( 'export_completed', array(
            'post_id'  => $post_id,
            'format'   => $format,
            'filesize' => $export_data['size'] ?? 0,
            'duration' => timer_stop( 0, 3 ),
        ) );
    }
    
    // Update post meta for reporting
    $count = (int) get_post_meta( $post_id, '_export_count', true );
    update_post_meta( $post_id, '_export_count', $count + 1 );
    update_post_meta( $post_id, '_last_export_format', $format );
    update_post_meta( $post_id, '_last_export_date', current_time( 'mysql' ) );
}, 10, 4 );

// Alert on export failures
add_action( 'read_offline_export_failed', function( $post_id, $format, $args, $response ) {
    $error_message = is_wp_error( $response ) 
        ? $response->get_error_message() 
        : 'Unknown error';
    
    // Send to error tracking service
    if ( function_exists( 'sentry_capture_message' ) ) {
        sentry_capture_message( "Export failed: Post #$post_id, Format: $format", array(
            'level' => 'error',
            'extra' => array(
                'post_id' => $post_id,
                'format'  => $format,
                'error'   => $error_message,
            ),
        ) );
    }
    
    // Email admin on failures
    wp_mail(
        get_option( 'admin_email' ),
        'Read Offline Export Failed',
        sprintf( "Export failed for post #%d (format: %s)\nError: %s", $post_id, $format, $error_message )
    );
}, 10, 4 );
```

---

### 4. Response Customization

Add custom fields or modify response structure.

```php
// Add download tracking URL
add_filter( 'read_offline_rest_response', function( $payload, $post_id, $format, $args, $request ) {
    if ( isset( $payload['export']['url'] ) ) {
        // Replace direct URL with tracking URL
        $payload['export']['download_url'] = add_query_arg( array(
            'track'   => 'true',
            'post_id' => $post_id,
            'format'  => $format,
        ), $payload['export']['url'] );
        
        // Add expiration timestamp (24 hours)
        $payload['export']['expires_at'] = gmdate( 'c', time() + DAY_IN_SECONDS );
    }
    
    return $payload;
}, 10, 5 );

// Add post metadata to response
add_filter( 'read_offline_rest_response', function( $payload, $post_id, $format, $args, $request ) {
    $post = get_post( $post_id );
    
    $payload['post'] = array(
        'id'        => $post_id,
        'title'     => get_the_title( $post_id ),
        'author'    => get_the_author_meta( 'display_name', $post->post_author ),
        'date'      => get_the_date( 'c', $post_id ),
        'permalink' => get_permalink( $post_id ),
    );
    
    return $payload;
}, 10, 5 );
```

---

### 5. Custom Deduplication Strategy

Modify how concurrent requests are prevented.

```php
// User-specific lock (allow concurrent exports from different users)
add_filter( 'read_offline_export_lock_key', function( $key, $post_id, $format, $args ) {
    $user_id = get_current_user_id();
    return "read_offline:lock:$format:$post_id:user_$user_id";
}, 10, 4 );

// Disable deduplication entirely
add_filter( 'read_offline_export_lock_key', '__return_false' );

// Arguments-aware lock (different args = different exports)
add_filter( 'read_offline_export_lock_key', function( $key, $post_id, $format, $args ) {
    $args_hash = md5( wp_json_encode( $args ) );
    return "read_offline:lock:$format:$post_id:$args_hash";
}, 10, 4 );
```

---

### 6. Cache Key Customization

Control how export artifacts are cached.

```php
// Include user role in cache key (different cache per role)
add_filter( 'read_offline_cache_key', function( $key, $post_id, $format, $args ) {
    $user = wp_get_current_user();
    $role = ! empty( $user->roles ) ? $user->roles[0] : 'guest';
    
    return sprintf(
        'read_offline:%s:%d:%s:%s',
        $format,
        $post_id,
        $role,
        md5( wp_json_encode( $args ) )
    );
}, 10, 4 );

// Shorter TTL for draft posts
add_filter( 'read_offline_cache_ttl', function( $ttl, $post_id, $format, $args ) {
    $status = get_post_status( $post_id );
    
    return match( $status ) {
        'draft'   => 1 * HOUR_IN_SECONDS,
        'pending' => 2 * HOUR_IN_SECONDS,
        'publish' => 24 * HOUR_IN_SECONDS,
        default   => 12 * HOUR_IN_SECONDS,
    };
}, 10, 4 );
```

---

## Complete Example: Premium Export System

Combine multiple hooks for a comprehensive premium export feature.

```php
/**
 * Premium Export System
 * - Free users: queued exports (background)
 * - Premium users: instant exports
 * - Track usage quota
 * - Custom response with metadata
 */

// Check if user is premium
function is_premium_user( $user_id = null ) {
    $user_id = $user_id ?: get_current_user_id();
    return get_user_meta( $user_id, 'premium_member', true );
}

// Background processing for free users
add_filter( 'read_offline_pre_export', function( $result, $post_id, $format, $args, $request ) {
    // Premium users get instant exports
    if ( is_premium_user() ) {
        return null; // Continue with synchronous export
    }
    
    // Free users: queue and return 202
    $job_id = as_enqueue_async_action( 'premium_export_job', array(
        'post_id' => $post_id,
        'format'  => $format,
        'args'    => $args,
        'user_id' => get_current_user_id(),
    ), 'premium-exports' );
    
    return new WP_REST_Response( array(
        'status'  => 'queued',
        'message' => 'Export queued. You will be notified when ready.',
        'post_id' => $post_id,
        'format'  => $format,
        'job_id'  => $job_id,
        'upgrade_url' => home_url( '/premium' ),
    ), 202 );
}, 10, 5 );

// Track quotas on request
add_action( 'read_offline_export_requested', function( $post_id, $format, $args, $request ) {
    $user_id = get_current_user_id();
    
    // Increment usage counter
    $count = (int) get_user_meta( $user_id, 'export_count_this_month', true );
    update_user_meta( $user_id, 'export_count_this_month', $count + 1 );
    
    // Log to analytics
    do_action( 'premium_analytics_track', 'export_requested', array(
        'user_id'  => $user_id,
        'post_id'  => $post_id,
        'format'   => $format,
        'plan'     => is_premium_user( $user_id ) ? 'premium' : 'free',
    ) );
}, 10, 4 );

// Enhance response with premium features
add_filter( 'read_offline_rest_response', function( $payload, $post_id, $format, $args, $request ) {
    $user_id = get_current_user_id();
    
    // Add usage stats
    $payload['usage'] = array(
        'exports_this_month' => (int) get_user_meta( $user_id, 'export_count_this_month', true ),
        'plan'               => is_premium_user( $user_id ) ? 'premium' : 'free',
        'quota_limit'        => is_premium_user( $user_id ) ? 'unlimited' : 10,
    );
    
    // Premium users get extra metadata
    if ( is_premium_user( $user_id ) ) {
        $payload['premium'] = array(
            'processing_time' => timer_stop( 0, 3 ),
            'direct_download' => true,
            'priority'        => 'instant',
        );
    }
    
    return $payload;
}, 10, 5 );

// Capability check with quota enforcement
add_filter( 'read_offline_can_export', function( $allowed, $post_id, $format, $args, $request ) {
    if ( ! $allowed ) {
        return false; // Respect base permission check
    }
    
    $user_id = get_current_user_id();
    
    // Premium users: unlimited
    if ( is_premium_user( $user_id ) ) {
        return true;
    }
    
    // Free users: check quota
    $count = (int) get_user_meta( $user_id, 'export_count_this_month', true );
    $limit = 10;
    
    if ( $count >= $limit ) {
        return new WP_Error(
            'export_quota_exceeded',
            sprintf( 'You have reached your monthly limit of %d exports. Please upgrade to premium.', $limit ),
            array( 'status' => 429 )
        );
    }
    
    return true;
}, 10, 5 );
```

---

## REST Endpoint Reference

### Endpoint
```
GET /wp-json/read-offline/v1/export
```

### Parameters
- `id` (required): Post ID to export
- `format` (optional): Export format (`pdf`, `epub`, `md`). Default: `pdf`
- Additional format-specific args (margins, toc, etc.)

### Standard Response (200 OK)
```json
{
  "status": "ok",
  "format": "pdf",
  "post_id": 123,
  "export": {
    "file": "my-post.pdf",
    "url": "https://example.com/wp-content/uploads/read-offline/my-post.pdf",
    "path": "/var/www/html/wp-content/uploads/read-offline/my-post.pdf",
    "size": 245678,
    "filename": "my-post.pdf"
  }
}
```

### Error Responses
- `403 Forbidden`: Capability check failed
- `409 Conflict`: Duplicate export already in progress
- `429 Too Many Requests`: Rate limit or quota exceeded (custom)
- `500 Internal Server Error`: Export generation failed

---

## Best Practices

1. **Return Early**: In `read_offline_pre_export`, return `null` to continue or a `WP_REST_Response`/`WP_Error` to short-circuit
2. **Preserve Default Behavior**: Most filters pass a default value—modify only when needed
3. **Check User Context**: Many use cases vary by user role/capability
4. **Handle Errors**: Wrap integrations in try/catch and return meaningful errors
5. **Test Lifecycle**: Verify all three lifecycle actions fire correctly
6. **Respect Lock Keys**: If disabling deduplication, ensure your system handles concurrent requests
7. **Cache Wisely**: Adjust TTL based on post status and update frequency

---

## Debugging

Enable WordPress debug logging to see hook execution:

```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// Log hook calls
add_action( 'read_offline_export_requested', function( $post_id, $format ) {
    error_log( "Export requested: Post #$post_id, Format: $format" );
}, 10, 2 );

add_action( 'read_offline_export_completed', function( $post_id, $format ) {
    error_log( "Export completed: Post #$post_id, Format: $format" );
}, 10, 2 );

add_action( 'read_offline_export_failed', function( $post_id, $format, $args, $response ) {
    error_log( "Export failed: Post #$post_id, Format: $format, Error: " . 
        ( is_wp_error( $response ) ? $response->get_error_message() : 'Unknown' ) );
}, 10, 4 );
```

---

## Further Reading

- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [Action Scheduler Documentation](https://actionscheduler.org/)
- [WordPress Plugin Handbook: Hooks](https://developer.wordpress.org/plugins/hooks/)

---

## Support

For questions or issues with queue-aware hooks:
- [GitHub Issues](https://github.com/soderlind/read-offline/issues)
- [Plugin Support Forum](https://wordpress.org/support/plugin/read-offline/)
