(function ($) {
    'use strict';

    const selectors = {
        tab: '[data-gaenity-tab]',
        tabPanel: '.gaenity-support-hub__tab-panel',
        form: '.gaenity-support-hub__form',
    };

    function activateTab($button) {
        const target = $button.data('gaenity-tab');

        $button
            .addClass('is-active')
            .attr({
                'aria-selected': 'true',
                tabindex: '0',
            })
            .siblings()
            .removeClass('is-active')
            .attr({
                'aria-selected': 'false',
                tabindex: '-1',
            });

        $button
            .closest('.gaenity-support-hub__section')
            .find(selectors.tabPanel)
            .removeClass('is-active')
            .attr('hidden', true)
            .filter('#gaenity-tab-' + target)
            .addClass('is-active')
            .removeAttr('hidden');
    }

    function bindTabs($context) {
        $context.on('click keydown', selectors.tab, function (event) {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            activateTab($(this));
        });

        $context.find(selectors.tab).first().trigger('click');
    }

    function bindForms($context) {
        $context.on('submit', selectors.form, function (event) {
            event.preventDefault();
            const $form = $(this);
            const message = $form.data('success') || 'Submission received!';
            const $notice = $form.find('.gaenity-support-hub__form-notice');

            if ($notice.length) {
                $notice.text(message).addClass('is-visible');
            }

            this.reset();
        });
    }

    $(function () {
        const $hub = $('[data-gaenity-component="support-hub"]');

        if ($hub.length) {
            bindTabs($hub);
        }

        bindForms($(document));
    });
})(jQuery);
