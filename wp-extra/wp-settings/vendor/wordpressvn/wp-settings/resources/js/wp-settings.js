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
    $('input[type="checkbox"]').on('change', function() {
        var $span = $(this).closest('.components-form-toggle');
        if ($(this).is(':checked')) {
            $span.addClass('is-checked');
        } else {
            $span.removeClass('is-checked');
        }
    });
    $('.nav-tab-content .tab-content').not(':first').hide();
    $('.nav-section a').first().addClass('nav-tab-active');
    $('.nav-section a').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab-content .tab-content').hide();
        var tabId = $(this).data('section');
        $('#' + tabId).show();
        $('.nav-section a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
    });
    $('#submit').on('click', function(event) {
        var $button = $(this);
        $button.attr('aria-disabled', 'true');
        $button.addClass('is-busy');
        var $form = $(this).closest('form');
        $form.on('submit', function() {
            setTimeout(function() {
                $button.attr('aria-disabled', 'false');
                $button.removeClass('is-busy');
            }, 1000);
        });
    });
});