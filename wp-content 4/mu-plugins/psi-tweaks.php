<?php
/**
 * PSI Tweaks (front-end only)
 * Location: wp-content/mu-plugins/psi-tweaks.php
 *
 * Goals:
 * - Prevent CLS from images, logos, embeds, galleries, meta rows.
 * - Restore/normalize srcset/sizes for images.
 * - Make only the LCP image eager/high; others lazy/low.
 * - Provide a lightweight Twitter/X facade and reserve space for other embeds.
 */

defined('ABSPATH') || exit;

/* -----------------------------------------------------------
 * 0) Guard: only run on front-end (not admin, feeds, or REST)
 * ----------------------------------------------------------- */
function psi_is_front() {
    if ( is_admin() ) { return false; }
    if ( is_feed() )  { return false; }
    if ( function_exists('wp_is_json_request') && wp_is_json_request() ) { return false; }
    return true;
}

/* ----------------------------------------------------------------
 * 1) Global attachment <img> attribute fixer (srcset/sizes + hints)
 * ---------------------------------------------------------------- */
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
    if ( ! psi_is_front() ) return $attr;
    if ( ! $attachment instanceof WP_Post ) return $attr;

    $is_single_post = is_singular('post') && is_main_query();
    $is_lcp = $is_single_post && ( $attachment->ID === get_post_thumbnail_id() );

    // Re-add srcset/sizes if missing.
    if ( empty($attr['srcset']) ) {
        $srcset = wp_get_attachment_image_srcset($attachment->ID, $size);
        if ($srcset) {
            $attr['srcset'] = $srcset;

            if ( empty($attr['sizes']) ) {
                $target_width = 0;

                if ( is_array($size) && !empty($size[0]) ) {
                    $target_width = (int) $size[0];
                } elseif ( is_string($size) ) {
                    if ( function_exists('wp_get_registered_image_subsizes') ) {
                        $subs = wp_get_registered_image_subsizes();
                        if ( isset($subs[$size]['width']) ) {
                            $target_width = (int) $subs[$size]['width'];
                        } elseif ( $size === 'full' ) {
                            $meta = wp_get_attachment_metadata($attachment->ID);
                            if ( !empty($meta['width']) ) {
                                $target_width = (int) $meta['width'];
                            }
                        }
                    }
                }

                $attr['sizes'] = ($target_width > 0)
                    ? "(max-width: {$target_width}px) 100vw, {$target_width}px"
                    : '100vw';
            }
        }
    }

    // LCP vs non-LCP tuning
    if ( $is_lcp ) {
        $attr['loading']       = 'eager';
        $attr['fetchpriority'] = 'high';
        $attr['decoding']      = 'auto';
    } else {
        if ( empty($attr['loading']) ) $attr['loading'] = 'lazy';
        if ( strtolower((string)$attr['loading']) === 'lazy' ) $attr['fetchpriority'] = 'low';
        if ( empty($attr['decoding']) ) $attr['decoding'] = 'async';
    }

    return $attr;
}, 9999, 3);

/* -----------------------------------------------------------------------
 * 2) Only the very first image can be non-lazy on single posts (LCP guard)
 * ----------------------------------------------------------------------- */
add_filter('wp_omit_loading_attr_threshold', function ($threshold) {
    if ( ! psi_is_front() ) return $threshold;
    if ( is_singular('post') ) return 1; // only the first image (typically featured) can be non-lazy
    return $threshold;
}, 10);

/* -------------------------------------------------------
 * 3) Restore core content image processor if theme removed
 * ------------------------------------------------------- */
add_action('init', function () {
    if ( ! psi_is_front() ) return;
    if ( ! has_filter('the_content', 'wp_filter_content_tags') ) {
        add_filter('the_content', 'wp_filter_content_tags', 10);
    }
});

/* ------------------------------------------------------------------------
 * 4) Fix images inside post content if a plugin stripped srcset/sizes/hints
 * ------------------------------------------------------------------------ */
add_filter('wp_content_img_tag', function ($html, $context, $attachment_id) {
    if ( ! psi_is_front() ) return $html;
    if ( ! is_singular('post') || $context !== 'the_content' ) return $html;

    // Skip if already has srcset.
    if ( strpos($html, ' srcset=') !== false ) return $html;

    // Try to obtain attachment ID.
    $att_id = (int) $attachment_id;
    if ( ! $att_id && preg_match('/class="[^"]*wp-image-(\d+)/', $html, $m) ) {
        $att_id = (int) $m[1];
    }
    if ( ! $att_id ) return $html; // external/unknown image

    // Requested size from class="size-xxx" or fallback to 'large'.
    $size = 'large';
    if ( preg_match('/class="[^"]*size-([a-zA-Z0-9_\-]+)/', $html, $m) ) {
        $size = sanitize_key($m[1]);
    }

    // Build srcset with graceful fallback chain.
    $srcset = wp_get_attachment_image_srcset($att_id, $size)
        ?: wp_get_attachment_image_srcset($att_id, 'large')
        ?: wp_get_attachment_image_srcset($att_id, 'full');

    if ( ! $srcset ) return $html;

    // Content column sizes.
    $content_sizes = '(min-width:1200px) 720px, (min-width:700px) 640px, 92vw';

    // Inject srcset/sizes once.
    $inject = ' srcset="' . esc_attr($srcset) . '" sizes="' . esc_attr($content_sizes) . '"';
    $html = preg_replace('/<img\s+/i', '<img' . $inject . ' ', $html, 1);

    // Non-LCP content images: hint lazy/low/async if not present.
    if ( strpos($html, ' loading=') === false )       $html = preg_replace('/<img\s+/i', '<img loading="lazy" ', $html, 1);
    if ( strpos($html, ' fetchpriority=') === false ) $html = preg_replace('/<img\s+/i', '<img fetchpriority="low" ', $html, 1);
    if ( strpos($html, ' decoding=') === false )      $html = preg_replace('/<img\s+/i', '<img decoding="async" ', $html, 1);

    return $html;
}, 9999, 3);

/* -------------------------------------------
 * 5) Avatars: keep them non-critical & smaller
 * ------------------------------------------- */
add_filter('option_show_avatars', function($value){
    // Disable avatars only on single post/page; keep global setting elsewhere.
    return is_singular() ? '0' : $value;
}, 10, 1);

add_filter('pre_get_avatar_data', function($args, $id_or_email){
    if ( is_singular() ) $args['size'] = 48; // tiny on singular
    return $args;
}, 10, 2);

add_filter('get_avatar', function ($html) {
    if ( ! psi_is_front() ) return $html;
    if ( strpos($html, '<img') === false ) return $html;

    if ( strpos($html, ' loading=') === false )       $html = preg_replace('/<img\s+/i', '<img loading="lazy" ', $html, 1);
    if ( strpos($html, ' fetchpriority=') === false ) $html = preg_replace('/<img\s+/i', '<img fetchpriority="low" ', $html, 1);
    if ( strpos($html, ' decoding=') === false )      $html = preg_replace('/<img\s+/i', '<img decoding="async" ', $html, 1);

    return $html;
}, 10);

add_filter('wp_get_loading_attr_default', function ($default, $tag, $context) {
    if ( $tag === 'img' && $context === 'avatar' ) return 'lazy';
    return $default;
}, 10, 3);

/* --------------------------------------------------------
 * 6) Header logo: tiny, safe tweak (prevents small shifts)
 * -------------------------------------------------------- */
/* --------------------------------------------------------
 * 6) Header logo: add width/height + safe loading
 * -------------------------------------------------------- */
add_filter('get_custom_logo', function($html){
    // 1) Make sure logo is not lazy (usually tiny, above the fold)
    $html = preg_replace('/\sloading="lazy"/i', ' loading="eager"', $html);
    // 2) Let browser decide priority
    $html = preg_replace('/\sfetchpriority="[^"]*"/i', '', $html);

    // 3) Ensure width/height on the <img> to avoid "Unsized image element"
    if ( strpos($html, '<img') !== false &&
         (strpos($html, ' width=') === false || strpos($html, ' height=') === false) ) {

        $logo_id = get_theme_mod('custom_logo');
        if ( $logo_id ) {
            $image = wp_get_attachment_image_src($logo_id, 'full');
            if ( $image ) {
                $w = (int) $image[1];
                $h = (int) $image[2];

                $html = preg_replace_callback(
                    '/<img\b[^>]*>/i',
                    function($m) use ($w, $h) {
                        $img = $m[0];

                        $need_width  = (strpos($img, ' width=') === false);
                        $need_height = (strpos($img, ' height=') === false);

                        if ( $need_width || $need_height ) {
                            $extra = '';
                            if ( $need_width )  { $extra .= ' width="'.$w.'"'; }
                            if ( $need_height ) { $extra .= ' height="'.$h.'"'; }

                            $img = preg_replace('/<img\b/i', '<img'.$extra, $img, 1);
                        }
                        return $img;
                    },
                    $html,
                    1 // only first <img> in the logo markup
                );
            }
        }
    }

    return $html;
}, 20);







/* --------------------------------------------------------------
 * 7) Twitter/X lightweight facade (replace heavy widgets script)
 * -------------------------------------------------------------- */
function wd4_extract_tweet_id_from_url( string $url ): string {
    if (! $url) return '';
    if (preg_match('#/(?:status|statuses)/(\d+)#', $url, $m)) return $m[1];
    return '';
}

function wd4_render_twitter_facade( string $tweet_id ): string {
    if (! $tweet_id) return '';
    $label = esc_attr__( 'View post on X', 'foxiz-child' );

    return sprintf(
        '<figure class="wp-block-embed is-type-rich is-provider-twitter wp-block-embed-twitter has-tw-facade" data-tweet-id="%1$s">
            <div class="wp-block-embed__wrapper">
                <button type="button" class="tw-facade" data-tweet-id="%1$s" aria-label="%2$s">
                    <span class="tw-facade__icon" aria-hidden="true"></span>
                    <span class="tw-facade__label">%3$s</span>
                </button>
                <div class="tw-embed__slot" aria-hidden="true"></div>
            </div>
         </figure>',
        esc_attr($tweet_id),
        $label,
        esc_html__( 'View post on X', 'foxiz-child' )
    );
}

// Convert Twitter/X embeds to facade
add_filter('embed_oembed_html', function ($html, $url) {
    $is_tw = (strpos($url, 'twitter.com') !== false) || (strpos($url, 'x.com') !== false);
    if (strpos($html, 'has-tw-facade') !== false) return $html; // already done
    if (! $is_tw) return $html;

    $id = wd4_extract_tweet_id_from_url($url);
    if (! $id && preg_match('#data-tweet-id=["\'](\d+)#i', (string) $html, $m)) $id = $m[1];
    if (! $id) return $html;

    return wd4_render_twitter_facade($id);
}, 9, 2);

add_filter('render_block', function (string $content, array $block) {
    if (($block['blockName'] ?? '') !== 'core/embed') return $content;

    $provider = strtolower($block['attrs']['providerNameSlug'] ?? '');
    if (! in_array($provider, array('twitter', 'x'), true)) return $content;

    $url = $block['attrs']['url'] ?? '';
    $id  = wd4_extract_tweet_id_from_url($url);
    if (! $id && preg_match('#data-tweet-id=["\'](\d+)#i', $content, $m)) $id = $m[1];
    return $id ? wd4_render_twitter_facade($id) : $content;
}, 9, 2);

// Allow a lightweight activator script if your theme enqueues it
add_filter('my_allowed_js_handles', function (array $list, string $context) {
    if (in_array($context, array('post','page'), true)) $list[] = 'tw-facade';
    return array_values(array_unique($list));
}, 10, 2);

/* ---------------------------------------------------------------
 * 8) One output buffer: normalize videos + strip Twitter widgets
 * --------------------------------------------------------------- */
add_action('template_redirect', function () {
    if ( ! psi_is_front() ) return;

    ob_start(function (string $html) {
        static $video_index = 0;

        // --- Video normalizer ---
        $fix_video = function ($video_html) use (&$video_index) {
            $video_index++;
            $desired_preload = ($video_index === 1) ? 'metadata' : 'none';

            // Ensure playsinline + preload
            if (!preg_match('/\splaysinline\b/i', $video_html)) {
                $video_html = preg_replace('/<video\b/i', '<video playsinline', $video_html, 1);
            }
            if (preg_match('/\spreload="[^"]*"/i', $video_html)) {
                $video_html = preg_replace('/\spreload="[^"]*"/i', ' preload="'.$desired_preload.'"', $video_html, 1);
            } else {
                $video_html = preg_replace('/<video\b/i', '<video preload="'.$desired_preload.'"', $video_html, 1);
            }

            // Inject intrinsic width/height (+ aspect-ratio) if missing.
            if ( ! preg_match('/\swidth="\d+"/i', $video_html) || ! preg_match('/\sheight="\d+"/i', $video_html) ) {
                if ( preg_match('#<source[^>]+src="([^"]+)"#i', $video_html, $ms) ) {
                    $src = html_entity_decode($ms[1], ENT_QUOTES);
                    $uploads = wp_upload_dir();

                    if ( ! function_exists('wp_read_video_metadata') ) {
                        @require_once ABSPATH . 'wp-admin/includes/media.php';
                    }

                    if ( strpos($src, $uploads['baseurl']) === 0 && function_exists('wp_read_video_metadata') ) {
                        $path = wp_normalize_path( str_replace($uploads['baseurl'], $uploads['basedir'], $src) );
                        if ( file_exists($path) ) {
                            $meta = wp_read_video_metadata($path);
                            if ( ! empty($meta['width']) && ! empty($meta['height']) ) {
                                $w = (int) $meta['width'];
                                $h = (int) $meta['height'];

                                $video_html = preg_replace('/<video\b/i', '<video width="'.$w.'" height="'.$h.'"', $video_html, 1);

                                if ( preg_match('/\sstyle="([^"]*)"/i', $video_html, $sm) ) {
                                    if ( stripos($sm[1], 'aspect-ratio:') === false ) {
                                        $newStyle = 'aspect-ratio: '.$w.'/'.$h.'; ' . $sm[1];
                                        $video_html = preg_replace('/\sstyle="[^"]*"/i', ' style="'.esc_attr($newStyle).'"', $video_html, 1);
                                    }
                                } else {
                                    $video_html = preg_replace('/<video\b/i', '<video style="aspect-ratio: '.$w.'/'.$h.';"', $video_html, 1);
                                }
                            }
                        }
                    }
                }
            }

            // Rebuild <source> list: keep first per unique src; optionally add MP4
            $video_html = preg_replace_callback('#(<video\b[^>]*>)(.*?)(</video>)#is', function ($m) {
                $open  = $m[1]; $inner = $m[2]; $close = $m[3];

                preg_match_all('#<source\b[^>]*src="([^"]+)"[^>]*>#i', $inner, $all, PREG_SET_ORDER);
                $seen = []; $keep = []; $has_mp4 = false; $first_src = null;

                foreach ($all as $s) {
                    $src = html_entity_decode($s[1], ENT_QUOTES);
                    $key = strtolower($src);
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $keep[] = $s[0];
                        if ($first_src === null) { $first_src = $src; }
                        if (stripos($s[0], 'type="video/mp4"') !== false) { $has_mp4 = true; }
                    }
                }

                if (!$has_mp4 && $first_src) {
                    $uploads = wp_upload_dir();
                    $baseurl = rtrim($uploads['baseurl'], '/');
                    $basedir = rtrim($uploads['basedir'], DIRECTORY_SEPARATOR);

                    if (strpos($first_src, $baseurl) === 0) {
                        $mp4url = preg_replace('/\.(webm|mkv|mov|mp4|m4v|avif|webp)(\?.*)?$/i', '.mp4', $first_src);
                        if ($mp4url !== $first_src) {
                            $mp4rel  = substr($mp4url, strlen($baseurl));
                            $mp4path = wp_normalize_path($basedir . $mp4rel);
                            if (file_exists($mp4path)) {
                                $keep[] = '<source src="' . esc_url($mp4url) . '" type="video/mp4">';
                            }
                        }
                    }
                }

                $inner_no_sources = preg_replace('#<source\b[^>]*>#i', '', $inner);
                $rebuilt = implode("\n", $keep) . "\n" . $inner_no_sources;

                return $open . $rebuilt . $close;
            }, $video_html, 1);

            return $video_html;
        };

        // Apply to <video> and <noscript><video>
        $html = preg_replace_callback('#<video\b[^>]*>.*?</video>#is', function ($m) use ($fix_video) {
            return $fix_video($m[0]);
        }, $html);

        $html = preg_replace_callback('#<noscript>\s*(<video\b[^>]*>.*?</video>)\s*</noscript>#is', function ($m) use ($fix_video) {
            return '<noscript>' . $fix_video($m[1]) . '</noscript>';
        }, $html);

        // Strip heavy Twitter widgets.js if it sneaks in
        $html = preg_replace(
            '#<script[^>]+src=[\'"]https://platform\.twitter\.com/widgets\.js[^>]*></script>#i',
            '',
            $html
        ) ?: $html;

        return $html;
    });
});

/* ------------------------------------------------------------------------------------
 * 9) Archive/Home cards: lazy/low/async + clamp sizes to ~256px typical card thumbnail
 * ------------------------------------------------------------------------------------ */


/* ---------------------------------------------------------
 * 10) Small global CSS (loads on every view via wp_head)
 * --------------------------------------------------------- */
 
add_action('wp_head', function(){ ?>
<style id="psi-global-inline">
  .archive .p-featured .featured-img,
  .blog .p-featured .featured-img,
  .category .p-featured .featured-img{
    display:block;
    width:100%;
    height:auto;
    max-width:100%;
  }

  .site-branding img[alt="AI Study Now"]{
    width:363px;
    height:120px;
    max-width:100%;
    aspect-ratio:363/120;
    display:block;
  }
</style>
<?php }, 5);




/* --------------------------------------------------------------
 * 11) oEmbeds: reserve space so iframes don't cause CLS
 * -------------------------------------------------------------- */
add_filter('embed_oembed_html', function($html, $url){
    if (!is_singular('post')) return $html;

    // Skip if it's already our lightweight Twitter facade
    if (strpos($html, 'has-tw-facade') !== false) return $html;

    // Skip Twitter/X embeds entirely (facade handled earlier)
    if (stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false) return $html;

    // If the iframe already has a fixed height, don’t wrap
    if (preg_match('#<iframe[^>]+height=["\']\d+#i', $html)) return $html;

    $u = strtolower($url);
    $ratio = '16/9'; $fixed = '';

    if (strpos($u,'tiktok.com')!==false)       $ratio = '9/16';
    if (strpos($u,'instagram.com')!==false)    $ratio = '1/1';
    if (strpos($u,'google.com/maps')!==false)  $ratio = '3/2';
    if (strpos($u,'soundcloud.com')!==false)   $fixed = '166px';
    if (strpos($u,'spotify.com')!==false)      $fixed = '352px';

    if ($fixed) return '<div class="embed-fixed-h" style="min-height:'.$fixed.'">'.$html.'</div>';
    return '<div class="embed-ar" style="aspect-ratio:'.$ratio.'">'.$html.'</div>';
}, 10, 2);

add_filter('render_block', function($content,$block){
    if (!is_singular('post')) return $content;
    if (($block['blockName'] ?? '') !== 'core/embed') return $content;

    $provider = strtolower($block['attrs']['providerNameSlug'] ?? '');
    if (in_array($provider, ['twitter','x'], true)) return $content;

    $ratio = '16/9'; $fixed = '';

    if ($provider==='tiktok')          $ratio = '9/16';
    elseif ($provider==='instagram')   $ratio = '1/1';
    elseif ($provider==='google-maps') $ratio = '3/2';
    elseif ($provider==='soundcloud')  $fixed = '166px';
    elseif ($provider==='spotify')     $fixed = '352px';

    if ($fixed) return '<div class="embed-fixed-h" style="min-height:'.$fixed.'">'.$content.'</div>';
    return '<div class="embed-ar" style="aspect-ratio:'.$ratio.'">'.$content.'</div>';
}, 10, 2);

/* --------------------------------------------------------------
 * 12) Single-post CLS CSS (meta/share row, embeds, hero)
 * -------------------------------------------------------------- */
add_action('wp_head', function(){
    if (!is_singular('post')) return; ?>
<style id="psi-cls-one-block">
/* --- DESKTOP hardening for the meta/share row (matches your markup) --- */
@media (min-width: 992px){
  /* isolate layout so late-loading icons/fonts don't ripple through */
  .single-meta{ contain: layout paint; }

  /* give the whole meta block a floor */
  .single-meta .smeta-in{ min-height:48px; }

  /* lock heights for the two right-side groups that PSI flags */
  .single-meta .smeta-extra,
  .single-meta .single-right-meta{
    height:48px;
    display:flex;
    align-items:center;
  }

  .single-meta .smeta-extra{ gap:.75rem; }

  /* reserve space for the "Share" header (icon + label) */
  .single-meta .t-shared-sec .t-shared-header{
    min-width:72px;
    display:inline-flex;
    align-items:center;
    gap:.4rem;
  }

  /* keep the word “Share” from reflowing on font swap */
  .single-meta .t-shared-sec .share-label{
    display:inline-block;
    min-width:42px;
    white-space:nowrap;
  }

  /* reserve space for the row of share icons that fades in later */
  .single-meta .t-shared-sec .effect-fadeout{
    display:inline-flex;
    min-width:110px; /* adjust if you add/remove icons */
  }

  /* reserve a slot for the reading-time text so digits don’t nudge layout */
  .single-meta .single-right-meta .meta-read{
    min-width:9ch; /* fits “8 Min Read” / “12 Min Read” */
    text-align:right;
    white-space:nowrap;
  }

  /* icon boxes: fixed footprint even if icon font/svg loads late */
  .single-meta .rbi,
  .single-meta .share-action i,
  .single-meta .t-shared-header .rbi{
    width:20px; height:20px; flex:0 0 20px; display:inline-block; line-height:1;
  }
}





/* --- HERO/FEATURED IMAGE --- */
.single-post .s-feat-outer{
  content-visibility:visible !important;
  contain:layout paint !important;
}

.single-post .s-feat{
  max-width:1280px;       /* match LCP size */
  margin:0 auto;
}

.single-post .s-feat img.featured-image{
  display:block;
  width:100%;
  height:auto;
  aspect-ratio:1280/720;  /* exact 16:9 for your LCP image */
}






/* --- EMBEDS: reserve space so iframes don't cause CLS --- */
.embed-ar{display:block;}
.embed-ar .wp-block-embed__wrapper,
.embed-ar iframe, .embed-ar object, .embed-ar embed{width:100%; height:100% !important; display:block;}
.embed-fixed-h{display:block; position:relative;}
.embed-fixed-h .wp-block-embed__wrapper,
.embed-fixed-h iframe, .embed-fixed-h object, .embed-fixed-h embed{width:100%; height:100% !important; display:block;}

/* --- GALLERY/SWIPER: give a floor before JS hydrates --- */
.swiper-container.pre-load{aspect-ratio:16/9; display:block;}
.swiper-container.pre-load .swiper-wrapper{height:100%;}
</style>
<?php }, 99);

/* --------------------------------------------------------------
 * 13) LCP PRELOAD: featured image on single posts (same-origin)
 * -------------------------------------------------------------- */


/* --------------------------------------------------------------
 * 14) SINGLE: ensure featured image eager/high (belt & suspenders)
 * -------------------------------------------------------------- */
add_filter('post_thumbnail_html', function($html, $post_id, $thumb_id, $size, $attr){
    if ( !is_singular('post') ) return $html;
    if ( (int)$thumb_id !== (int)get_post_thumbnail_id($post_id) ) return $html;

    // Force eager/high/auto on the single’s featured image element
    $has_loading = preg_match('/\sloading=/', $html);
    $has_fetch   = preg_match('/\sfetchpriority=/', $html);
    $has_dec     = preg_match('/\sdecoding=/', $html);

    if ($has_loading) $html = preg_replace('/\sloading=(["\']).*?\1/i', ' loading="eager"', $html);
    else $html = preg_replace('/<img/i','<img loading="eager"', $html,1);

    if ($has_fetch) $html = preg_replace('/\sfetchpriority=(["\']).*?\1/i', ' fetchpriority="high"', $html);
    else $html = preg_replace('/<img/i','<img fetchpriority="high"', $html,1);

    if ($has_dec) $html = preg_replace('/\sdecoding=(["\']).*?\1/i', ' decoding="auto"', $html);
    else $html = preg_replace('/<img/i','<img decoding="auto"', $html,1);

    return $html;
}, 9, 5);

/* --------------------------------------------------------------
 * 15) Defer non-critical scripts on single posts (adjust handles)
 * -------------------------------------------------------------- */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if ( !is_singular('post') ) return $tag;

    // Replace with your actual enqueued handles (view source for <script id="HANDLE-js">)
    $defer_handles = ['tw-facade','foxiz-share','social-share','sticky-sidebar'];

    if ( in_array($handle, $defer_handles, true) && strpos($tag, ' defer ') === false ) {
        $tag = str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}, 10, 3);

/* --------------------------------------------------------------
 * OPTIONAL: cap max srcset candidate width for shorter attributes
 * -------------------------------------------------------------- */
// add_filter('max_srcset_image_width', function($max){ return 1600; });

