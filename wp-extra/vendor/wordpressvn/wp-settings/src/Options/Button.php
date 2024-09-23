<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Enqueuer;

class Button extends OptionAbstract
{
    public $view = 'button';

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }

    public function enqueue()
    {
        Enqueuer::add('wps-button', function () {
            $id = str_replace('-', '_', $this->get_id_attribute());
            $stop = "stop_".$id;
            $result = "result_".$id;
            wp_enqueue_script('wp-settings');
            wp_localize_script('jquery', $id, [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce($id.'_nonce')
            ]);
            wp_add_inline_script('wp-settings', "jQuery(document).ready(function($) {
                var isRunning = false;
                $('#{$stop}').prop('disabled', true);
                $('#{$stop}').on('click', function() {
                    isRunning = !isRunning;
                    $(this).prop('disabled', !isRunning);
                });
                $('#{$id}').on('click', function(event) {
                    event.preventDefault();
                    var input = $(this);
                    input.next('.spinner').addClass('is-active');
                    if (isRunning) {
                        return;
                    }
                    isRunning = true;
                    var nonce = {$id}.nonce;
                    $.ajax({
                        url: {$id}.ajaxurl,
                        type: 'post',
                        dataType: 'json',
                        data: {
                            action: '{$id}',
                            nonce: nonce,
                        },
                        success: function(response) {
                            input.next('.spinner').removeClass('is-active');
                            var messages = response.message.split('<br>');
                            displayMessages(messages, 0, input);
                            setTimeout(function() {
                                $('#{$result}').append(response.success_message);
                            }, messages.length * 1000 + 1000);
                        },
                        error: function(response) {
                            input.next('.spinner').removeClass('is-active');
                            alert(response.responseJSON.data);
                        }
                    });
                });
                function displayMessages(messages, index, input) {
                    if (index < messages.length && isRunning) {
                        $('#{$result}').append(messages[index]);
                        
                    $('#{$stop}').prop('disabled', false);
                    
                        setTimeout(function() {
                            displayMessages(messages, index + 1, input);
                        }, 1000);
                    }
                }
            });");
        });
    }
}
