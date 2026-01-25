document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('wc-customer-lists-modal');
    const modalContent = modal.querySelector('.wc-customer-lists-modal-content');
    const submitBtn = modal.querySelector('.modal-submit-btn');
    let currentProductId = null;

    /**
     * Open modal when any "Add to List" button is clicked
     */
    document.querySelectorAll('.wc-customer-lists-add-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            currentProductId = this.dataset.productId;
            openModal();
        });
    });

    /**
     * Close modal function
     */
    function closeModal() {
        modal.close();
        modalContent.innerHTML = '<p class="wc-customer-lists-loading">Loading your lists...</p>';
    }

    /**
     * Close modal events
     */
    modal.querySelector('.modal-close-btn').addEventListener('click', closeModal);

    // Close on ESC key
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.open) closeModal();
    });

    // Close when clicking on overlay
    const overlay = modal.querySelector('.wc-customer-lists-modal-overlay');
    overlay.addEventListener('click', function () {
        if (modal.open) closeModal();
    });

    /**
     * Open modal and fetch user lists
     */
    function openModal() {
        fetchUserLists(currentProductId)
            .then(html => {
                modalContent.innerHTML = html;
                modal.showModal();

                // Attach change handler for dropdown
                const listDropdown = modalContent.querySelector('select[name="wc_list_id"]');
                if (listDropdown) {
                    listDropdown.addEventListener('change', handleListChange);
                    handleListChange(); // initialize first selection
                }
            })
            .catch(err => {
                modalContent.innerHTML = '<p>Error loading lists. Please try again.</p>';
                modal.showModal();
                console.error(err);
            });
    }

    /**
     * Handle list selection change to show/hide event fields
     */
    function handleListChange() {
        const selectedList = modalContent.querySelector('select[name="wc_list_id"]');
        const container = modalContent.querySelector('#wc_event_fields_container');

        if (!selectedList || !container) return;
        container.innerHTML = '';

        const supportsEvents = selectedList.selectedOptions[0].dataset.supportsEvents === '1';
        if (!supportsEvents) return;

        // Dynamically add standard event fields
        const eventFields = [
            { id: 'event_name', label: 'Event Name', type: 'text', required: true },
            { id: 'event_date', label: 'Event Date', type: 'date', required: true },
            { id: 'closing_date', label: 'List Closing Date', type: 'date', required: true },
            { id: 'delivery_deadline', label: 'Delivery Deadline', type: 'date', required: true }
        ];

        eventFields.forEach(f => {
            const wrapper = document.createElement('div');
            wrapper.className = 'wc-event-field';

            const label = document.createElement('label');
            label.htmlFor = f.id;
            label.textContent = f.label;

            const input = document.createElement('input');
            input.type = f.type;
            input.id = f.id;
            input.name = f.id;
            input.required = f.required;

            wrapper.appendChild(label);
            wrapper.appendChild(input);
            container.appendChild(wrapper);
        });
    }

    /**
     * Submit handler
     */
    submitBtn.addEventListener('click', function (e) {
        e.preventDefault();

        const selectedList = modalContent.querySelector('select[name="wc_list_id"]');
        if (!selectedList || !selectedList.value) {
            alert('Please select a list.');
            return;
        }

        const listId = selectedList.value;

        // Collect event data safely
        const eventData = {};
        let missingField = false;
        modalContent.querySelectorAll('#wc_event_fields_container input').forEach(input => {
            if (input.required && !input.value) {
                alert(`Please fill in the "${input.previousSibling.textContent}" field.`);
                missingField = true;
                return;
            }
            eventData[input.name] = input.value;
        });
        if (missingField) return;

        addProductToList(currentProductId, listId, eventData)
            .then(resp => {
                alert(resp.data.message);
                closeModal();
            })
            .catch(err => {
                alert('Error adding product. Please try again.');
                console.error(err);
            });
    });

    /**
     * Fetch user lists via AJAX
     */
    function fetchUserLists(productId) {
        const params = new URLSearchParams({
            action: 'wccl_get_user_lists',
            nonce: WCCL_Ajax.nonce,
            product_id: productId
        });

        return fetch(WCCL_Ajax.ajax_url, {
            method: 'POST',
            body: params.toString(),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw data;
                return data.data.html;
            });
    }

    /**
     * Add product to list via AJAX
     */
    function addProductToList(productId, listId, eventData = {}) {
        const params = new URLSearchParams({
            action: 'wccl_add_product_to_list',
            nonce: WCCL_Ajax.nonce,
            product_id: productId,
            list_id: listId
        });

        for (const key in eventData) {
            params.append(`event_data[${key}]`, eventData[key]);
        }

        return fetch(WCCL_Ajax.ajax_url, {
            method: 'POST',
            body: params.toString(),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw data;
                return data;
            });
    }
});
