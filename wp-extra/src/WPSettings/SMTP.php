<?php

namespace WPEXtra\WPSettings;

use WPVNTeam\WPSettings\Options\OptionAbstract;

class SMTP extends OptionAbstract
{
    public $view = 'smtp';
    
    public function render()
    {
        
       if(isset($_POST['smtp_mailer_send_test_email'])){
            $to = isset($_POST['smtp_mailer_to_email']) ? sanitize_email($_POST['smtp_mailer_to_email']) : '';
            $subject = "This is the test mail from ".get_bloginfo('name');
            $message = "<p>Dear Admin,</p>
<p>I would like to express my sincere gratitude for using the WP Extra plugin on your WordPress website. We hope that the plugin has been beneficial in enhancing your website's functionality and user experience.</p>
<p>We are committed to providing high-quality products, and we appreciate your trust in choosing WP Extra for your website needs. If you have any feedback or suggestions for improvement, please feel free to reach out to us. We value your input and strive to continuously enhance our products to meet your expectations.</p>
<p>Once again, thank you for choosing WP Extra. We look forward to serving you and contributing to the success of your online presence.</p>
<p>Best regards,</p>
<p>TienCOP</p>";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $sent = wp_mail($to, $subject, $message, $headers);
            if($sent){
                add_settings_error(
                    'smtp_mailer',
                    'smtp_mailer_success',
                    __( 'Check your email' ),
                    'notice-success is-dismissible'
                );
            } else {
                add_settings_error(
                    'smtp_mailer',
                    'smtp_mailer_error',
                    __( 'Invalid' ),
                    'notice-error is-dismissible'
                );
            }
        }
        settings_errors('smtp_mailer');
        ?>
        <tr valign="top">
            <th scope="row">
                <?php echo $this->get_label(); ?>
            </th>
            <td class="forminp forminp-text">
                <input name="smtp_mailer_to_email" type="text" value="" class="regular-text">
                <button type="submit" name="smtp_mailer_send_test_email" class="button wp-hide-pw">
                    <span class="dashicons dashicons-email-alt tooltip" title="<?php _e('Send Request'); ?>"></span>
                </button>
                <?php if ($description = $this->get_arg('description')) { ?>
                    <p class="description"><?php echo $description; ?></p>
                <?php } ?>
            </td>
        </tr>
        <?php
    }
}
