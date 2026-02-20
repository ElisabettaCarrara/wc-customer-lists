/**
 * WC Customer Lists - Frontend JavaScript
 * Vanilla JS - Product Modal + My Account CRUD
 */

document.addEventListener('DOMContentLoaded', function () {
    // ========================================
    // PRODUCT MODAL (Add to List)
    // ========================================
    const modal = document.getElementById('wc-customer-lists-modal');
    if (modal) {
        initModal();
    }

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

/**
 * Show notification toast
 */
function showNotification(message, type) {
    type = type || 'success';
    const notification = document.createElement('div');
    notification.className = 'wccl-notification wccl-' + type;
    notification.innerHTML = '<span>' + message + '</span><button type="button" class="wccl-notification-close">&times;</button>';
    
    document.body.appendChild(notification);

    setTimeout(function() {
        notification.remove();
    }, 5000);
    
    const closeBtn = notification.querySelector('.wccl-notification-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            notification.remove();
        });
    }
}

function initModal() {
    const modal = document.getElementById('wc-customer-lists-modal');
    const modalContent = modal.querySelector('.wc-customer-lists-modal-content');
    let currentProductId = null;

    /**
     * Bind product buttons using EVENT DELEGATION
     * 
     * This listens at the document level and checks if the clicked element
     * is (or is inside) a button with .wc-customer-lists-add-btn class.
     * 
     * Benefits:
     * - Works with buttons present at page load AND dynamically added later
     * - Properly handles event bubbling without parent elements interfering
     * - Single listener instead of one per button (more efficient)
     */
    document.addEventListener('click', function(e) {
        // Use closest() to find the button, even if user clicked the icon inside
        const button = e.target.closest('.wc-customer-lists-add-btn');
        
        // If clicked element is not our button, exit early
        if (!button) return;
        
        // Prevent default behavior and stop event from bubbling to parent elements
        e.preventDefault();
        e.stopPropagation();
        
        // Extract product ID and open modal
        currentProductId = parseInt(button.dataset.productId, 10);
        if (currentProductId) {
            openModal();
        }
    });

    /**
     * Close + reset modal
     */
    function closeModal() {
        modal.close();
        modalContent.innerHTML = '<p class="wc-customer-lists-loading">Loading lists...</p>';
        currentProductId = null;
    }

    // Close button event
    const closeBtn = modal.querySelector('.modal-close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }
    
    // Overlay click event
    const overlay = modal.querySelector('.wc-customer-lists-modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeModal);
    }
    
    // Escape key event
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.hasAttribute('open')) {
            closeModal();
        }
    });

    /**
     * Open modal + fetch lists
     */
    function openModal() {
        modalContent.innerHTML = '<p class="wc-customer-lists-loading">Loading lists...</p>';
        
        fetchUserLists(currentProductId)
            .then(function(html) {
                modalContent.innerHTML = html;
                modal.showModal();

                const dropdown = modalContent.querySelector('select[name="wc_list_id"]');
                if (dropdown) {
                    dropdown.addEventListener('change', handleListChange);
                    handleListChange();
                }
            })
            .catch(function(error) {
                const errorMsg = error.data && error.data.message ? error.data.message : 'Error loading lists';
                modalContent.innerHTML = '<p class="error">' + errorMsg + '</p>';
                modal.showModal();
            });
    }

    /**
     * Event fields toggle when list selection changes
     */
    function handleListChange() {
        const dropdown = modalContent.querySelector('select[name="wc_list_id"]');
        const container = modalContent.querySelector('#wc_event_fields_container');
        
        if (!dropdown || !container) {
            return;
        }

        container.innerHTML = '';
        
        const selectedOption = dropdown.selectedOptions[0];
        const supportsEvents = selectedOption && selectedOption.dataset.supportsEvents === '1';
        
        if (!supportsEvents) {
            return;
        }

        const fields = [
            { id: 'event_name', label: 'Event Name *', type: 'text' },
            { id: 'event_date', label: 'Event Date *', type: 'date' },
            { id: 'closing_date', label: 'Closing Date *', type: 'date' },
            { id: 'delivery_deadline', label: 'Delivery Deadline *', type: 'date' }
        ];

        fields.forEach(function(field) {
            const div = document.createElement('div');
            div.className = 'wc-event-field';
            div.innerHTML = '<label for="' + field.id + '">' + field.label + '</label>' +
                          '<input id="' + field.id + '" name="' + field.id + '" type="' + field.type + '" required>';
            container.appendChild(div);
            
            requestAnimationFrame(function() {
                div.classList.add('show');
            });
        });
    }

    /**
     * Submit modal form to AJAX
     * 
     * Changed from listening on form submit to listening on button click.
     * This gives us more control over the submission process.
     */
    const submitBtn = modal.querySelector('.modal-submit-btn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const dropdown = modalContent.querySelector('select[name="wc_list_id"]');
            if (!dropdown || !dropdown.value) {
                showNotification('Please select a list.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'wccl_add_product_to_list');
            formData.append('nonce', WCCL_Ajax.nonce);
            formData.append('product_id', currentProductId);
            formData.append('list_id', dropdown.value);

            const eventInputs = modalContent.querySelectorAll('#wc_event_fields_container input');
            eventInputs.forEach(function(input) {
                if (input.value) {
                    formData.append('event_data[' + input.name + ']', input.value);
                }
            });

            fetch(WCCL_Ajax.ajax_url, { 
                method: 'POST', 
                body: formData 
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (data.success) {
                    showNotification(data.data.message);
                    closeModal();
                    if (window.location.hash !== '#wc-customer-lists-modal') {
                        window.location.reload();
                    }
                } else {
                    const errorMsg = data.data && data.data.message ? data.data.message : 'Failed to add.';
                    showNotification(errorMsg, 'error');
                }
            })
            .catch(function(error) {
                showNotification('Network error.', 'error');
            });
        });
    }

    /**
     * Fetch user's lists via AJAX
     * 
     * @param {number} productId - The product ID to fetch lists for
     * @returns {Promise<string>} - Promise resolving to HTML string of lists
     */
    function fetchUserLists(productId) {
        const params = new URLSearchParams({
            action: 'wccl_get_user_lists',
            nonce: WCCL_Ajax.nonce,
            product_id: productId
        });

        return fetch(WCCL_Ajax.ajax_url, {
            method: 'POST',
            body: params,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (!data.success) {
                throw data;
            }
            return data.data.html;
        });
    }
}

/**
 * Initialize My Account page handlers
 * 
 * Uses event delegation to handle:
 * - Toggle list visibility
 * - Delete entire lists
 * - Remove individual products from lists
 */
function initMyAccountHandlers() {
    document.addEventListener('click', function(e) {
        // Toggle list products
        if (e.target.matches('.toggle-list')) {
            const listId = e.target.dataset.listId;
            const container = document.querySelector('#list-' + listId);
            if (container) {
                const isHidden = container.style.display === 'none';
                container.style.display = isHidden ? 'block' : 'none';
                e.target.textContent = isHidden ? 'Hide Products' : 'Show Products';
            }
        }

        // Delete entire list
        if (e.target.matches('.delete-list')) {
            if (confirm('Delete this entire list?')) {
                deleteList(e.target.dataset.listId);
            }
        }

        // Remove single product from list
        if (e.target.matches('.remove-item')) {
            if (confirm('Remove this product from list?')) {
                removeProduct(e.target.dataset.listId, e.target.dataset.productId);
            }
        }
    });
}

/**
 * Delete an entire list via AJAX
 * 
 * @param {number} listId - The list ID to delete
 */
function deleteList(listId) {
    const formData = new FormData();
    formData.append('action', 'wccl_delete_list');
    formData.append('nonce', window.WCCL_MyAccount && window.WCCL_MyAccount.nonce ? window.WCCL_MyAccount.nonce : WCCL_Ajax.nonce);
    formData.append('list_id', listId);

    const ajaxUrl = window.WCCL_MyAccount && window.WCCL_MyAccount.ajax_url ? window.WCCL_MyAccount.ajax_url : WCCL_Ajax.ajax_url;

    fetch(ajaxUrl, { 
        method: 'POST', 
        body: formData 
    })
    .then(function(res) {
        return res.json();
    })
    .then(function(data) {
        if (data.success) {
            const card = document.querySelector('.wc-customer-lists-card:has(#list-' + listId + ')');
            if (card) {
                card.remove();
            }
            if (window.WCCL && window.WCCL.showNotification) {
                window.WCCL.showNotification(data.data.message);
            }
        } else {
            const errorMsg = data.data && data.data.message ? data.data.message : 'Delete failed.';
            if (window.WCCL && window.WCCL.showNotification) {
                window.WCCL.showNotification(errorMsg, 'error');
            }
        }
    })
    .catch(function(error) {
        if (window.WCCL && window.WCCL.showNotification) {
            window.WCCL.showNotification('Delete failed.', 'error');
        }
    });
}

/**
 * Remove a single product from a list via AJAX
 * 
 * @param {number} listId - The list ID
 * @param {number} productId - The product ID to remove
 */
function removeProduct(listId, productId) {
    const formData = new FormData();
    formData.append('action', 'wccl_toggle_product');
    formData.append('nonce', window.WCCL_MyAccount && window.WCCL_MyAccount.nonce ? window.WCCL_MyAccount.nonce : WCCL_Ajax.nonce);
    formData.append('list_id', listId);
    formData.append('product_id', productId);

    const ajaxUrl = window.WCCL_MyAccount && window.WCCL_MyAccount.ajax_url ? window.WCCL_MyAccount.ajax_url : WCCL_Ajax.ajax_url;

    fetch(ajaxUrl, { 
        method: 'POST', 
        body: formData 
    })
    .then(function(res) {
        return res.json();
    })
    .then(function(data) {
        if (data.success) {
            // Remove row
            const row = document.querySelector('tr[data-product-id="' + productId + '"]');
            if (row) {
                row.remove();
            }
            
            // Update item count
            const itemCountEl = document.querySelector('.item-count');
            if (itemCountEl && data.data && typeof data.data.item_count !== 'undefined') {
                itemCountEl.textContent = data.data.item_count;
            }
            
            if (window.WCCL && window.WCCL.showNotification) {
                window.WCCL.showNotification(data.data.message);
            }
        } else {
            const errorMsg = data.data && data.data.message ? data.data.message : 'Remove failed.';
            if (window.WCCL && window.WCCL.showNotification) {
                window.WCCL.showNotification(errorMsg, 'error');
            }
        }
    })
    .catch(function(error) {
        if (window.WCCL && window.WCCL.showNotification) {
            window.WCCL.showNotification('Remove failed.', 'error');
        }
    });
}
