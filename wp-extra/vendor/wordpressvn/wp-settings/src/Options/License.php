<?php
namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Updater;

class License extends OptionAbstract
{
    public $view = 'license';

    public function __construct($section, $args = [])
    {
        add_action('admin_init', [$this, 'pluginUpdater'], 0);
        parent::__construct($section, $args);
    }

    public function pluginUpdater() {
        $doing_cron = defined('DOING_CRON') && DOING_CRON;
        if (!current_user_can('manage_options') && !$doing_cron) {
            return;
        }
        $license = $this->get_value_attribute() ? trim($this->get_value_attribute()) : '';
        $edd_updater = new Updater($this->get_arg('store_url'), $this->get_arg('file'), array(
            'version'  => $this->get_arg('version'),
            'license'  => $license,
            'item_id'  => $this->get_arg('item_id'),
            'author'   => $this->get_arg('author') ?: 'TienCOP'
        ));
    }
    
    public function activate() {
        $option_name = $this->section->tab->settings->option_name;
        $license = $this->get_value_attribute() ? trim($this->get_value_attribute()) : '';
        if (empty($license)) {
            return false;
        }
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_name'  => urlencode($this->get_arg('item_name')),
            'url'        => home_url()
        );
        $response = wp_remote_post($this->get_arg('store_url'), array(
            'timeout'   => 15,
            'sslverify' => false,
            'body'      => $api_params
        ));
        if (is_wp_error($response)) {
            return false;
        }
        $license_data = json_decode(wp_remote_retrieve_body($response));
        $lic = get_option($option_name, []);
        $lic['license_status'] = $license_data->license;
        $lic['license_expires'] = $license_data->expires;
        update_option($option_name, $lic);
        $item_name = $this->get_arg('item_name');
        $slug = strtolower(str_replace(' ', '-', $item_name));
        wp_redirect(admin_url('admin.php?page=' . $slug . '&tab=license'));
		exit();
    }
    
    public function deactivate() {
        $option_name = $this->section->tab->settings->option_name;
        $lic = get_option($option_name, []);
        $license = $lic['license_key'] ? trim($lic['license_key']) : '';
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $license,
            'item_name'  => urlencode($this->get_arg('item_name')),
            'url'        => home_url()
        );
        $response = wp_remote_post($this->get_arg('store_url'), array(
            'timeout'   => 15,
            'sslverify' => false,
            'body'      => $api_params
        ));
        if (is_wp_error($response)) { 
            return false;
        }
        $license_data = json_decode(wp_remote_retrieve_body($response));
        $lic['license_status'] = $license_data->license;
        $lic['license_expires'] = $license_data->expires;
        update_option($option_name, $lic);
        $item_name = $this->get_arg('item_name');
        $slug = strtolower(str_replace(' ', '-', $item_name));
        wp_redirect(admin_url('admin.php?page=' . $slug . '&tab=license'));
		exit();
    }
}