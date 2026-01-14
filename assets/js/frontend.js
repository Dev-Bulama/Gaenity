(function ($) {
    'use strict';

    const pluginData = window.GaeinityCommunity || {};

    const modalTriggers = document.querySelectorAll('[data-resource]');
    modalTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const resourceId = trigger.getAttribute('data-resource');
            const modal = document.getElementById(`gaenity-resource-modal-${resourceId}`);
            if (modal) {
                modal.removeAttribute('hidden');
                modal.setAttribute('aria-hidden', 'false');
            }
        });
    });

    // Paid resource modal triggers
    const paidModalTriggers = document.querySelectorAll('[data-paid-resource]');
    paidModalTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const resourceId = trigger.getAttribute('data-paid-resource');
            const modal = document.getElementById(`gaenity-paid-resource-modal-${resourceId}`);
            if (modal) {
                modal.removeAttribute('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                // Focus first input in modal
                setTimeout(() => {
                    const firstInput = modal.querySelector('input, select, textarea');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            }
        });
    });

    // Modal close handlers with body scroll lock
    document.addEventListener('click', (event) => {
        const target = event.target;
        
        // Close button clicked
        if (target.classList.contains('gaenity-modal-close')) {
            const modal = target.closest('.gaenity-modal');
            if (modal) {
                closeModal(modal);
            }
        }
        
        // Clicked outside modal content (on overlay)
        if (target.classList.contains('gaenity-modal')) {
            closeModal(target);
        }
    });

    // Close modal on ESC key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.gaenity-modal:not([hidden])');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });

    // Helper function to close modal
    function closeModal(modal) {
        modal.setAttribute('hidden', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = ''; // Restore scroll
    }

    // When opening modal, prevent body scroll
    modalTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const resourceId = trigger.getAttribute('data-resource');
            const modal = document.getElementById(`gaenity-resource-modal-${resourceId}`);
            if (modal) {
                modal.removeAttribute('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
                
                // Focus first input in modal
                setTimeout(() => {
                    const firstInput = modal.querySelector('input, select, textarea');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            }
        });
    });

    document.addEventListener('change', (event) => {
        const select = event.target;
        if (select.matches('select[name="industry"]')) {
            const container = select.closest('form');
            const otherField = container ? container.querySelector('input[name="industry_other"]') : null;
            if (otherField) {
                if (select.value && select.value.toLowerCase() === 'other') {
                    otherField.parentElement?.classList.remove('gaenity-hidden');
                    otherField.required = true;
                } else {
                    otherField.parentElement?.classList.add('gaenity-hidden');
                    otherField.value = '';
                    otherField.required = false;
                }
            }
        }
    });

    const handleFormSuccess = (form, data) => {
        const feedback = form.querySelector('.gaenity-form-feedback');
        if (data && data.message && feedback) {
            feedback.textContent = data.message;
            feedback.classList.remove('gaenity-error');
        }

        // Handle redirect to download page
        if (data && data.redirect_url) {
            const modal = form.closest('.gaenity-modal');
            if (modal) {
                modal.setAttribute('hidden', 'hidden');
                document.body.style.overflow = '';
            }
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 500);
            return;
        }

        // Legacy: Handle direct download URL (for backward compatibility)
        if (data && data.download_url) {
            window.open(data.download_url, '_blank');
            const modal = form.closest('.gaenity-modal');
            if (modal) {
                modal.setAttribute('hidden', 'hidden');
            }
        }

        const successMessage = form.getAttribute('data-success-message');
        if (successMessage && feedback) {
            feedback.textContent = successMessage;
        }

        const redirect = form.getAttribute('data-success-redirect');
        if (redirect) {
            setTimeout(() => {
                window.location.href = redirect;
            }, 800);
        }

        const refreshTarget = form.getAttribute('data-refresh');
        if (refreshTarget && data && data.results) {
            const container = document.getElementById(refreshTarget);
            if (container) {
                container.innerHTML = data.results;
            }
        } else if (refreshTarget && !data.results) {
            if (refreshTarget === 'gaenity-chat') {
                refreshChat();
            } else if (refreshTarget === 'gaenity-polls') {
                window.location.reload();
            }
        }

        form.reset();
    };

    const handleFormError = (form, message) => {
        const feedback = form.querySelector('.gaenity-form-feedback');
        if (feedback) {
            feedback.textContent = message || 'Something went wrong. Please try again.';
            feedback.classList.add('gaenity-error');
        }
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.classList.contains('gaenity-ajax-form')) {
            return;
        }
        event.preventDefault();

        const feedback = form.querySelector('.gaenity-form-feedback');
        if (feedback) {
            feedback.textContent = pluginData.submittingText || 'Submitting…';
            feedback.classList.remove('gaenity-error');
        }

        const formData = new FormData(form);
        if (!formData.has('gaenity_nonce') && pluginData.nonce) {
            formData.append('gaenity_nonce', pluginData.nonce);
        }

        fetch(pluginData.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then((response) => response.json())
            .then((json) => {
                if (json.success) {
                    handleFormSuccess(form, json.data || {});
                } else {
                    handleFormError(form, json.data?.message || json.message);
                }
            })
            .catch(() => {
                handleFormError(form);
            });
    });

    const refreshChat = () => {
        const chatSection = document.querySelector('.gaenity-chat');
        if (!chatSection || !pluginData.ajaxUrl) {
            return;
        }
        const list = chatSection.querySelector('.gaenity-chat-messages');
        if (!list) {
            return;
        }
        const formData = new FormData();
        formData.append('action', 'gaenity_chat_fetch');
        formData.append('gaenity_nonce', pluginData.nonce || '');
        fetch(pluginData.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then((response) => response.json())
            .then((json) => {
                if (!json.success || !Array.isArray(json.data?.messages)) {
                    return;
                }
                list.innerHTML = '';
                json.data.messages.forEach((item) => {
                    const li = document.createElement('li');
                    const meta = document.createElement('div');
                    meta.className = 'gaenity-chat-meta';
                    meta.innerHTML = `<strong>${item.display_name || ''}</strong>`;
                    if (item.role) {
                        meta.innerHTML += ` <span class="gaenity-badge">${item.role}</span>`;
                    }
                    meta.innerHTML += ` <span class="gaenity-chat-timestamp">${item.time || ''}</span>`;
                    const body = document.createElement('div');
                    body.className = 'gaenity-chat-body';
                    body.innerHTML = item.message || '';
                    li.appendChild(meta);
                    li.appendChild(body);
                    list.appendChild(li);
                });
                const windowEl = chatSection.querySelector('.gaenity-chat-window');
                if (windowEl) {
                    windowEl.scrollTop = windowEl.scrollHeight;
                }
            })
            .catch(() => {});
    };

    if (pluginData.chat && pluginData.chat.pollInterval) {
        setInterval(refreshChat, pluginData.chat.pollInterval);
        refreshChat();
    }

    // Resource tab switching
    const resourceTabs = document.querySelectorAll('.gaenity-resource-tab');
    const resourceGrids = document.querySelectorAll('.gaenity-resource-grid');

    if (resourceTabs.length > 0 && resourceGrids.length > 0) {
        // Hide all grids except the first one
        resourceGrids.forEach((grid, index) => {
            if (index !== 0) {
                grid.style.display = 'none';
            }
        });

        // Mark first tab as active
        if (resourceTabs[0]) {
            resourceTabs[0].classList.add('active');
        }

        // Add click handlers to tabs
        resourceTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const targetId = tab.getAttribute('data-target');

                // Remove active class from all tabs
                resourceTabs.forEach((t) => t.classList.remove('active'));

                // Add active class to clicked tab
                tab.classList.add('active');

                // Hide all grids
                resourceGrids.forEach((grid) => {
                    grid.style.display = 'none';
                });

                // Show target grid
                const targetGrid = document.getElementById(targetId);
                if (targetGrid) {
                    targetGrid.style.display = 'grid';
                }
            });
        });
    }

})(jQuery);
