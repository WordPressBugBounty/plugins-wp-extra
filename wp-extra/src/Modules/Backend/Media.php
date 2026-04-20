<?php
namespace WPEXtra\Modules\Backend;

use WPEXtra\Settings;
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
		'save_images',
		'autoset',
		'allow_filetype',
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
    }
    
    public function media_thumbnails() {
        add_filter( 'intermediate_image_sizes_advanced', [$this, 'remove_image_sizes']);
    }
    
    public function media_functions() {
        if (in_array('threshold',  Settings::get_option('media_functions'))) {
			add_filter( 'big_image_size_threshold', '__return_false' );
		}
        if (in_array('exif',  Settings::get_option('media_functions'))) {
			add_filter( 'wp_image_maybe_exif_rotate', '__return_false' );
		}
    }
    
    public function save_images() {
        add_action( 'save_post', [$this, 'save_post_images'], 10, 3 );
    }
    
    public function autoset() {
        add_action( 'save_post', [$this, 'auto_featured_image'] );
    }
    
    public function allow_filetype() {
        add_filter('wp_check_filetype_and_ext', [$this, 'ignore_upload_ext'], 10, 4);
        add_filter('mime_types', [$this, 'webp_upload_mimes']);
        add_filter('file_is_displayable_image', [$this, 'webp_is_displayable'], 10, 2);
    }
    
    public function save_post_images($post_id, $post, $update) {
        $flip        = Settings::get_option('autoflip') ? true : false;
        $crop_w      = Settings::get_option('crop_width') ?: '';
        $crop_h      = Settings::get_option('crop_height') ?: '';
        $set_quality = intval(Settings::get_option('image_quality', 90));

        if (!Settings::get_option('save_images')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

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
            if ($index > 50) break;

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

            if (Settings::get_option('rename_images')) {
                $base_slug = sanitize_title($post->post_name ?: $post->post_title);
                $img_slug = $base_slug . '-' . $index;
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

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            $response = wp_remote_get($url, ['timeout' => 20]);
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

            switch ($ext) {
                case 'png':  $img = imagecreatefrompng($path); break;
                case 'gif':  $img = imagecreatefromgif($path); break;
                case 'webp': $img = imagecreatefromwebp($path); break;
                default:     $img = imagecreatefromjpeg($path); break;
            }

            if (!$img) return false;

            $w = imagesx($img);
            $h = imagesy($img);

            $tw = ($crop_w >= 100) ? $crop_w : $w;
            $th = ($crop_h >= 100) ? $crop_h : $h;

            $thumb = imagecreatetruecolor($tw, $th);
            imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));

            if ($flip) {
                imagecopyresampled($thumb, $img, 0, 0, $w - 1, 0, $tw, $th, -$w, $h);
            } else {
                imagecopyresampled($thumb, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
            }

            imagejpeg($thumb, $path, $set_quality);
            imagedestroy($thumb);
            imagedestroy($img);
        }

        return $path;
    }
		
    public function auto_featured_image() {
        global $post;
        if ($post && !has_post_thumbnail($post->ID)) {
            $attached_image = get_children( "post_parent=$post->ID&amp;post_type=attachment&amp;post_mime_type=image&amp;numberposts=1" );
            if ($attached_image) {
                  foreach ($attached_image as $attachment_id => $attachment) {
                       set_post_thumbnail($post->ID, $attachment_id);
                  }
             }
        }
    }

    public function remove_image_sizes( $sizes ) {
		$list_thumbnails = get_intermediate_image_sizes();
		$disablethumbnails = Settings::get_option('media_thumbnails');
		foreach ( $list_thumbnails as $value ) {
			if ( in_array( $value, $disablethumbnails ) ) {
				unset( $sizes[ $value ] );
			}
		}
		return $sizes;

	}

    public function auto_upload_images($image_data) {
        $autoconverter = Settings::get_option('autoconverter');
        $max_width     = intval(Settings::get_option('image_max_width', 0));
        $max_height    = intval(Settings::get_option('image_max_height', 0));

        if (
            $image_data['type'] === 'image/gif'
            && $this->is_animated_gif($image_data['file'])
        ) {
            return $image_data;
        }

        if ($image_data['type'] === 'image/png') {
            if ($autoconverter === 'jpg') {
                $image_data = $this->convert_png_to_jpg($image_data);
            } elseif ($autoconverter === 'webp') {
                $image_data = $this->convert_png_to_webp($image_data);
            }
        }

        if ($image_data['type'] === 'image/jpeg' && $autoconverter === 'webp') {
            $image_data = $this->convert_jpg_to_webp($image_data);
        }

        $image_editor = wp_get_image_editor($image_data['file']);

        if (!is_wp_error($image_editor)) {
            $sizes = $image_editor->get_size();

            if (
                ($max_width  && $sizes['width']  > $max_width) ||
                ($max_height && $sizes['height'] > $max_height)
            ) {
                $image_editor->resize($max_width, $max_height, false);
                $image_editor->save($image_data['file']);
            }
        }

        return $image_data;
    }

    private function convert_png_to_jpg($params) {
        $img = imagecreatefrompng($params['file']);
        if (!$img) {
            return $params;
        }

        $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, true);
        imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));

        [$newPath, $newUrl] = $this->generate_new_path($params, 'jpg');

        if (imagejpeg($bg, $newPath)) {
            wp_delete_file($params['file']);
            $params['file'] = $newPath;
            $params['url']  = $newUrl;
            $params['type'] = 'image/jpeg';
        }

        return $params;
    }

    private function convert_png_to_webp($params) {
        if (!function_exists('imagewebp')) {
            return $params;
        }

        $img = imagecreatefrompng($params['file']);
        if (!$img) {
            return $params;
        }

        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        [$newPath, $newUrl] = $this->generate_new_path($params, 'webp');

        if (imagewebp($img, $newPath)) {
            wp_delete_file($params['file']);
            $params['file'] = $newPath;
            $params['url']  = $newUrl;
            $params['type'] = 'image/webp';
        }

        return $params;
    }

    private function convert_jpg_to_webp($params) {
        if (!function_exists('imagewebp')) {
            return $params;
        }

        $img = imagecreatefromjpeg($params['file']);
        if (!$img) {
            return $params;
        }

        imagepalettetotruecolor($img);
        imagealphablending($img, true);

        [$newPath, $newUrl] = $this->generate_new_path($params, 'webp');

        if (imagewebp($img, $newPath)) {
            wp_delete_file($params['file']);
            $params['file'] = $newPath;
            $params['url']  = $newUrl;
            $params['type'] = 'image/webp';
        }

        return $params;
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
        $limit = intval(Settings::get_option('image_limit'));
        if (!$limit) {
            return;
        }
        $image_size = $file['size'] / 1024;
        $is_image = strpos($file['type'], 'image');
        if ($image_size > $limit && $is_image !== false) {
            $file['error'] = __('Your picture is too large. It has to be smaller than ', 'wp-extra') . '' . $limit . 'KB';
        }
        return $file;
    }
    
    public function jpeg_quality($quality) {
        return intval(Settings::get_option('image_quality', 90));
    }

    public function update_image_metadata($attachment_ID) {
        if (Settings::get_option('meta_images')) {
            if (!current_user_can('edit_post', $attachment_ID)) {
                return;
            }
            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], home_url()) !== 0) {
                return;
            }
            if (!empty($_REQUEST['post_id']) && !Settings::get_option('meta_images_filename')) {
                $post_id = (int)$_REQUEST['post_id'];
            } else {
                $post_id = $attachment_ID;
            }
            $post_object = get_post($post_id);
            $post_title = isset($post_object->post_title) ? $post_object->post_title : '';
            if (!empty($post_title)) {
                $post_title = preg_replace('/\s*[-_\s]+\s*/', ' ', $post_title);
                $post_title = ucwords(strtolower($post_title));
                $post_data = array(
                    'ID' => $attachment_ID, 
                    'post_title' => $post_title,
                    'post_content' => $post_title,
                    'post_excerpt' => $post_title,
                );
                update_post_meta($attachment_ID, '_wp_attachment_image_alt', $post_title);
                wp_update_post($post_data);
            }
        }
    }
    
    function ignore_upload_ext($checked, $file, $filename, $mimes){
		if(!$checked['type']){
			$wp_filetype = wp_check_filetype( $filename, $mimes );
			$ext = $wp_filetype['ext'];
			$type = $wp_filetype['type'];
			$proper_filename = $filename;
            if ($type && 0 === strpos($type, 'image/')) {
                if ($ext === 'ico' && $type === 'image/x-icon') {
                    $checked = compact('ext', 'type', 'proper_filename');
                }
                elseif ($ext !== 'svg') {
                    $ext = $type = false;
                }
            }
			$checked = compact('ext','type','proper_filename');
		}
		return $checked;
	}
    
    function webp_upload_mimes($existing_mimes) {
        $existing_mimes['webp'] = 'image/webp';
        $existing_mimes['ico'] = 'image/x-icon';
        return $existing_mimes;
    }
    
    function webp_is_displayable($result, $path) {
        if ($result === false) {
            $displayable_image_types = array( IMAGETYPE_WEBP );
            $info = @getimagesize( $path );
            if (empty($info)) {
                $result = false;
            } elseif (!in_array($info[2], $displayable_image_types)) {
                $result = false;
            } else {
                $result = true;
            }
        }
        return $result;
    }
    
}
