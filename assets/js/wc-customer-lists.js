document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('wc-customer-lists-modal');
    if (!modal) return;

    const modalContent = modal.querySelector('.wc-customer-lists-modal-content');
    const submitBtn = modal.querySelector('.modal-submit-btn');
    let currentProductId = null;

    /**
     * Show notification (CSS classed)
     */
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `wccl-notification wccl-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button type="button" class="wccl-notification-close">&times;</button>
        `;
        
        document.body.appendChild(notification);

        // Auto-remove
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);

        // Manual close
        const closeBtn = notification.querySelector('.wccl-notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                if (notification.parentNode) {
                    notification.remove();
                }
            });
        }
    }

    /**
     * Bind all "Add to List" buttons
     */
    document.querySelectorAll('.wc-customer-lists-add-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            currentProductId = parseInt(this.dataset.productId);
            if (currentProductId) {
                openModal();
            }
        });
    });

    /**
     * Close modal and reset
     */
    function closeModal() {
        if (modal.open) {
            modal.close();
        }
        if (modalContent) {
            modalContent.innerHTML = '<p class="wc-customer-lists-loading">Loading lists...</p>';
        }
        currentProductId = null;
    }

    // Close handlers (null-safe)
    const closeBtn = modal.querySelector('.modal-close-btn');
    const overlay = modal.querySelector('.wc-customer-lists-modal-overlay');
    
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);

    // ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.open) {
            closeModal();
        }
    });

    /**
     * Open modal + fetch lists
     */
    async function openModal() {
        if (!currentProductId) return;

        try {
            if (modalContent) {
                modalContent.innerHTML = '<p class="wc-customer-lists-loading">Loading lists...</p>';
            }
            const html = await fetchUserLists(currentProductId);
            
            if (modalContent) {
                modalContent.innerHTML = html;
                modal.showModal();
            }

            const listDropdown = modalContent?.querySelector('select[name="wc_list_id"]');
            if (listDropdown) {
                listDropdown.addEventListener('change', handleListChange);
                handleListChange(); // Init first option
            }
        } catch (error) {
            if (modalContent) {
                modalContent.innerHTML = '<p class="error">' + 
                    (error.data?.message || 'Error loading lists. Try again.') + 
                    '</p>';
                modal.showModal();
            }
            console.error('Modal error:', error);
        }
    }

    /**
     * Toggle event fields based on list selection
     */
    function handleListChange() {
        const dropdown = modalContent?.querySelector('select[name="wc_list_id"]');
        const container = modalContent?.querySelector('#wc_event_fields_container');
        
        if (!dropdown || !container) return;

        container.innerHTML = '';
        const supportsEvents = dropdown.selectedOptions[0]?.dataset.supportsEvents === '1';
        
        if (!supportsEvents) return;

        const fields = [
            { id: 'event_name', label: 'Event Name *', type: 'text' },
            { id: 'event_date', label: 'Event Date *', type: 'date' },
            { id: 'closing_date', label: 'Closing Date *', type: 'date' },
            { id: 'delivery_deadline', label: 'Delivery Deadline *', type: 'date' }
        ];

        fields.forEach(field => {
            const div = document.createElement('div');
            div.className = 'wc-event-field';
            div.innerHTML = `
                <label for="${field.id}">${field.label}</label>
                <input id="${field.id}" name="${field.id}" type="${field.type}" required>
            `;
            container.appendChild(div);

            // Animate in (CSS handles, just trigger class)
            requestAnimationFrame(() => div.classList.add('show'));
        });
    }

    /**
     * Submit form → add to list
     */
    if (submitBtn) {
        submitBtn.addEventListener('click', async function (e) {
            e.preventDefault();

            const dropdown = modalContent?.querySelector('select[name="wc_list_id"]');
            if (!dropdown?.value) {
                showNotification('Please select a list.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'wccl_add_product_to_list');
            formData.append('nonce', WCCL_Ajax.nonce);
            formData.append('product_id', currentProductId);
            formData.append('list_id', dropdown.value);

            // Add event fields safely
            modalContent?.querySelectorAll('#wc_event_fields_container input').forEach(input => {
                if (input.value) {
                    formData.append(`event_data[${input.name}]`, input.value);
                }
            });

            try {
                const response = await fetch(WCCL_Ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showNotification(data.data.message);
                    closeModal();
                    // Refresh to update UI (buttons, counts, etc.)
                    if (window.location.hash !== '#wc-customer-lists-modal') {
                        window.location.reload();
                    }
                } else {
                    showNotification(data.data?.message || 'Failed to add product.', 'error');
                }
            } catch (error) {
                showNotification('Connection error. Please try again.', 'error');
                console.error('Submit error:', error);
            }
        });
    }

    /**
     * Fetch user lists via AJAX
     */
    async function fetchUserLists(productId) {
        const params = new URLSearchParams({
            action: 'wccl_get_user_lists',
            nonce: WCCL_Ajax.nonce,
            product_id: productId
        });

        const response = await fetch(WCCL_Ajax.ajax_url, {
            method: 'POST',
            body: params,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });

        const data = await response.json();
        if (!data.success) {
            throw data;
        }
        return data.data.html;
    }
});
