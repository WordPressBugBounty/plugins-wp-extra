<?php

namespace WPVNTeam\WPSettings\Options;

class Image extends Media
{
    public function get_media_library_config()
    {
        return wp_parse_args($this->get_arg('media_library', []), [
            'title' => __('Select Image'),
            'button' => [
                'text' => __('Select Image'),
            ],
            'library' => [
                'type' => 'image',
            ],
            'multiple' => false,
        ]);
    }
}
