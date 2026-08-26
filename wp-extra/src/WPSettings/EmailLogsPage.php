<?php
namespace WPEXtra\WPSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Helper;

class EmailLogsPage
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_submenu'], 99);
        add_action('wp_ajax_wpex_view_email_log', [$this, 'ajax_view_log']);
        add_action('wp_ajax_wpex_resend_email_log', [$this, 'ajax_resend_log']);
        add_action('wp_ajax_wpex_delete_email_log', [$this, 'ajax_delete_log']);
        add_action('wp_ajax_wpex_clear_email_logs', [$this, 'ajax_clear_logs']);
        add_action('admin_init', [$this, 'handle_bulk_actions']);
    }

    public static function is_enabled()
    {
        $modules = (array) Helper::get_option('modules', []);
        if (empty($modules) || !in_array('smtp', $modules, true)) {
            return false;
        }

        return (bool) Helper::get_option('email_log', false);
    }

    public function register_submenu()
    {
        if (!self::is_enabled()) {
            return;
        }

        add_submenu_page(
            'wp-extra',
            __('Email Logs', 'wp-extra'),
            __('Email Logs', 'wp-extra'),
            'manage_options',
            'wpex-email-logs',
            [$this, 'render_page']
        );
    }

    public function handle_bulk_actions()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpex-email-logs') {
            return;
        }

        if (!current_user_can('manage_options') || !self::is_enabled()) {
            return;
        }

        $action = isset($_POST['action']) ? $_POST['action'] : (isset($_POST['action2']) ? $_POST['action2'] : '');
        if ($action === 'bulk_delete' && !empty($_POST['log_ids'])) {
            check_admin_referer('wpex_bulk_logs_action');
            $ids = array_map('intval', (array)$_POST['log_ids']);
            if (!empty($ids)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'wpex_email_logs';
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $ids));
                wp_safe_redirect(add_query_arg(['deleted' => count($ids)], remove_query_arg(['action', 'action2', '_wpnonce'])));
                exit;
            }
        }
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        if (!self::is_enabled()) {
            wp_die(__('Email logging is currently disabled or SMTP module is not active.', 'wp-extra'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpex_email_logs';

        // Check table
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            ?>
            <div class="wrap">
                <h1><?php _e('Email Delivery Logs', 'wp-extra'); ?></h1>
                <div class="notice notice-info">
                    <p><?php _e('Email log table is not yet initialized. Enable Email Logging in WP Extra Settings > SMTP to start tracking emails.', 'wp-extra'); ?></p>
                </div>
            </div>
            <?php
            return;
        }

        $paged    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset   = ($paged - 1) * $per_page;

        $search   = isset($_GET['s']) ? sanitize_text_field(trim($_GET['s'])) : '';
        $status   = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (to_email LIKE %s OR subject LIKE %s OR mailer_account LIKE %s OR error_message LIKE %s)";
            $wildcard = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if (!empty($status) && in_array($status, ['success', 'failed'], true)) {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        // Count totals
        $count_all = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $count_success = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'");
        $count_failed = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'");

        $count_sql = "SELECT COUNT(*) FROM $table_name $where";
        $total_items = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_sql, $params)) : $wpdb->get_var($count_sql);
        $total_pages = ceil($total_items / $per_page);

        $query_sql = "SELECT id, to_email, subject, status, mailer_account, error_message, created_at FROM $table_name $where ORDER BY id DESC LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$per_page, $offset]);
        $logs = $wpdb->get_results($wpdb->prepare($query_sql, $query_params));
        ?>
        <div class="wrap wpex-email-logs-page">
            <h1 class="wp-heading-inline">
                <?php _e('Email Delivery Logs', 'wp-extra'); ?>
            </h1>
            
            <?php if ($count_all > 0) { ?>
                <button type="button" class="page-title-action wpex-clear-all-logs" style="color: #b32d2e;">
                    <span class="dashicons dashicons-trash" style="vertical-align: -2px; font-size: 16px;"></span>
                    <?php _e('Clear All Logs', 'wp-extra'); ?>
                </button>
            <?php } ?>

            <hr class="wp-header-end">

            <?php if (isset($_GET['deleted'])) { ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php printf(_n('%s log record deleted.', '%s log records deleted.', intval($_GET['deleted']), 'wp-extra'), number_format_i18n(intval($_GET['deleted']))); ?></p>
                </div>
            <?php } ?>

            <!-- Status Views Filter -->
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo esc_url(remove_query_arg(['status', 'paged'])); ?>" class="<?php echo empty($status) ? 'current' : ''; ?>">
                        <?php _e('All', 'wp-extra'); ?> <span class="count">(<?php echo number_format_i18n($count_all); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg(['status' => 'success', 'paged' => 1])); ?>" class="<?php echo $status === 'success' ? 'current' : ''; ?>">
                        <span style="color: #00a32a;">●</span> <?php _e('Sent', 'wp-extra'); ?> <span class="count">(<?php echo number_format_i18n($count_success); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg(['status' => 'failed', 'paged' => 1])); ?>" class="<?php echo $status === 'failed' ? 'current' : ''; ?>">
                        <span style="color: #d63638;">●</span> <?php _e('Failed', 'wp-extra'); ?> <span class="count">(<?php echo number_format_i18n($count_failed); ?>)</span>
                    </a>
                </li>
            </ul>

            <!-- Search Box -->
            <form method="get" style="float: right; margin-bottom: 10px;">
                <input type="hidden" name="page" value="wpex-email-logs">
                <?php if (!empty($status)) { ?>
                    <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
                <?php } ?>
                <p class="search-box">
                    <label class="screen-reader-text" for="wpex-search-input"><?php _e('Search Logs:', 'wp-extra'); ?></label>
                    <input type="search" id="wpex-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Recipient, subject, error...', 'wp-extra'); ?>">
                    <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Logs', 'wp-extra'); ?>">
                </p>
            </form>

            <form method="post">
                <?php wp_nonce_field('wpex_bulk_logs_action'); ?>
                
                <!-- Table Navigation Top -->
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action'); ?></label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk actions'); ?></option>
                            <option value="bulk_delete"><?php _e('Delete'); ?></option>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="<?php _e('Apply'); ?>">
                    </div>

                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_items, 'wp-extra'), number_format_i18n($total_items)); ?></span>
                        <?php if ($total_pages > 1) { 
                            echo paginate_links([
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $paged
                            ]);
                        } ?>
                    </div>
                </div>

                <!-- Table -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All'); ?></label>
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column" style="width: 140px;"><?php _e('Date / Time', 'wp-extra'); ?></th>
                            <th scope="col" class="manage-column" style="width: 220px;"><?php _e('Recipient', 'wp-extra'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Subject', 'wp-extra'); ?></th>
                            <th scope="col" class="manage-column" style="width: 130px;"><?php _e('Mailer Account', 'wp-extra'); ?></th>
                            <th scope="col" class="manage-column" style="width: 100px; text-align: center;"><?php _e('Status', 'wp-extra'); ?></th>
                            <th scope="col" class="manage-column" style="width: 120px; text-align: right;"><?php _e('Actions', 'wp-extra'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                        <?php if (empty($logs)) { ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="7" style="text-align: center; padding: 30px;">
                                    <span class="dashicons dashicons-email-alt" style="font-size: 36px; width: 36px; height: 36px; opacity: 0.3;"></span>
                                    <p style="margin: 8px 0 0; color: #646970;"><?php _e('No email log entries found.', 'wp-extra'); ?></p>
                                </td>
                            </tr>
                        <?php } else {
                            foreach ($logs as $log) {
                                $is_success = ($log->status === 'success');
                                ?>
                                <tr id="log-<?php echo esc_attr($log->id); ?>">
                                    <th scope="row" class="check-column">
                                        <input id="cb-select-<?php echo esc_attr($log->id); ?>" type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                                    </th>
                                    <td class="column-date" style="font-size: 12px; color: #50575e;">
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' H:i:s', strtotime($log->created_at))); ?>
                                    </td>
                                    <td class="column-recipient">
                                        <strong style="color: #1d2327; font-size: 13px;"><?php echo esc_html($log->to_email); ?></strong>
                                    </td>
                                    <td class="column-subject">
                                        <span style="font-weight: 500; font-size: 13px;"><?php echo esc_html($log->subject ?: __('(No Subject)', 'wp-extra')); ?></span>
                                        <?php if (!$is_success && !empty($log->error_message)) { ?>
                                            <div style="font-size: 11px; color: #d63638; margin-top: 3px;" title="<?php echo esc_attr($log->error_message); ?>">
                                                ⚠️ <?php echo esc_html(wp_trim_words($log->error_message, 12)); ?>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="column-mailer" style="font-size: 12px;">
                                        <span style="background: #f0f0f1; padding: 2px 6px; border-radius: 4px; font-family: monospace;">
                                            <?php echo esc_html($log->mailer_account ?: 'Default'); ?>
                                        </span>
                                    </td>
                                    <td class="column-status" style="text-align: center;">
                                        <?php if ($is_success) { ?>
                                            <span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #007017; background: #e7f5ea; border-radius: 12px;">
                                                ✓ <?php _e('Sent', 'wp-extra'); ?>
                                            </span>
                                        <?php } else { ?>
                                            <span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #b32d2e; background: #fcf0f1; border-radius: 12px;">
                                                ✗ <?php _e('Failed', 'wp-extra'); ?>
                                            </span>
                                        <?php } ?>
                                    </td>
                                    <td class="column-actions" style="text-align: right; white-space: nowrap;">
                                        <button type="button" class="button button-small wpex-view-log-btn" data-id="<?php echo esc_attr($log->id); ?>" title="<?php esc_attr_e('View email preview', 'wp-extra'); ?>">
                                            <span class="dashicons dashicons-visibility" style="font-size: 14px; line-height: 20px;"></span>
                                        </button>
                                        <button type="button" class="button button-small wpex-resend-log-btn" data-id="<?php echo esc_attr($log->id); ?>" title="<?php esc_attr_e('Resend email', 'wp-extra'); ?>">
                                            <span class="dashicons dashicons-controls-repeat" style="font-size: 14px; line-height: 20px;"></span>
                                        </button>
                                        <button type="button" class="button button-small button-link-delete wpex-delete-log-btn" data-id="<?php echo esc_attr($log->id); ?>" title="<?php esc_attr_e('Delete log', 'wp-extra'); ?>">
                                            <span class="dashicons dashicons-trash" style="font-size: 14px; line-height: 20px;"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php }
                        } ?>
                    </tbody>
                </table>

                <!-- Table Navigation Bottom -->
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action'); ?></label>
                        <select name="action2" id="bulk-action-selector-bottom">
                            <option value="-1"><?php _e('Bulk actions'); ?></option>
                            <option value="bulk_delete"><?php _e('Delete'); ?></option>
                        </select>
                        <input type="submit" id="doaction2" class="button action" value="<?php _e('Apply'); ?>">
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_items, 'wp-extra'), number_format_i18n($total_items)); ?></span>
                        <?php if ($total_pages > 1) { 
                            echo paginate_links([
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $paged
                            ]);
                        } ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Popup -->
        <div id="wpex-email-log-modal" class="wpex-modal-overlay">
            <div class="wpex-modal-dialog">
                <div class="wpex-modal-header">
                    <h3 id="wpex-modal-title">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php esc_html_e('Email Content Preview', 'wp-extra'); ?>
                    </h3>
                    <button type="button" id="wpex-modal-close" class="wpex-modal-close-btn">&times;</button>
                </div>
                <div id="wpex-modal-meta" class="wpex-modal-meta">
                </div>
                <div id="wpex-modal-body" class="wpex-modal-body">
                </div>
                <div class="wpex-modal-footer">
                    <span id="wpex-modal-status-badge"></span>
                    <button type="button" class="button button-primary" id="wpex-modal-resend-btn">
                        <span class="dashicons dashicons-controls-repeat" style="vertical-align: middle; font-size: 15px;"></span>
                        <?php esc_html_e('Resend Email', 'wp-extra'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_view_log()
    {
        check_ajax_referer('wpex_email_log_action', '_nonce');
        if (!current_user_can('manage_options') || !self::is_enabled()) {
            wp_send_json_error(__('Permission denied.', 'wp-extra'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpex_email_logs';
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$log) {
            wp_send_json_error(__('Log record not found.', 'wp-extra'));
        }

        wp_send_json_success($log);
    }

    public function ajax_resend_log()
    {
        check_ajax_referer('wpex_email_log_action', '_nonce');
        if (!current_user_can('manage_options') || !self::is_enabled()) {
            wp_send_json_error(__('Permission denied.', 'wp-extra'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpex_email_logs';
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$log) {
            wp_send_json_error(__('Log record not found.', 'wp-extra'));
        }

        $headers = !empty($log->headers) ? maybe_unserialize($log->headers) : ['Content-Type: text/html; charset=UTF-8'];
        $sent = wp_mail($log->to_email, $log->subject, $log->message, $headers);

        if ($sent) {
            wp_send_json_success(sprintf(__('Email resent successfully to %s!', 'wp-extra'), $log->to_email));
        } else {
            wp_send_json_error(__('Failed to resend email. Please check SMTP settings.', 'wp-extra'));
        }
    }

    public function ajax_delete_log()
    {
        check_ajax_referer('wpex_email_log_action', '_nonce');
        if (!current_user_can('manage_options') || !self::is_enabled()) {
            wp_send_json_error(__('Permission denied.', 'wp-extra'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpex_email_logs';
        $deleted = $wpdb->delete($table_name, ['id' => $id], ['%d']);

        if ($deleted) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Failed to delete record.', 'wp-extra'));
        }
    }

    public function ajax_clear_logs()
    {
        check_ajax_referer('wpex_email_log_action', '_nonce');
        if (!current_user_can('manage_options') || !self::is_enabled()) {
            wp_send_json_error(__('Permission denied.', 'wp-extra'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpex_email_logs';
        $wpdb->query("TRUNCATE TABLE $table_name");

        wp_send_json_success(__('All email logs have been cleared.', 'wp-extra'));
    }
}
