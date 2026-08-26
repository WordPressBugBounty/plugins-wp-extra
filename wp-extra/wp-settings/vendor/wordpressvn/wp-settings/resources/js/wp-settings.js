jQuery(function($) {
    var clipboard = new ClipboardJS('.clipboard'),
        successTimeout;
    clipboard.on('success', function(event) {
        var triggerElement = $(event.trigger),
            successElement = $('.success', triggerElement.closest('.copy-to-clipboard-container'));
        event.clearSelection();
        clearTimeout(successTimeout);
        successElement.removeClass('hidden').addClass('visible');
        successTimeout = setTimeout(function() {
            successElement.removeClass('visible').addClass('hidden');
        }, 3000);
        if (typeof wp !== 'undefined' && typeof wp.a11y !== 'undefined') {
            wp.a11y.speak(wp.i18n.__('The content has been copied to your clipboard'));
        }
    });

    // Checkbox Gutenberg toggle switch state
    $('input[type="checkbox"]').on('change', function() {
        var $span = $(this).closest('.components-form-toggle');
        if ($(this).is(':checked')) {
            $span.addClass('is-checked');
        } else {
            $span.removeClass('is-checked');
        }
    });

    // Choice card radio selection highlight
    $('.wps-choice-label-wrapper input[type="radio"]').on('change', function() {
        var $group = $(this).closest('.wps-choices-grid');
        $group.find('.wps-choice-label-wrapper').removeClass('is-selected');
        if ($(this).is(':checked')) {
            $(this).closest('.wps-choice-label-wrapper').addClass('is-selected');
        }
    });

    // Sub-section switching with URL Hash and LocalStorage memory
    function activateSection(sectionId) {
        if (!sectionId) return;
        var $targetLink = $('.nav-section a[data-section="' + sectionId + '"]');
        if ($targetLink.length) {
            $('.nav-tab-content .tab-content').hide();
            $('#' + sectionId).fadeIn(150);
            $('.nav-section a').removeClass('nav-tab-active');
            $targetLink.addClass('nav-tab-active');
            if (history.replaceState) {
                history.replaceState(null, null, '#' + sectionId);
            }
            try {
                localStorage.setItem('wps_last_section', sectionId);
            } catch (e) {}
        }
    }

    var hash = window.location.hash ? window.location.hash.substring(1) : '';
    if (hash && $('#' + hash).length) {
        activateSection(hash);
    } else {
        var lastSection = '';
        try {
            lastSection = localStorage.getItem('wps_last_section');
        } catch (e) {}

        if (lastSection && $('.nav-section a[data-section="' + lastSection + '"]').length) {
            activateSection(lastSection);
        } else {
            $('.nav-tab-content .tab-content').not(':first').hide();
            $('.nav-section a').first().addClass('nav-tab-active');
        }
    }

    $('.nav-section a').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).data('section');
        activateSection(tabId);
    });

    // Declarative show_if evaluator
    function evaluateShowIf() {
        $('tr[data-show-if]').each(function() {
            var $row = $(this);
            var conditions = $row.data('show-if');
            if (!conditions || typeof conditions !== 'object') return;

            var matchAll = true;
            $.each(conditions, function(fieldKey, targetValue) {
                var $input = $(':input[name*="[' + fieldKey + ']"], :input#' + fieldKey);
                if (!$input.length) return;

                var currentValue;
                if ($input.is(':checkbox')) {
                    currentValue = $input.is(':checked') ? 1 : 0;
                    if (targetValue === true) targetValue = 1;
                    if (targetValue === false) targetValue = 0;
                } else if ($input.is(':radio')) {
                    currentValue = $input.filter(':checked').val();
                } else {
                    currentValue = $input.val();
                }

                if (String(currentValue) !== String(targetValue)) {
                    matchAll = false;
                    return false;
                }
            });

            if (matchAll) {
                $row.show().removeClass('hidden');
            } else {
                $row.hide().addClass('hidden');
            }
        });
    }

    evaluateShowIf();
    $(document).on('change', 'form :input', function() {
        evaluateShowIf();
    });

    // Toast Floating Notification
    function showToast(message, type) {
        $('.wps-ajax-toast').remove();
        var $toast = $('<div class="wps-ajax-toast is-' + (type || 'success') + '">' + message + '</div>');
        $('body').append($toast);
        setTimeout(function() { $toast.addClass('is-visible'); }, 50);
        setTimeout(function() {
            $toast.removeClass('is-visible');
            setTimeout(function() { $toast.remove(); }, 350);
        }, 2500);
    }

    // AJAX Save handler
    $('form[data-ajax-save="1"]').on('submit', function(e) {
        if ($(document.activeElement).attr('name') === 'do_reset') {
            return;
        }
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('#submit');
        $btn.attr('aria-disabled', 'true').addClass('is-busy');

        var formData = new FormData(this);
        formData.append('action', $form.data('action'));

        var ajaxEndpoint = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';

        fetch(ajaxEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(function(res) {
            return res.text().then(function(text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('AJAX save raw response:', text);
                    var errMsg = (typeof wp !== 'undefined' && wp.i18n) ? wp.i18n.__('Something went wrong.') : 'Something went wrong.';
                    return { success: false, data: { message: errMsg } };
                }
            });
        })
        .then(function(res) {
            $btn.attr('aria-disabled', 'false').removeClass('is-busy');
            if (res && res.success) {
                var defaultSuccess = (typeof wp !== 'undefined' && wp.i18n) ? wp.i18n.__('Settings saved.') : 'Settings saved.';
                showToast(res.data.message || defaultSuccess, 'success');
                if (res.data && res.data.tab_menu_html) {
                    $('#wrap-extra header .nav-menu').replaceWith(res.data.tab_menu_html);
                }
                if (res.data && res.data.is_module_tab) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                }
            } else {
                var defaultError = (typeof wp !== 'undefined' && wp.i18n) ? wp.i18n.__('Something went wrong.') : 'Something went wrong.';
                showToast((res && res.data && res.data.message) || defaultError, 'error');
            }
        })
        .catch(function(err) {
            $btn.attr('aria-disabled', 'false').removeClass('is-busy');
            console.error('AJAX Save Network Error:', err);
            var defaultError = (typeof wp !== 'undefined' && wp.i18n) ? wp.i18n.__('Something went wrong.') : 'Something went wrong.';
            showToast(defaultError, 'error');
        });
    });

    // Non-AJAX fallback save button spinner
    $('form:not([data-ajax-save="1"]) #submit').on('click', function() {
        var $button = $(this);
        $button.attr('aria-disabled', 'true').addClass('is-busy');
        var $form = $(this).closest('form');
        $form.on('submit', function() {
            setTimeout(function() {
                $button.attr('aria-disabled', 'false').removeClass('is-busy');
            }, 1000);
        });
    });

    // Repeater: Add Card
    $(document).on('click', '.wps-repeater-add', function(e) {
        e.preventDefault();
        var $container = $(this).closest('.wps-repeater-container');
        var $template = $container.find('.wps-repeater-template');
        var $rows = $container.find('.wps-repeater-rows');
        var randSuffix = Date.now();
        var newIndex = $rows.find('.wps-repeater-card').length + '_' + randSuffix;

        var templateHtml = $template.html();
        var $newCard = $(templateHtml);

        $newCard.find('[data-name-template]').each(function() {
            var name = $(this).data('name-template').replace(/__INDEX__/g, newIndex);
            $(this).attr('name', name).removeAttr('data-name-template');
        });

        // Append to DOM first so dimensions are properly computed
        $rows.append($newCard);

        // Initialize Dynamic WP Editor if present
        var $dynamicEditor = $newCard.find('.wps-dynamic-editor');
        if ($dynamicEditor.length) {
            var dynamicId = 'wps_ed_' + randSuffix;
            $dynamicEditor.attr('id', dynamicId);

            var defaultMceInit = {
                selector: '#' + dynamicId,
                elements: dynamicId,
                wpautop: true,
                plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wptextpattern',
                toolbar1: 'bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_adv',
                toolbar2: 'formatselect,underline,alignjustify,forecolor,pastetext,removeformat,undo,redo'
            };

            var defaultQtInit = {
                id: dynamicId,
                buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
            };

            if (typeof tinyMCEPreInit !== 'undefined') {
                if (tinyMCEPreInit.mceInit) {
                    for (var baseId in tinyMCEPreInit.mceInit) {
                        if (tinyMCEPreInit.mceInit.hasOwnProperty(baseId)) {
                            defaultMceInit = $.extend(true, {}, tinyMCEPreInit.mceInit[baseId]);
                            defaultMceInit.selector = '#' + dynamicId;
                            defaultMceInit.elements = dynamicId;
                            if (defaultMceInit.body_class) {
                                defaultMceInit.body_class = defaultMceInit.body_class.replace(baseId, dynamicId);
                            }
                            break;
                        }
                    }
                }
                if (tinyMCEPreInit.qtInit) {
                    for (var qtBaseId in tinyMCEPreInit.qtInit) {
                        if (tinyMCEPreInit.qtInit.hasOwnProperty(qtBaseId)) {
                            defaultQtInit = $.extend(true, {}, tinyMCEPreInit.qtInit[qtBaseId]);
                            defaultQtInit.id = dynamicId;
                            break;
                        }
                    }
                }
            }

            if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
                wp.editor.initialize(dynamicId, {
                    tinymce: defaultMceInit,
                    quicktags: defaultQtInit,
                    mediaButtons: true
                });
            }
        }

        // Focus title field and smooth scroll
        var $titleField = $newCard.find('.wps-title-sync');
        if ($titleField.length) {
            $titleField.focus();
        }
        
        $('html, body').animate({
            scrollTop: $newCard.offset().top - 70
        }, 200);
    });

    // Repeater: Remove Card
    $(document).on('click', '.wps-repeater-remove', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $card = $(this).closest('.wps-repeater-card');
        
        // Clean up WP Editor instance if exists
        var $editor = $card.find('textarea[id^="wps_ed_"]');
        if ($editor.length && typeof wp !== 'undefined' && wp.editor && wp.editor.remove) {
            wp.editor.remove($editor.attr('id'));
        }

        $card.fadeOut(200, function() {
            var $rows = $(this).closest('.wps-repeater-rows');
            $(this).remove();
            if ($rows.find('.wps-repeater-card').length === 0) {
                $rows.empty();
            }
        });
    });

    // Repeater: Card Accordion Toggle
    $(document).on('click', '.wps-repeater-card-header', function(e) {
        if ($(e.target).closest('.wps-repeater-remove').length) return;
        var $card = $(this).closest('.wps-repeater-card');
        $card.toggleClass('is-collapsed');
    });

    $(document).on('click', '.wps-repeater-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $card = $(this).closest('.wps-repeater-card');
        $card.toggleClass('is-collapsed');
    });
    // Repeater: Sync Title on typing
    $(document).on('input change', '.wps-title-sync', function() {
        var val = $(this).val().trim();
        var $card = $(this).closest('.wps-repeater-card');
        $card.find('.wps-card-label').text(val || 'Untitled Widget');
    });

    // Repeater: Initialize Drag & Drop Sortable
    function initRepeaterSortable() {
        if (typeof $.fn.sortable === 'undefined') return;
        
        $('.wps-repeater-rows').sortable({
            handle: '.wps-repeater-card-header',
            placeholder: 'wps-repeater-card-placeholder',
            cursor: 'move',
            opacity: 0.9,
            cancel: 'button, input, select, textarea',
            start: function(e, ui) {
                ui.placeholder.height(ui.item.outerHeight());
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }
            },
            stop: function(e, ui) {
                var $container = $(this).closest('.wps-repeater-container');
                var baseName = $container.data('name');
                
                $container.find('.wps-repeater-card').each(function(cardIndex) {
                    $(this).find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name && baseName) {
                            var escapedBase = baseName.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
                            var newName = name.replace(new RegExp('^' + escapedBase + '\\[[^\\]]+\\]'), baseName + '[' + cardIndex + ']');
                            $(this).attr('name', newName);
                        }
                    });
                });
            }
        });
    }

    initRepeaterSortable();

    // Save TinyMCE content before form submission
    $('form#wps-settings-form, form[action="options.php"]').on('submit', function() {
        if (typeof tinyMCE !== 'undefined') {
            tinyMCE.triggerSave();
        }
    });

    // Module Grid Switches
    $(document).on('click', '.wps-module-item', function(e) {
        if ($(e.target).is('input') || $(e.target).closest('.wps-switch-toggle').length) {
            return;
        }
        var $input = $(this).find('input[type="checkbox"]');
        $input.prop('checked', !$input.prop('checked')).trigger('change');
    });

    $(document).on('change', '.wps-switch-toggle input', function() {
        var $card = $(this).closest('.wps-module-item');
        if (this.checked) {
            $card.addClass('is-active');
        } else {
            $card.removeClass('is-active');
        }
    });

    // Backup JSON Import
    $(document).off('click.wpsImport', '.wps-btn-import-file, .wpex-btn-import-file').on('click.wpsImport', '.wps-btn-import-file, .wpex-btn-import-file', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $wrap = $btn.closest('.wps-transfer-card');
        var $fileInput = $wrap.find('.wps-import-file-input, input[type="file"]');
        var $spinner = $wrap.find('.wps-import-spinner, .spinner');
        var $feedback = $wrap.find('.wps-transfer-feedback');
        var optionName = $btn.data('option') || 'wp_settings';
        var nonce = $btn.data('nonce') || '';

        if (!$fileInput.length || !$fileInput[0].files || !$fileInput[0].files[0]) {
            alert('Please select a .json backup file first.');
            return;
        }

        if (!confirm('Are you sure you want to import this file? Existing configurations will be overwritten.')) {
            return;
        }

        var file = $fileInput[0].files[0];
        var formData = new FormData();
        formData.append('action', 'wp_settings_import_backup');
        formData.append('nonce', nonce);
        formData.append('option_name', optionName);
        formData.append('backup_file', file);

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $feedback.text('Importing...').css('color', '#2271b1');

        var endpoint = (typeof ajaxurl !== 'undefined') ? ajaxurl : (window.ajaxurl || '/wp-admin/admin-ajax.php');

        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            if (data.success) {
                $feedback.css('color', '#00a32a').text(data.data.message || 'Settings imported successfully!');
                setTimeout(function() {
                    window.location.reload();
                }, 1200);
            } else {
                $feedback.css('color', '#d63638').text((data.data && data.data.message) ? data.data.message : 'Import failed.');
            }
        })
        .catch(function() {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            $feedback.css('color', '#d63638').text('Import failed.');
        });
    });

    // Checkbox List: Select All / Deselect All
    $(document).on('click', '.select-all', function(e) {
        e.preventDefault();
        $(this).closest('td').find('input[type="checkbox"]').prop('checked', true);
    });
    $(document).on('click', '.deselect', function(e) {
        e.preventDefault();
        $(this).closest('td').find('input[type="checkbox"]').prop('checked', false);
    });

    // Disk File Editor (Save / Reload / Restore)
    $(document).off('click.wpsSave', '.wps-save-file-btn, .wpex-save-file-btn').on('click.wpsSave', '.wps-save-file-btn, .wpex-save-file-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('target');
        var filepath = $btn.data('filepath');
        var $ta = $('#' + targetId);
        var cm = $ta.data('codemirrorInstance');
        if (!cm && $ta.next('.CodeMirror').length && $ta.next('.CodeMirror')[0].CodeMirror) {
            cm = $ta.next('.CodeMirror')[0].CodeMirror;
        }
        var content = cm ? cm.getValue() : $ta.val();

        $btn.prop('disabled', true).addClass('updating-message');
        $.post(ajaxurl, {
            action: 'wp_settings_save_disk_file',
            filepath: filepath,
            content: content,
            _nonce: (window.wpex_settings && window.wpex_settings.nonces && window.wpex_settings.nonces.reload_disk_file) || ''
        }, function(res) {
            $btn.prop('disabled', false).removeClass('updating-message');
            if (res.success) {
                alert(res.data.message || 'Saved successfully to file.');
                if (res.data.has_backup) {
                    $btn.siblings('.wps-restore-file-btn, .wpex-restore-file-btn').show();
                }
            } else {
                alert(res.data || 'Failed to save file.');
            }
        });
    });

    $(document).off('click.wpsReload', '.wps-reload-file-btn, .wpex-reload-file-btn').on('click.wpsReload', '.wps-reload-file-btn, .wpex-reload-file-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('target');
        var filepath = $btn.data('filepath');
        $btn.prop('disabled', true).addClass('updating-message');
        $.post(ajaxurl, {
            action: 'wp_settings_reload_disk_file',
            filepath: filepath,
            _nonce: (window.wpex_settings && window.wpex_settings.nonces && window.wpex_settings.nonces.reload_disk_file) || ''
        }, function(res) {
            $btn.prop('disabled', false).removeClass('updating-message');
            if (res.success) {
                var $ta = $('#' + targetId);
                $ta.val(res.data.content);
                var cm = $ta.data('codemirrorInstance');
                if (!cm && $ta.next('.CodeMirror').length && $ta.next('.CodeMirror')[0].CodeMirror) {
                    cm = $ta.next('.CodeMirror')[0].CodeMirror;
                }
                if (cm) {
                    cm.setValue(res.data.content);
                    cm.save();
                }
            } else {
                alert(res.data || 'Could not reload file.');
            }
        });
    });

    $(document).off('click.wpsRestore', '.wps-restore-file-btn, .wpex-restore-file-btn').on('click.wpsRestore', '.wps-restore-file-btn, .wpex-restore-file-btn', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to load the previous backup into the editor?')) {
            return;
        }
        var $btn = $(this);
        var targetId = $btn.data('target');
        var filepath = $btn.data('filepath');
        $btn.prop('disabled', true).addClass('updating-message');
        $.post(ajaxurl, {
            action: 'wp_settings_restore_disk_file',
            filepath: filepath,
            _nonce: (window.wpex_settings && window.wpex_settings.nonces && window.wpex_settings.nonces.reload_disk_file) || ''
        }, function(res) {
            $btn.prop('disabled', false).removeClass('updating-message');
            if (res.success) {
                var $ta = $('#' + targetId);
                $ta.val(res.data.content);
                var cm = $ta.data('codemirrorInstance');
                if (!cm && $ta.next('.CodeMirror').length && $ta.next('.CodeMirror')[0].CodeMirror) {
                    cm = $ta.next('.CodeMirror')[0].CodeMirror;
                }
                if (cm) {
                    cm.setValue(res.data.content);
                    cm.save();
                }
                $btn.hide();
                if (res.data.message) {
                    alert(res.data.message);
                }
            } else {
                alert(res.data || 'No backup available.');
            }
        });
    });
});