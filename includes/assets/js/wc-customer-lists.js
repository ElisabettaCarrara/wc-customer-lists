document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('wc-customer-lists-modal');
    const modalContent = modal.querySelector('.wc-customer-lists-modal-content');
    const closeBtn = modal.querySelector('.modal-close-btn');
    const submitBtn = modal.querySelector('.modal-submit-btn');

    let currentProductId = null;

    // Open modal when any "Add to List" button is clicked
    document.querySelectorAll('.wc-customer-lists-add-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentProductId = this.dataset.productId;
            openModal();
        });
    });

    // Close modal
    closeBtn.addEventListener('click', function() {
        modal.close();
        modalContent.innerHTML = '';
    });

    // Close modal on ESC
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.open) {
            modal.close();
            modalContent.innerHTML = '';
        }
    });

    // Open modal function
    function openModal() {
        // Fetch user's lists via AJAX
        fetchUserLists(currentProductId)
            .then(html => {
                modalContent.innerHTML = html;
                modal.showModal();
            })
            .catch(err => {
                modalContent.innerHTML = '<p>Error loading lists. Please try again.</p>';
                modal.showModal();
                console.error(err);
            });
    }

    // Submit button handler
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();

        const selectedList = modalContent.querySelector('select[name="wc_list_id"]');
        if (!selectedList || !selectedList.value) {
            alert('Please select a list.');
            return;
        }

        const listId = selectedList.value;

        addProductToList(currentProductId, listId)
            .then(resp => {
                alert(resp.data.message);
                modal.close();
                modalContent.innerHTML = '';
            })
            .catch(err => {
                alert('Error adding product. Please try again.');
                console.error(err);
            });
    });

    /**
     * Fetch all lists for the current user
     * Returns HTML for dropdown + optional event fields
     */
    function fetchUserLists(productId) {
        return fetch(WCCL_Ajax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=wccl_get_user_lists&nonce=${WCCL_Ajax.nonce}&product_id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw data;
            return data.data.html;
        });
    }

    /**
     * Add product to selected list via AJAX
     */
    function addProductToList(productId, listId) {
        return fetch(WCCL_Ajax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=wccl_add_product_to_list&nonce=${WCCL_Ajax.nonce}&product_id=${productId}&list_id=${listId}`
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw data;
            return data;
        });
    }
});
