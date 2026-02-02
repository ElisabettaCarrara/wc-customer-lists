/**
 * WC Customer Lists - Frontend JavaScript
 * Vanilla JS - Product Modal + My Account CRUD
 */

document.addEventListener('DOMContentLoaded', function () {
    // ========================================
    // PRODUCT MODAL (Add to List)
    // ========================================
    const modal = document.getElementById('wc-customer-lists-modal');
    if (modal) initModal();

    // ========================================
    // MY ACCOUNT LISTS (CRUD)
    // ========================================
    initMyAccountHandlers();

    // ========================================
    // SHARED UTILITIES
    // ========================================
    window.WCCL = window.WCCL || {};
    window.WCCL.showNotification = showNotification;
});

function initModal() {
    const modal = document.getElementById('wc-customer-lists-modal');
    const modalContent = modal.querySelector('.wc-customer-lists-modal-content');
    let currentProductId = null;

    /**
     * Show notification toast
     */
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `wccl-notification wccl-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button type="button" class="wccl-notification-close">&times;</button>
        `;
        
        document.body.appendChild(notification);

        setTimeout(() => notification.remove(), 5000);
        notification.querySelector('.wccl-notification-close')?.addEventListener('click', () => notification.remove());
    }

    /**
     * Bind product buttons
     */
    document.querySelectorAll('.wc-customer-lists-add-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            currentProductId = parseInt(button.dataset.productId);
            if (currentProductId) openModal();
        });
    });

    /**
     * Close + reset
     */
    function closeModal() {
        modal.close();
        modalContent.innerHTML = '<p class="wc-customer-lists-loading">Loading lists...</p>';
        currentProductId = null;
    }

    // Close events
    modal.querySelector('.modal-close-btn')?.addEventListener('click', closeModal);
    modal.querySelector('.wc-customer-lists-modal-overlay')?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.open) closeModal();
    });

    /**
     * Open + fetch lists
     */
    async function openModal() {
        try {
            modalContent.innerHTML = '<p class="wc-customer-lists-loading">Loading lists...</p>';
            const html = await fetchUserLists(currentProductId);
            
            modalContent.innerHTML = html;
            modal.showModal();

            const dropdown = modalContent.querySelector('select[name="wc_list_id"]');
            if (dropdown) {
                dropdown.addEventListener('change', handleListChange);
                handleListChange();
            }
        } catch (error) {
            modalContent.innerHTML = `<p class="error">${error.data?.message || 'Error loading lists'}</p>`;
            modal.showModal();
        }
    }

    /**
     * Event fields toggle
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

        fields.forEach(({ id, label, type }) => {
            const div = document.createElement('div');
            div.className = 'wc-event-field';
            div.innerHTML = `<label for="${id}">${label}</label><input id="${id}" name="${id}" type="${type}" required>`;
            container.appendChild(div);
            requestAnimationFrame(() => div.classList.add('show'));
        });
    }

    /**
     * Submit to AJAX
     */
    modal.querySelector('.modal-submit-btn')?.addEventListener('click', async (e) => {
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

        modalContent.querySelectorAll('#wc_event_fields_container input').forEach(input => {
            if (input.value) formData.append(`event_data[${input.name}]`, input.value);
        });

        try {
            const res = await fetch(WCCL_Ajax.ajax_url, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showNotification(data.data.message);
                closeModal();
                if (window.location.hash !== '#wc-customer-lists-modal') {
                    window.location.reload();
                }
            } else {
                showNotification(data.data?.message || 'Failed to add.', 'error');
            }
        } catch (error) {
            showNotification('Network error.', 'error');
        }
    });

    /**
     * Fetch lists AJAX
     */
    async function fetchUserLists(productId) {
        const params = new URLSearchParams({
            action: 'wccl_get_user_lists',
            nonce: WCCL_Ajax.nonce,
            product_id: productId
        });

        const res = await fetch(WCCL_Ajax.ajax_url, {
            method: 'POST',
            body: params,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });

        const data = await res.json();
        if (!data.success) throw data;
        return data.data.html;
    }
}

function initMyAccountHandlers() {
    // My Account AJAX data (localized in PHP)
    const ajaxData = window.WCCL_MyAccount || {};

    document.addEventListener('click', (e) => {
        // Toggle list products
        if (e.target.matches('.toggle-list')) {
            const container = document.querySelector(`#list-${e.target.dataset.listId}`);
            const isHidden = container.style.display === 'none';
            container.style.display = isHidden ? 'block' : 'none';
            e.target.textContent = isHidden ? 'Hide Products' : 'Show Products';
        }

        // Delete entire list
        if (e.target.matches('.delete-list')) {
            if (confirm('Delete this entire list?')) {
                deleteList(e.target.dataset.listId);
            }
        }

        // Remove single product
        if (e.target.matches('.remove-item')) {
            if (confirm('Remove this product from list?')) {
                removeProduct(e.target.dataset.listId, e.target.dataset.productId);
            }
        }
    });
}

async function deleteList(listId) {
    const formData = new FormData();
    formData.append('action', 'wccl_delete_list');
    formData.append('nonce', window.WCCL_MyAccount?.nonce || WCCL_Ajax.nonce);
    formData.append('list_id', listId);

    try {
        const res = await fetch(window.WCCL_MyAccount?.ajax_url || WCCL_Ajax.ajax_url, { 
            method: 'POST', 
            body: formData 
        });
        const data = await res.json();

        if (data.success) {
            document.querySelector(`.wc-customer-lists-card:has(#list-${listId})`).remove();
            window.WCCL?.showNotification(data.data.message);
        } else {
            window.WCCL?.showNotification(data.data?.message || 'Delete failed.', 'error');
        }
    } catch (error) {
        window.WCCL?.showNotification('Delete failed.', 'error');
    }
}

async function removeProduct(listId, productId) {
    const formData = new FormData();
    formData.append('action', 'wccl_toggle_product');
    formData.append('nonce', window.WCCL_MyAccount?.nonce || WCCL_Ajax.nonce);
    formData.append('list_id', listId);
    formData.append('product_id', productId);

    try {
        const res = await fetch(window.WCCL_MyAccount?.ajax_url || WCCL_Ajax.ajax_url, { 
            method: 'POST', 
            body: formData 
        });
        const data = await res.json();

        if (data.success) {
            // Remove row
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (row) row.remove();
            
            // Update count
            document.querySelector('.item-count')?.textContent = data.data.item_count;
            
            window.WCCL?.showNotification(data.data.message);
        } else {
            window.WCCL?.showNotification(data.data?.message || 'Remove failed.', 'error');
        }
    } catch (error) {
        window.WCCL?.showNotification('Remove failed.', 'error');
    }
}
