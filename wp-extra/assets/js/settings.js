/**
 * WP EXtra - Admin Settings JavaScript (WP Extra Specific)
 *
 * @package WPEXtra
 */

/* global jQuery, ajaxurl, wpex_settings */
(function ($) {
    'use strict';

    $(function () {
        var config = window.wpex_settings || {};
        var i18n = config.i18n || {};
        var nonces = config.nonces || {};

        /* ==========================================================================
           1. Protect .htaccess Toggle
           ========================================================================== */
        $(document).on('click', '.wpex-protect-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $wrap = $btn.closest('.wpex-protect-wrap');
            var target = $btn.data('target');
            var action = $btn.data('action');

            if (action === 'remove' && !window.confirm(i18n.confirm_remove_htaccess || 'Are you sure you want to remove the .htaccess protection file?')) {
                return;
            }

            var originalText = $btn.html();
            $btn.prop('disabled', true).text(i18n.processing || 'Processing...');

            $.post(ajaxurl, {
                action: 'wpex_toggle_htaccess_protect',
                target: target,
                protect_action: action,
                _nonce: nonces.htaccess_protect || ''
            }, function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    if (res.data.is_active) {
                        $wrap.find('.wpex-protect-badge')
                            .removeClass('is-inactive').addClass('is-active')
                            .html('<span class="dashicons dashicons-yes-alt"></span> ' + (i18n.protected || 'Protected'));

                        $btn.removeClass('button-primary').addClass('is-danger')
                            .data('action', 'remove')
                            .html('<span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;line-height:14px;"></span> ' + (i18n.remove_protection || 'Remove Protection'));
                    } else {
                        $wrap.find('.wpex-protect-badge')
                            .removeClass('is-active').addClass('is-inactive')
                            .html('<span class="dashicons dashicons-shield"></span> ' + (i18n.not_protected || 'Not Protected'));

                        $btn.removeClass('is-danger').addClass('button-primary')
                            .data('action', 'create')
                            .html('<span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;line-height:14px;"></span> ' + (i18n.create_protection || 'Create Protection File'));
                    }
                    window.alert(res.data.message);
                } else {
                    $btn.html(originalText);
                    window.alert(res.data.message || (i18n.error_occurred || 'An error occurred.'));
                }
            }).fail(function () {
                $btn.prop('disabled', false).html(originalText);
                window.alert(i18n.request_failed || 'Request failed.');
            });
        });

        /* ==========================================================================
           2. SMTP Send Test Email
           ========================================================================== */
        $('#wpex_btn_send_smtp_test').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $btnText = $btn.find('.wpex-btn-text');
            var $result = $('#wpex_smtp_test_result');
            var recipient = $('#wpex_test_email_recipient').val().trim();

            if (!recipient) {
                $result.html('<div style="color: #b32d2e; font-weight: 600;">⚠️ ' + (i18n.enter_recipient_email || 'Please enter a recipient email address.') + '</div>')
                    .css({ background: '#fcf0f1', border: '1px solid #f0b8bd', display: 'block' });
                return;
            }

            $btn.prop('disabled', true).addClass('updating-message');
            $btnText.text(i18n.connecting_sending || 'Connecting & Sending...');
            $result.hide().empty();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpex_smtp_send_test_ajax',
                    nonce: nonces.smtp_test || '',
                    to_email: recipient
                },
                success: function (res) {
                    $btn.prop('disabled', false).removeClass('updating-message');
                    $btnText.text(i18n.send_test_email || 'Send Test Email');

                    if (res.success) {
                        var html = '<div style="display: flex; align-items: flex-start; gap: 8px;">';
                        html += '<span class="dashicons dashicons-yes-alt" style="color: #008a20; font-size: 20px; margin-top: -2px;"></span>';
                        html += '<div>';
                        html += '<strong style="color: #008a20; font-size: 13.5px;">' + (i18n.delivery_successful || 'Delivery Successful!') + '</strong>';
                        html += '<div style="margin-top: 4px; color: #2c3338;">' + res.data.message + '</div>';
                        html += '</div></div>';

                        $result.html(html).css({
                            background: '#edfaef',
                            border: '1px solid #a6e9ab',
                            display: 'block'
                        });
                    } else {
                        var errHtml = '<div style="display: flex; align-items: flex-start; gap: 8px;">';
                        errHtml += '<span class="dashicons dashicons-dismiss" style="color: #d63638; font-size: 20px; margin-top: -2px;"></span>';
                        errHtml += '<div style="flex: 1;">';
                        errHtml += '<strong style="color: #d63638; font-size: 13.5px;">' + (i18n.delivery_failed || 'Delivery Failed') + '</strong>';
                        errHtml += '<div style="margin-top: 4px; color: #2c3338;">' + res.data.message + '</div>';
                        if (res.data.hint) {
                            errHtml += '<div style="margin-top: 8px; padding: 6px 10px; background: #ffffff; border-radius: 3px; border-left: 3px solid #d63638; font-size: 12.5px; color: #50575e;">' + res.data.hint + '</div>';
                        }
                        errHtml += '</div></div>';

                        $result.html(errHtml).css({
                            background: '#fcf0f1',
                            border: '1px solid #f0b8bd',
                            display: 'block'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    $btn.prop('disabled', false).removeClass('updating-message');
                    $btnText.text(i18n.send_test_email || 'Send Test Email');
                    $result.html('<div style="color: #b32d2e;"><strong>' + (i18n.server_error || 'Server Error') + ':</strong> ' + error + '</div>')
                        .css({ background: '#fcf0f1', border: '1px solid #f0b8bd', display: 'block' });
                }
            });
        });

        /* ==========================================================================
           3. SMTP Providers Presets & Repeater Cards
           ========================================================================== */
        var providers = config.smtp_providers || {};
        var usageData = config.smtp_usage || {};

        function updateSmtpCard($card, idx) {
            var $provSelect = $card.find('select[name*="[provider]"]');
            if (!$provSelect.length) {
                return;
            }
            var val = $provSelect.val() || 'gmail';
            var p = providers[val] || providers.other || {};

            var $hostItem = $card.find('input[name*="[host]"]').closest('.wps-repeater-field-item');
            var $portItem = $card.find('input[name*="[port]"]').closest('.wps-repeater-field-item');
            var $encItem = $card.find('select[name*="[encryption]"]').closest('.wps-repeater-field-item');

            var $hint = $card.find('.wpex-smtp-provider-hint');
            if (!$hint.length) {
                $hint = $('<div class="wpex-smtp-provider-hint wps-repeater-field-item wps-field-full"></div>');
                $provSelect.closest('.wps-repeater-field-item').after($hint);
            }

            if (val === 'other') {
                $hostItem.show();
                $portItem.show();
                $encItem.show();
                $hint.hide();
            } else {
                $hostItem.hide();
                $portItem.hide();
                $encItem.hide();

                if (p.host) {
                    $card.find('input[name*="[host]"]').val(p.host);
                }
                if (p.port) {
                    $card.find('input[name*="[port]"]').val(p.port);
                }
                if (p.encryption) {
                    $card.find('select[name*="[encryption]"]').val(p.encryption);
                }

                if (p.hint) {
                    $hint.html('💡 <strong>' + p.name + ' Preset:</strong> ' + p.hint + '<br><small style="color: #646970;">Server: <code>' + p.host + '</code> | Port: <code>' + p.port + '</code> | Security: <code>' + (p.encryption || '').toUpperCase() + '</code></small>').show();
                } else {
                    $hint.hide();
                }
            }

            // Render live quota usage badge in card header
            if (typeof idx === 'number' && usageData[idx]) {
                var u = usageData[idx];
                var $badge = $card.find('.wpex-quota-badge');
                if (!$badge.length) {
                    $badge = $('<span class="wpex-quota-badge"></span>');
                    $card.find('.wps-repeater-card-title').append($badge);
                }
                if (u.limit > 0) {
                    if (u.sent >= u.limit) {
                        $badge.html('<span style="background: #fcf0f1; color: #d63638; border: 1px solid #f0b8bd; padding: 2px 8px; border-radius: 12px; font-weight: 600;">⚠️ Daily Limit Reached (' + u.sent + '/' + u.limit + ')</span>');
                    } else {
                        $badge.html('<span style="background: #edfaef; color: #008a20; border: 1px solid #c3e6cb; padding: 2px 8px; border-radius: 12px;">' + u.sent + '/' + u.limit + ' sent today (' + u.percent + '%)</span>');
                    }
                } else {
                    $badge.html('<span style="background: #f0f0f1; color: #50575e; padding: 2px 8px; border-radius: 12px;">' + u.sent + ' sent today (Unlimited)</span>');
                }
            }
        }

        $(document).on('change', '.wps-repeater-container[data-name="wp_extra[smtp_accounts]"] select[name*="[provider]"]', function () {
            var $card = $(this).closest('.wps-repeater-card');
            var idx = $card.index();
            var val = $(this).val();
            var p = providers[val];
            if (p && val !== 'other') {
                var $title = $card.find('input[name*="[title]"]');
                if (!$title.val() || $title.val().indexOf('Account') !== -1 || $title.val().indexOf('SMTP') !== -1 || Object.keys(providers).some(function (k) { return providers[k].name === $title.val(); })) {
                    $title.val(p.name).trigger('input');
                }
            }
            updateSmtpCard($card, idx);
        });

        function initSmtpCards() {
            $('.wps-repeater-container[data-name="wp_extra[smtp_accounts]"] .wps-repeater-card').each(function (idx) {
                updateSmtpCard($(this), idx);
            });
        }

        if ($('.wps-repeater-container[data-name="wp_extra[smtp_accounts]"]').length) {
            initSmtpCards();
            $(document).on('click', '.wps-repeater-add', function () {
                setTimeout(initSmtpCards, 80);
            });
        }

        /* ==========================================================================
           4. Email Delivery Logs Page
           ========================================================================== */
        var currentLogId = null;
        var logNonce = nonces.email_log || '';

        // View Details Modal
        $(document).on('click', '.wpex-view-log-btn', function (e) {
            e.preventDefault();
            var logId = $(this).data('id');
            currentLogId = logId;
            $('#wpex-modal-meta').html('<p style="color: #646970; margin: 0;">' + (i18n.loading_details || 'Loading details...') + '</p>');
            $('#wpex-modal-body').html('<div style="text-align: center; padding: 40px;"><span class="spinner is-active" style="float: none; margin: 0;"></span></div>');
            $('#wpex-email-log-modal').css('display', 'flex');

            $.post(ajaxurl, {
                action: 'wpex_view_email_log',
                id: logId,
                _nonce: logNonce
            }, function (res) {
                if (res.success) {
                    var log = res.data;
                    var metaHtml = '<div><strong>' + (i18n.to || 'To:') + '</strong> ' + $('<div/>').text(log.to_email).html() + '</div>' +
                                   '<div><strong>' + (i18n.subject || 'Subject:') + '</strong> ' + $('<div/>').text(log.subject).html() + '</div>' +
                                   '<div><strong>' + (i18n.date_time || 'Date / Time:') + '</strong> ' + log.created_at + ' | <strong>' + (i18n.mailer_account || 'Mailer Account:') + '</strong> ' + (log.mailer_account || 'Default') + '</div>';

                    if (log.status !== 'success' && log.error_message) {
                        metaHtml += '<div style="color: #d63638; margin-top: 4px;"><strong>' + (i18n.error_details || 'Error Details:') + '</strong> ' + $('<div/>').text(log.error_message).html() + '</div>';
                    }
                    $('#wpex-modal-meta').html(metaHtml);

                    var iframe = $('<iframe style="width: 100%; height: 360px; border: 1px solid #e2e4e7; border-radius: 4px;" />');
                    $('#wpex-modal-body').empty().append(iframe);
                    var doc = iframe[0].contentWindow.document;
                    doc.open();
                    doc.write(log.message);
                    doc.close();

                    var badge = (log.status === 'success')
                        ? '<span style="color: #007017; font-weight: 600;">✓ ' + (i18n.delivered_successfully || 'Delivered Successfully') + '</span>'
                        : '<span style="color: #b32d2e; font-weight: 600;">✗ ' + (i18n.delivery_failed || 'Delivery Failed') + '</span>';
                    $('#wpex-modal-status-badge').html(badge);
                } else {
                    $('#wpex-modal-body').html('<p style="color: #d63638;">' + (res.data || 'Failed to load details.') + '</p>');
                }
            });
        });

        $('#wpex-modal-close, #wpex-email-log-modal').on('click', function (e) {
            if (e.target === this) {
                $('#wpex-email-log-modal').hide();
            }
        });

        function resendLog(logId, $btn) {
            if (!window.confirm(i18n.confirm_resend_email || 'Are you sure you want to resend this email?')) {
                return;
            }
            $btn.prop('disabled', true);
            $.post(ajaxurl, {
                action: 'wpex_resend_email_log',
                id: logId,
                _nonce: logNonce
            }, function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    window.alert(res.data || (i18n.email_resent_successfully || 'Email resent successfully!'));
                    window.location.reload();
                } else {
                    window.alert(res.data || (i18n.failed_resend_email || 'Failed to resend email.'));
                }
            });
        }

        $(document).on('click', '.wpex-resend-log-btn', function (e) {
            e.preventDefault();
            resendLog($(this).data('id'), $(this));
        });

        $('#wpex-modal-resend-btn').on('click', function (e) {
            e.preventDefault();
            if (currentLogId) {
                resendLog(currentLogId, $(this));
            }
        });

        // Delete single log
        $(document).on('click', '.wpex-delete-log-btn', function (e) {
            e.preventDefault();
            var logId = $(this).data('id');
            if (!window.confirm(i18n.confirm_delete_log || 'Delete this email log record?')) {
                return;
            }
            var $row = $('#log-' + logId);
            $row.css('opacity', '0.4');

            $.post(ajaxurl, {
                action: 'wpex_delete_email_log',
                id: logId,
                _nonce: logNonce
            }, function (res) {
                if (res.success) {
                    $row.fadeOut(200, function () { $(this).remove(); });
                } else {
                    $row.css('opacity', '1');
                    window.alert(res.data || 'Failed to delete record.');
                }
            });
        });

        // Clear All Logs
        $('.wpex-clear-all-logs').on('click', function (e) {
            e.preventDefault();
            if (!window.confirm(i18n.confirm_clear_all_logs || 'Are you sure you want to delete ALL email log records? This cannot be undone.')) {
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.post(ajaxurl, {
                action: 'wpex_clear_email_logs',
                _nonce: logNonce
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    $btn.prop('disabled', false);
                    window.alert(res.data || 'Failed to clear logs.');
                }
            });
        });
    });
})(jQuery);
