<?php

namespace WPEXtra\Modules;

class Media {

    public function __construct() {
        if(wp_extra_get_option('meta_images')) {
            add_action('add_attachment', [$this, 'update_image_metadata']);
        }
        if(wp_extra_get_option('media_default')) {
            add_filter( 'get_post_metadata', [$this, 'set_media_default'], 10, 4 );
        }
        if(wp_extra_get_option('resize_images')) {
            add_action('wp_handle_upload', [$this, 'resize_image']);
        }
        if(wp_extra_get_option('image_limit')) {
            add_filter('wp_handle_upload_prefilter', [$this, 'validate_image_limit']);
        }
        if(wp_extra_get_option('media_thumbnails')) {
            add_filter( 'intermediate_image_sizes_advanced', [$this, 'remove_image_sizes']);
        }
        if (wp_extra_get_option('media_functions') && in_array('threshold',  wp_extra_get_option('media_functions'))) {
			add_filter( 'big_image_size_threshold', '__return_false' );
		}
        if (wp_extra_get_option('media_functions') && in_array('exif',  wp_extra_get_option('media_functions'))) {
			add_filter( 'wp_image_maybe_exif_rotate', '__return_false' );
		}
        
        if(wp_extra_get_option('save_images')) {
            add_action( 'save_post', [$this, 'save_post_images'], 10, 3 );
        }
        
        if(wp_extra_get_option('autoset')) {
            add_action('save_post', [$this, 'auto_featured_image']);
        }
        
        if(wp_extra_get_option('delete_attached')) {
            add_action('before_delete_post', [$this, 'delete_attachments']);
        }
    }
    
    public function delete_attachments( $post_id ) {
        $attachments = get_attached_media( '', $post_id );
        foreach ($attachments as $attachment) {
            $attachment_used_in = $this->get_posts_by_attachment_id($attachment->ID);
            $is_parent = $attachment->post_parent === $post_id;
            if( $is_parent ) {
                $other_posts_exits_content = array_diff( $attachment_used_in['content'],[$post_id]);
                $other_posts_exits_thumb = array_diff( $attachment_used_in['thumbnail'],[$post_id]);
                $other_posts_exits = array_merge($other_posts_exits_content, $other_posts_exits_thumb);
                if( !empty($other_posts_exits) ) {
                    wp_update_post([
                        'ID' => $attachment->ID,
                        'post_parent' => $other_posts_exits[0]
                    ]);
                } else {
                    wp_delete_attachment( $attachment->ID, true );
                }
            }
        }
    }
    
    private function get_posts_by_attachment_id( $attachment_id ) {
        $used_as_thumbnail = array();
        if ( wp_attachment_is_image( $attachment_id ) ) {
            $thumbnail_query = new \WP_Query( array(
                'meta_key'       => '_thumbnail_id',
                'meta_value'     => $attachment_id,
                'post_type'      => 'any',
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'posts_per_page' => - 1,
                'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
            ) );

            $used_as_thumbnail = $thumbnail_query->posts;
        }
        $attachment_urls = array( wp_get_attachment_url( $attachment_id ) );
        if ( wp_attachment_is_image( $attachment_id ) ) {
            foreach ( get_intermediate_image_sizes() as $size ) {
                $intermediate = image_get_intermediate_size( $attachment_id, $size );
                if ( $intermediate ) {
                    $attachment_urls[] = $intermediate['url'];
                }
            }
        }
        $used_in_content = array();
        foreach ( $attachment_urls as $attachment_url ) {
            $content_query = new \WP_Query( array(
                's'              => $attachment_url,
                'post_type'      => 'any',
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'posts_per_page' => - 1,
                'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
            ) );
            $used_in_content = array_merge( $used_in_content, $content_query->posts );
        }
        $used_in_content = array_unique( $used_in_content );
        return array(
            'thumbnail' => $used_as_thumbnail,
            'content'   => $used_in_content,
        );
    }
    
    public function save_post_images($post_id, $post, $update) {
        $convert = wp_extra_get_option('image_convert') ? true : false;
        $flip = wp_extra_get_option('autoflip') ? true : false;
        $crop_w = wp_extra_get_option('crop_width') ? wp_extra_get_option('crop_width') : '';
        $crop_h = wp_extra_get_option('crop_height') ? wp_extra_get_option('crop_height') : '';
        $set_quality = intval(wp_extra_get_option('image_quality'));
        
        if (!wp_extra_get_option('save_images')) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }
        remove_action( 'save_post', [ $this, 'save_post_images' ] );
        $post_content = $post->post_content;
        preg_match_all('/<img[^>]*(?:src|data-src|data-lazy)=["\']([^"\']+)["\'](?:[^>]*alt=["\']([^"\']+)["\'])?/i', stripslashes($post_content), $matches);

        $urls = $matches[1];
        $unique_urls = array_unique($urls); 
        
        if (($matches[2]) && wp_extra_get_option('meta_images_filename')) {
            $alts = $matches[2];
        } else {
            $alts = $post->post_title;
        }
        //$unique_urls = wp_extract_urls( $matches[2] );
        foreach($unique_urls as $key => $url){
            if (wp_extra_get_option('rename_images')) { 
                $img_slug = $post->post_name;
            } else {
                $img_slug = pathinfo(basename(wp_parse_url($url)['path']), PATHINFO_FILENAME);
            }
            $post_title = $alts[$key] ?? $post->post_title;
            $parsed_url = wp_parse_url($url);

            if (!isset($parsed_url['host'])) {
                continue;
            }
            $host_url = $parsed_url['host'];

            if (empty(strpos(site_url(), $host_url))){
                $img_path = $this->create_img($url, $img_slug, $convert, $flip, $crop_w, $crop_h, $set_quality);
                if ($img_path){
                    $filetype = wp_check_filetype(basename($img_path), null);
                    $upload_dir = wp_upload_dir();
                    $url_new = $upload_dir['url'] . '/' . basename($img_path);

                    $attachment = array(
                        'guid'           => $url_new, 
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => $post_title,
                        'post_content'   => $post_title,
                        'post_excerpt'   => $post_title,
                        'post_status'    => 'inherit'
                    );

                    $attachment_id = wp_insert_attachment($attachment, $img_path, $post_id);
                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $img_path);
                    wp_update_attachment_metadata($attachment_id, $attach_data);
                    update_post_meta($attachment_id, '_wp_attachment_image_alt', $post_title);
                    $post_content = str_replace($url, $url_new, $post_content);
                }
            }
        }
        $args = array(
            'ID'           => $post_id,
            'post_content' => $post_content,
        );
        wp_update_post($args);
    }

    public function create_img($url, $file_name, $convert = false, $flip = false, $crop_w = '', $crop_h = '', $set_quality = 90) {
        $allowed_extensions = array('jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff');
        $allowed_extensions = apply_filters('image_sideload_extensions', $allowed_extensions, $url);
        $allowed_extensions = array_map('preg_quote', $allowed_extensions);

        preg_match('/[^\?]+\.(' . implode('|', $allowed_extensions ) . ')\b/i', $url, $matches);

        if(!$matches){
            return false;
        }

        if (!function_exists('download_url')){
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $tmp = download_url($url);
        
        if (is_wp_error($tmp)) {
            $modified_url = str_replace(['https://', 'http://'], 'https://i0.wp.com/', $url);
            $tmp = download_url($modified_url);
            if (is_wp_error($tmp)) {
                wp_delete_file($tmp);
                return false;
            }
        }

        $ext = $matches[1];
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['path'] . '/' . $file_name . '.' . $ext;
        for($i = 1; file_exists($path); $i++){
            $path = $upload_dir['path'] . '/' . $file_name . '-' . $i . '.' . $ext;
        }

        copy($tmp, $path);
        wp_delete_file($tmp);

        if ($convert || $flip || $crop_w != '' || $crop_h != '') {
            $img = false;
            if ($ext == 'png') {
                $img = imagecreatefrompng($path);
                imageAlphaBlending($img, true);
                imageSaveAlpha($img, true);
            }elseif ($ext == 'jpg' || $ext == 'jpeg') {
                $img = imagecreatefromjpeg($path);
            }

            if ($img) {
                $image_w = imagesx($img);
                $image_h = imagesy($img);
                $thumb_w = $image_w;
                $thumb_h = $image_h;
                
                if (wp_extra_get_option('autocrop') && isset($crop_w) && isset($crop_h) && $crop_w >= 100 && $crop_h >= 100) {
                    $thumb_w = $crop_w;
                    $thumb_h = $crop_h;
                }

                $thumb = imagecreatetruecolor($thumb_w, $thumb_h);
                imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
                if ($flip) {
                    imagecopyresampled($thumb, $img, 0, 0, $image_w - 1, 0, $thumb_w, $thumb_h, -$image_w, $image_h);
                }else{
                    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumb_w, $thumb_h, $image_w, $image_h);
                }

                if ($convert) {
                    $ext_new = 'jpg';
                    $path = $upload_dir['path'] . '/' . $file_name . '.jpg';
                    for($i = 1; file_exists($path); $i++){
                        $path = $upload_dir['path'] . '/' . $file_name . '-' . $i . '.jpg';
                    }
                    wp_delete_file($upload_dir['path'] . '/' . $file_name . '.' . $ext);
                }else{
                    $ext_new = $ext;
                }

                if ($ext_new == 'png') {
                    imagepng($thumb, $path);
                }else{
                    imagejpeg($thumb, $path, $set_quality);
                }
                imagedestroy($thumb);
            }
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
    
    public function set_media_default( $null, $object_id, $meta_key, $single ) {
        if ( is_single($object_id)) {
            return null;
        }
        if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
            return $null;
        }
        if ( empty( $meta_key ) || $meta_key !== '_thumbnail_id' ) {
            return $null;
        }
        if ( ! post_type_supports( get_post_type( $object_id ), 'thumbnail' ) ) {
            return $null;
        }
        $meta_cache = wp_cache_get( $object_id, 'post_meta' );
        if ( ! $meta_cache ) {
            $meta_cache = update_meta_cache( 'post', array( $object_id ) );
            $meta_cache = $meta_cache[ $object_id ] ?? array();
        }
        if ( ! empty( $meta_cache['_thumbnail_id'][0] ) ) {
            return $null;
        }
        $meta_cache['_thumbnail_id'][0] = wp_extra_get_option('media_default');
        wp_cache_set( $object_id, $meta_cache, 'post_meta' );
        return $null;
    }

    public function remove_image_sizes( $sizes ) {
		$list_thumbnails = get_intermediate_image_sizes();
		$disablethumbnails = wp_extra_get_option('media_thumbnails');
		foreach ( $list_thumbnails as $value ) {
			if ( in_array( $value, $disablethumbnails ) ) {
				unset( $sizes[ $value ] );
			}
		}
		return $sizes;

	}

    public function resize_image($image_data) {
        $max_width = intval(wp_extra_get_option('image_max_width', 1000));
        $max_height = intval(wp_extra_get_option('image_max_height', 1000));
        $compression_level = intval(wp_extra_get_option('image_quality'));

        if ($image_data['type'] === 'image/png') {
            $image_data = $this->convert_image($image_data);
        }

        if ($image_data['type'] === 'image/gif' && $this->is_animated_gif($image_data['file'])) {
            return $image_data;
        }

        $image_editor = wp_get_image_editor($image_data['file']);

        if (!is_wp_error($image_editor)) {
            $sizes = $image_editor->get_size();

            if (
                (isset($sizes['width']) && $sizes['width'] > $max_width) ||
                (isset($sizes['height']) && $sizes['height'] > $max_height)
            ) {
                $image_editor->resize($max_width, $max_height, false);
                $image_editor->set_quality($compression_level);
                $image_editor->save($image_data['file']);
            }
        }

        return $image_data;
    }

    private function convert_image($params) {
        $img = imagecreatefrompng($params['file']);
        $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, 1);
        imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));

        $newPath = preg_replace("/\.png$/", ".jpg", $params['file']);
        $newUrl = preg_replace("/\.png$/", ".jpg", $params['url']);

        for ($i = 1; file_exists($newPath); $i++) {
            $newPath = preg_replace("/\.png$/", "-" . $i . ".jpg", $params['file']);
        }

        if (imagejpeg($bg, $newPath, $params['compression_level'])) {
            wp_delete_file($params['file']);
            $params['file'] = $newPath;
            $params['url'] = $newUrl;
            $params['type'] = 'image/jpeg';
        }

        return $params;
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
        $limit = intval(wp_extra_get_option('image_limit'));
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

    public function update_image_metadata($attachment_ID) {
        if (wp_extra_get_option('meta_images')) {
            if (!current_user_can('edit_post', $attachment_ID)) {
                return;
            }
            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], home_url()) !== 0) {
                return;
            }
            if (!empty($_REQUEST['post_id']) && !wp_extra_get_option('meta_images_filename')) {
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
    
}
