<?php
/**
 * Plugin Name: MU — Defer Small Theme JS
 * Description: Adds "defer" to selected small theme scripts on the frontend (not admin/AJAX).
 */

if (!defined('ABSPATH')) exit;

function mu_defer_is_frontend(): bool {
  if (is_admin()) return false;
  if (defined('REST_REQUEST') && REST_REQUEST) return false;
  if (defined('DOING_CRON') && DOING_CRON) return false;
  if (function_exists('wp_doing_ajax') && wp_doing_ajax()) return false;
  return true;
}

add_filter('script_loader_tag', function ($tag, $handle, $src) {
  if (!mu_defer_is_frontend()) return $tag;

  // Only touch real external scripts (skip inline)
  if (stripos($tag, ' src=') === false) return $tag;

  // Skip if already async/defer/module
  if (preg_match('~\s(defer|async)\b|type=["\']module["\']~i', $tag)) return $tag;

  // Theme handles to defer (adjust if your handles differ)
  static $defer_handles = [
    'comment',               // comment.js
    'main',                  // main.js
    'lazy',                  // lazy.js
    'pagination',            // pagination.js
    'download',              // download-form-validation.js
    'tw-facade',             // tw-facade.js
    // Add/remove handles as needed
  ];

  if (!in_array($handle, $defer_handles, true)) return $tag;

  // Add the defer attribute
  return str_replace('<script ', '<script defer ', $tag);
}, 20, 3);
