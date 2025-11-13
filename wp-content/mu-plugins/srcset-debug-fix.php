<?php
// mu-plugins/srcset-debug-fix.php (or your child theme functions.php)

// Helper: true only for the single post’s own featured image in the main query
function _is_main_post_featured_image($attachment) {
    if ( !is_singular('post') ) return false;
    $q_post_id = get_queried_object_id();                // stable ID of the page we’re on
    if ( !$q_post_id ) return false;
    if ( !is_main_query() ) return false;                // avoid Elementor/secondary loops
    return $attachment->ID === get_post_thumbnail_id($q_post_id);
}

// EARLY: log attributes
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
    if ( _is_main_post_featured_image($attachment) ) {
        error_log('IMG ATTR @prio1: ' . print_r($attr, true));
    }
    return $attr;
}, 1, 3);

// LATE: log + patch only the true LCP image
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
    if ( _is_main_post_featured_image($attachment) ) {
        error_log('IMG ATTR @prio9999 (before patch): ' . print_r($attr, true));
        if ( empty($attr['srcset']) ) {
            $attr['srcset'] = wp_get_attachment_image_srcset($attachment->ID, $size) ?: '';
            $attr['sizes']  = $attr['sizes'] ?? '(min-width:1440px) 1200px, (min-width:1024px) 960px, (min-width:700px) 720px, 92vw';
            error_log('PATCHED: re-added srcset/sizes for featured image.');
        }
        if ( function_exists('wp_debug_backtrace_summary') ) {
            error_log('BACKTRACE: ' . wp_debug_backtrace_summary(null, 3));
        }
    }
    return $attr;
}, 9999, 3);
