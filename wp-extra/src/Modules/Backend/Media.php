<?php
namespace WPEXtra\Modules\Backend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Helper;
use WPEXtra\Base;

class Media extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'meta_images',
		'autoupload',
		'image_limit',
		'image_quality',
		'media_thumbnails',
		'media_functions',
		'big_image_threshold',
		'save_images',
		'autoset',
		'allow_filetype',
		'rename_images',
		'media_default',
	];
    
    public function meta_images() {
        add_action('add_attachment', [$this, 'update_image_metadata']);
    }
    
    public function autoupload() {
        add_action('wp_handle_upload', [$this, 'auto_upload_images']);
    }
    
    public function image_limit() {
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_image_limit']);
    }
    
    public function image_quality() {
        add_filter('jpeg_quality', [$this, 'jpeg_quality']);
        add_filter('wp_editor_set_quality', [$this, 'jpeg_quality']);
    }
    
    public function media_thumbnails() {
        add_filter('intermediate_image_sizes_advanced', [$this, 'remove_image_sizes']);
    }
    
    public function media_functions() {
        $functions = (array) Helper::get_option('media_functions', []);
        if (in_array('threshold', $functions, true) || Helper::is_feature_active('big_image_threshold')) {
			add_filter('big_image_size_threshold', '__return_false');
		}
    }

    public function big_image_threshold() {
        add_filter('big_image_size_threshold', '__return_false');
    }
    
    public function save_images() {
        add_action('save_post', [$this, 'save_post_images'], 10, 3);
    }
    
    public function autoset() {
        add_action('save_post', [$this, 'auto_featured_image']);
    }
    
    public function allow_filetype() {
        add_filter('wp_check_filetype_and_ext', [$this, 'allow_svg_filetype'], 10, 4);
        add_filter('upload_mimes', [$this, 'allow_svg_mimes']);
        add_filter('mime_types', [$this, 'allow_svg_mimes']);
        add_filter('wp_handle_upload_prefilter', [$this, 'sanitize_svg_upload']);
        add_action('admin_head', [$this, 'svg_admin_css']);
    }

    public function allow_svg_mimes($mimes) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        $mimes['webp'] = 'image/webp';
        $mimes['ico']  = 'image/x-icon';
        return $mimes;
    }

    public function allow_svg_filetype($checked, $file, $filename, $mimes) {
        if (!$checked['type']) {
            $check = wp_check_filetype($filename, $mimes);
            $ext   = $check['ext'];
            $type  = $check['type'];
            if ($ext === 'svg' || $ext === 'svgz') {
                $checked = [
                    'ext'             => $ext,
                    'type'            => 'image/svg+xml',
                    'proper_filename' => $filename,
                ];
            }
        }
        return $checked;
    }

    public function sanitize_svg_upload($file) {
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === 'svg' || (isset($file['type']) && $file['type'] === 'image/svg+xml')) {
            if (!current_user_can('upload_files')) {
                $file['error'] = esc_html__('Permission denied.', 'wp-extra');
                return $file;
            }

            if (!empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
                $content = @file_get_contents($file['tmp_name']);
                if ($content !== false) {
                    // Check for XXE (XML External Entity Injection)
                    if (preg_match('/<!ENTITY|<!DOCTYPE|SYSTEM\s*["\']|PUBLIC\s*["\']/i', $content)) {
                        $file['error'] = esc_html__('Security Error: Uploaded SVG contains suspicious scripts or event handlers.', 'wp-extra');
                        return $file;
                    }

                    // Check for dangerous tags (script, foreignObject, iframe, embed, object, meta, link, applet)
                    if (preg_match('/<\s*(?:script|foreignObject|iframe|embed|object|meta|link|applet)\b/i', $content)) {
                        $file['error'] = esc_html__('Security Error: Uploaded SVG contains suspicious scripts or event handlers.', 'wp-extra');
                        return $file;
                    }

                    // Check for dangerous protocols and data URIs in attributes
                    if (preg_match('/(?:href|src|xlink:href)\s*=\s*["\']?\s*(?:javascript|vbscript|data:\s*text\/html|data:\s*application\/javascript):/i', $content)) {
                        $file['error'] = esc_html__('Security Error: Uploaded SVG contains suspicious scripts or event handlers.', 'wp-extra');
                        return $file;
                    }

                    // Check for all inline JavaScript event handlers (e.g. onload, onerror, onclick, onmouseover, onbegin, etc.)
                    if (preg_match('/\bon[a-z0-9_-]+\s*=/i', $content)) {
                        $file['error'] = esc_html__('Security Error: Uploaded SVG contains suspicious scripts or event handlers.', 'wp-extra');
                        return $file;
                    }
                }
            }
        }
        return $file;
    }

    public function svg_admin_css() {
        echo '<style>
            .media-icon img[src$=".svg"],
            .attachment-preview img[src$=".svg"],
            .thumbnail img[src$=".svg"] {
                width: 100% !important;
                height: auto !important;
            }
        </style>';
    }

    public function media_default() {
        add_filter('get_post_metadata', [$this, 'set_media_default'], 10, 4);
    }

    public function set_media_default($null, $object_id, $meta_key, $single) {
        if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $null;
        }
        
        if ($meta_key !== '_thumbnail_id') {
            return $null;
        }

        $post_type = get_post_type($object_id);
        if (!$post_type || !post_type_supports($post_type, 'thumbnail')) {
            return $null;
        }

        $meta_cache = wp_cache_get($object_id, 'post_meta');
        if (!$meta_cache) {
            $meta_cache = update_meta_cache('post', [$object_id]);
            $meta_cache = $meta_cache[$object_id] ?? [];
        }

        if (!empty($meta_cache['_thumbnail_id'][0])) {
            return $null;
        }

        $default_thumbnail_id = Helper::get_option('media_default');
        if (empty($default_thumbnail_id)) {
            return $null;
        }

        $meta_cache['_thumbnail_id'][0] = $default_thumbnail_id;
        wp_cache_set($object_id, $meta_cache, 'post_meta');

        return $default_thumbnail_id;
    }
    
    public function save_post_images($post_id, $post, $update) {
        $flip        = (bool) Helper::get_option('autoflip');
        $crop_w      = Helper::get_option('crop_width', '');
        $crop_h      = Helper::get_option('crop_height', '');
        $set_quality = intval(Helper::get_option('image_quality', 90));

        if (!Helper::get_option('save_images')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
        if (!is_object($post) || $post->post_status !== 'publish') return;

        $post_content = $post->post_content;

        preg_match_all(
            '#<img[^>]+(?:src|data-src|data-lazy|data-original|data-srcset|srcset)\s*=\s*["\']([^"\']+)["\'][^>]*?(?:alt\s*=\s*["\']([^"\']*)["\'])?[^>]*>#i',
            stripslashes($post_content),
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) return;

        $urls = [];
        $alts = [];

        foreach ($matches as $m) {
            $raw = trim($m[1]);
            if (strpos($raw, ',') !== false) {
                $raw = trim(explode(',', $raw)[0]);
            }
            $raw = trim(explode(' ', $raw)[0]);

            if ($raw) {
                $urls[] = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
                $alts[] = !empty($m[2]) ? wp_strip_all_tags($m[2]) : $post->post_title;
            }
        }

        if (empty($urls)) return;

        $unique_map = [];
        foreach ($urls as $i => $u) {
            if (!isset($unique_map[$u])) {
                $unique_map[$u] = $alts[$i] ?? $post->post_title;
            }
        }

        $upload_dir = wp_upload_dir();
        $changed = false;
        $index = 0;

        foreach ($unique_map as $url => $alt_text) {
            $url_clean = strtok($url, '?');
            $url_clean = strtok($url_clean, '#');

            $img_host  = wp_parse_url($url_clean, PHP_URL_HOST);
            $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

            if (!$img_host || strcasecmp($img_host, $site_host) === 0) continue;
            if (attachment_url_to_postid($url_clean)) continue;

            $index++;
            if ($index > 20) break; // Limit to 20 images per save to avoid timeouts

            $parsed = wp_parse_url($url);
            if (empty($parsed['path'])) continue;

            $try_urls = [$url];
            $url_no_query = strtok($url, '?');
            if ($url_no_query && $url_no_query !== $url) {
                $try_urls[] = $url_no_query;
            }
            if (preg_match('#https?://i[0-2]\.wp\.com/#i', $url)) {
                $try_urls[] = preg_replace('#https?://i[0-2]\.wp\.com/#i', 'https://', $url);
            }
            $try_urls = array_unique($try_urls);

            if (Helper::get_option('rename_images')) {
                $base_slug = sanitize_title($post->post_name ?: $post->post_title);
                $img_slug  = $base_slug . '-' . $index;
            } else {
                $filename = pathinfo(basename($parsed['path']), PATHINFO_FILENAME);
                $img_slug = $filename ?: 'image-' . $index;
            }
            $img_slug = sanitize_file_name($img_slug);

            $img_path = false;
            foreach ($try_urls as $try_url) {
                $img_path = $this->create_img(
                    $try_url,
                    $img_slug,
                    $flip,
                    $crop_w,
                    $crop_h,
                    $set_quality
                );
                if ($img_path) break;
            }

            if (!$img_path) continue;

            $filetype = wp_check_filetype(basename($img_path), null);
            if (empty($filetype['type'])) continue;

            $target_dir = dirname($img_path);
            $base_name  = basename($img_path);
            $unique     = wp_unique_filename($target_dir, $base_name);
            if ($unique !== $base_name) {
                $new_path = trailingslashit($target_dir) . $unique;
                @rename($img_path, $new_path);
                if (file_exists($new_path)) $img_path = $new_path;
            }

            $url_new = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $img_path);
            $url_new = str_replace('\\', '/', $url_new);

            $attachment = [
                'guid'           => $url_new,
                'post_mime_type' => $filetype['type'],
                'post_title'     => mb_substr($alt_text, 0, 200),
                'post_content'   => $alt_text,
                'post_excerpt'   => $alt_text,
                'post_status'    => 'inherit',
            ];

            $attachment_id = attachment_url_to_postid($url_new);
            if (!$attachment_id) {
                $attachment_id = wp_insert_attachment($attachment, $img_path, $post_id);
                if (!is_wp_error($attachment_id)) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $meta = wp_generate_attachment_metadata($attachment_id, $img_path);
                    wp_update_attachment_metadata($attachment_id, $meta);
                }
            }

            if ($attachment_id && !is_wp_error($attachment_id)) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
            }

            foreach ($try_urls as $old) {
                if (strpos($post_content, $old) !== false) {
                    $post_content = str_replace($old, $url_new, $post_content);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            remove_action('save_post', [$this, 'save_post_images'], 10);
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $post_content
            ]);
            add_action('save_post', [$this, 'save_post_images'], 10, 3);
        }
    }

    public function create_img($url, $file_name, $flip = false, $crop_w = '', $crop_h = '', $set_quality = 90) {
        $allowed = ['jpg','jpeg','jpe','png','gif','webp','bmp','tif','tiff','jfif'];
        $allowed = array_map('preg_quote', $allowed);

        if (!preg_match('/\.(' . implode('|', $allowed) . ')(\?|$)/i', $url, $m)) {
            return false;
        }

        $ext = strtolower($m[1]);
        if ($ext === 'jfif') $ext = 'jpg';

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $tmp = download_url($url, 10);

        if (is_wp_error($tmp)) {
            $response = wp_safe_remote_get($url, ['timeout' => 10]);
            if (is_wp_error($response)) return false;

            $mime = wp_remote_retrieve_header($response, 'content-type');
            if (strpos($mime, 'image/') !== 0) return false;

            $body = wp_remote_retrieve_body($response);
            $tmp = wp_tempnam($url);
            file_put_contents($tmp, $body);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) {
            wp_delete_file($tmp);
            return false;
        }

        $upload = wp_upload_dir();
        $path = $upload['path'] . '/' . $file_name . '.' . $ext;
        for ($i = 1; file_exists($path); $i++) {
            $path = $upload['path'] . '/' . $file_name . '-' . $i . '.' . $ext;
        }

        if (!@copy($tmp, $path)) {
            wp_delete_file($tmp);
            return false;
        }
        wp_delete_file($tmp);

        if ($flip || ($crop_w && $crop_h)) {
            $editor = wp_get_image_editor($path);
            if (!is_wp_error($editor)) {
                $editor->set_quality($set_quality);
                if ($flip) {
                    $editor->flip(true, false);
                }
                if ($crop_w >= 100 && $crop_h >= 100) {
                    $editor->resize((int)$crop_w, (int)$crop_h, true);
                }
                $editor->save($path);
            }
        }

        return $path;
    }
		
    public function auto_featured_image($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
        if (!has_post_thumbnail($post_id)) {
            $attached_images = get_children([
                'post_parent'    => $post_id,
                'post_type'      => 'attachment',
                'post_mime_type' => 'image',
                'posts_per_page' => 1,
            ]);
            if (!empty($attached_images)) {
                $first_image = reset($attached_images);
                set_post_thumbnail($post_id, $first_image->ID);
                return;
            }

            // Fallback: Check first <img> src embedded inside post_content
            $post = get_post($post_id);
            if ($post && !empty($post->post_content)) {
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $match)) {
                    $img_url = strtok($match[1], '?');
                    $attachment_id = attachment_url_to_postid($img_url);
                    if ($attachment_id) {
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }
            }
        }
    }

    public function remove_image_sizes($sizes) {
		$list_thumbnails = get_intermediate_image_sizes();
		$disablethumbnails = (array) Helper::get_option('media_thumbnails', []);
		foreach ($list_thumbnails as $value) {
			if (in_array($value, $disablethumbnails, true)) {
				unset($sizes[$value]);
			}
		}
		return $sizes;
	}

    public function auto_upload_images($image_data) {
        $autoconverter = Helper::get_option('autoconverter');
        $max_width     = intval(Helper::get_option('image_max_width', 0));
        $max_height    = intval(Helper::get_option('image_max_height', 0));
        $quality       = intval(Helper::get_option('image_quality', 90));

        if (
            $image_data['type'] === 'image/gif'
            && $this->is_animated_gif($image_data['file'])
        ) {
            return $image_data;
        }

        $image_editor = wp_get_image_editor($image_data['file']);

        if (!is_wp_error($image_editor)) {
            $image_editor->set_quality($quality);
            $sizes = $image_editor->get_size();

            if (
                ($max_width  && $sizes['width']  > $max_width) ||
                ($max_height && $sizes['height'] > $max_height)
            ) {
                $image_editor->resize($max_width, $max_height, false);
            }

            if ($autoconverter === 'webp' && $image_data['type'] !== 'image/webp') {
                [$newPath, $newUrl] = $this->generate_new_path($image_data, 'webp');
                $saved = $image_editor->save($newPath, 'image/webp');
                if (!is_wp_error($saved)) {
                    wp_delete_file($image_data['file']);
                    $image_data['file'] = $saved['path'];
                    $image_data['url']  = $newUrl;
                    $image_data['type'] = 'image/webp';
                }
            } elseif ($autoconverter === 'jpg' && $image_data['type'] === 'image/png') {
                [$newPath, $newUrl] = $this->generate_new_path($image_data, 'jpg');
                $saved = $image_editor->save($newPath, 'image/jpeg');
                if (!is_wp_error($saved)) {
                    wp_delete_file($image_data['file']);
                    $image_data['file'] = $saved['path'];
                    $image_data['url']  = $newUrl;
                    $image_data['type'] = 'image/jpeg';
                }
            } else {
                $image_editor->save($image_data['file']);
            }
        }

        return $image_data;
    }

    private function generate_new_path($params, $ext) {
        $basePath = preg_replace('/\.[^.]+$/', '', $params['file']);
        $baseUrl  = preg_replace('/\.[^.]+$/', '', $params['url']);

        $newPath = $basePath . '.' . $ext;
        $newUrl  = $baseUrl . '.' . $ext;

        for ($i = 1; file_exists($newPath); $i++) {
            $newPath = $basePath . "-$i.$ext";
            $newUrl  = $baseUrl . "-$i.$ext";
        }

        return [$newPath, $newUrl];
    }

    private function is_animated_gif($filename) {
        if (!($fh = @fopen($filename, 'rb'))) {
            return false;
        }
        $count = 0;
        $chunk = false;

        while (!feof($fh) && $count < 2) {
            $chunk = ($chunk ? substr($chunk, -20) : "") . fread($fh, 1024 * 100);
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
        }

        fclose($fh);
        return $count > 1;
    }

    public function validate_image_limit($file) {
        $limit = intval(Helper::get_option('image_limit'));
        if (!$limit) {
            return $file;
        }
        $image_size = ($file['size'] ?? 0) / 1024;
        $is_image   = isset($file['type']) && strpos($file['type'], 'image') !== false;
        if ($image_size > $limit && $is_image) {
            $file['error'] = sprintf(
                __('Your picture is too large. It has to be smaller than %dKB.', 'wp-extra'),
                $limit
            );
        }
        return $file;
    }
    
    public function jpeg_quality($quality) {
        return intval(Helper::get_option('image_quality', 90));
    }

    public function update_image_metadata($attachment_ID) {
        if (Helper::get_option('meta_images')) {
            if (!current_user_can('edit_post', $attachment_ID)) {
                return;
            }
            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], home_url()) !== 0) {
                return;
            }
            if (!empty($_REQUEST['post_id']) && !Helper::get_option('meta_images_filename')) {
                $post_id = (int)$_REQUEST['post_id'];
            } else {
                $post_id = $attachment_ID;
            }
            $post_object = get_post($post_id);
            $post_title  = isset($post_object->post_title) ? $post_object->post_title : '';
            if (!empty($post_title)) {
                $post_title = preg_replace('/\s*[-_\s]+\s*/', ' ', $post_title);
                $post_title = ucwords(strtolower($post_title));
                $post_data = [
                    'ID'           => $attachment_ID, 
                    'post_title'   => $post_title,
                    'post_content' => $post_title,
                    'post_excerpt' => $post_title,
                ];
                update_post_meta($attachment_ID, '_wp_attachment_image_alt', $post_title);
                wp_update_post($post_data);
            }
        }
    }

    public function rename_images() {
        add_filter('sanitize_file_name', [$this, 'update_image_filename_from_post_slug'], 10, 1);
    }

    public function update_image_filename_from_post_slug($filename) {
        $filename = Helper::normalizeString($filename);

        if (!empty($_REQUEST['post_id'])) {
            $post_id = (int)$_REQUEST['post_id'];
            $exists  = get_post_status($post_id);
            $info    = pathinfo($filename);

            if (isset($exists) && !empty($info['extension']) && in_array(strtolower($info['extension']), ['jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff'], true)) {
                $post_object = get_post($post_id);
                $ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
                $name = '';
                $opt = Helper::get_option('rename_images');
                if ($opt === 'date') {
                    $name = '-' . date('Y-m-d');
                } elseif ($opt === 'filename') {
                    $name = '-' . basename($filename, $ext);
                }

                $post_name  = isset($post_object->post_name) ? $post_object->post_name : '';
                $post_title = isset($post_object->post_title) ? $post_object->post_title : '';

                if (!empty($post_name)) {
                    $filename = strtolower($post_name . $name . $ext);
                } elseif (!empty($post_title)) {
                    $normalized_post_title = Helper::normalizeString($post_title);
                    $filename = strtolower($normalized_post_title . $name . $ext);
                }
            }
        }

        return $filename;
    }
}
