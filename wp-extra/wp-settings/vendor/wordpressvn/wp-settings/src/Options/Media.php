<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Enqueuer;

class Media extends OptionAbstract
{
    public $view = 'media';

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }

    public function sanitize($value)
    {
        $value = parent::sanitize($value);
        if (empty($value)) {
            return '';
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $site_url = site_url();
            $home_url = home_url();

            // Try to resolve to Attachment ID if it is a local upload
            if (function_exists('attachment_url_to_postid')) {
                $att_id = attachment_url_to_postid($value);
                if ($att_id) {
                    return (int) $att_id;
                }
            }

            // Convert local domain absolute URL to relative path to prevent migration breakage
            if (strpos($value, $site_url) === 0) {
                return substr($value, strlen($site_url));
            }
            if (strpos($value, $home_url) === 0) {
                return substr($value, strlen($home_url));
            }
        }

        return $value;
    }

    public function get_preview_url()
    {
        $value = $this->get_value_attribute();

        if (empty($value)) {
            return '';
        }

        if (is_numeric($value)) {
            $src = wp_get_attachment_image_src((int) $value, 'thumbnail');
            if (!empty($src[0])) {
                return $src[0];
            }
            $url = wp_get_attachment_url((int) $value);
            if ($url) {
                return $url;
            }
        }

        if (is_string($value)) {
            if (strpos($value, '/') === 0) {
                return home_url($value);
            }
            return $value;
        }

        return '/wp-includes/images/media/document.png';
    }

    public function get_media_library_config()
    {
        return wp_parse_args($this->get_arg('media_library', []), [
            'title' => __('Add Media'),
            'button' => [
                'text' => __('Add Media'),
            ],
            'multiple' => false,
        ]);
    }

    public function enqueue()
    {
        Enqueuer::add('wps-media', function () {
            wp_enqueue_media();
            ?>
            <style>
                .wps-media-wrapper > div {
                    display: flex;
                    gap: 15px;
                    align-items: start;
                }

                .wps-media-wrapper .wps-media-preview {
                    outline: 1px solid #0000001a;
                    width: 80px;
                    border-radius: 4px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 10px;
                    display: none;
                }

                .wps-media-wrapper .wps-media-preview img {
                    max-width: 100%;
                }
            </style>
            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    document.querySelectorAll('.wps-media-wrapper').forEach((el) => {
                        let trigger = el.querySelector('.wps-media-open');
                        let clear = el.querySelector('.wps-media-clear');
                        let target = el.querySelector('.wps-media-target');
                        let preview = el.querySelector('.wps-media-preview');
                        let media_library_config = JSON.parse(el.getAttribute('data-media-library'));

                        let media_library = wp.media(media_library_config);

                        clear.addEventListener('click', function (e) {
                            e.preventDefault();

                            target.value = '';
                            preview.innerHTML = '';
                            clear.style.display = 'none';
                            preview.style.display = 'none';
                        });

                        trigger.addEventListener('click', function (e) {
                            e.preventDefault();

                            media_library.open();
                        });

                        media_library.on('open', function() {
                            if(target.value === '') {
                                return;
                            }

                            let selection = media_library.state().get('selection');
                            let attachment = wp.media.attachment(target.value);

                            selection.add(attachment ? [attachment] : []);
                        });

                        media_library.on('select', function () {
                            let attachment = media_library.state().get('selection').first().toJSON();

                            target.value = attachment.id;

                            if (attachment.type === 'image') {
                                preview.innerHTML = '<img src="' + attachment.url + '">'; //attachment.sizes.thumbnail.url
                            } else {
                                preview.innerHTML = '<img src="' + attachment.icon + '">';
                            }

                            preview.style.display = 'flex';
                            clear.style.display = 'inline-block';
                        });
                    });
                });
            </script>
            <?php
        });
    }
}
