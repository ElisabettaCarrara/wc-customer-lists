/**
 * WC Customer Lists - Frontend Script
 *
 * Handles:
 * - Product modal interactions
 * - Add product to list
 * - My Account list CRUD actions
 *
 * Uses vanilla JS with event delegation for WooCommerce compatibility.
 */

document.addEventListener( 'DOMContentLoaded', function () {

	'use strict';

	/* global WCCL_Ajax, WCCL_MyAccount */

	if ( typeof WCCL_Ajax === 'undefined' ) {
		console.error( 'WC Customer Lists: AJAX configuration missing.' );
		return;
	}

	initProductModal();
	initMyAccountHandlers();

	window.WCCL = window.WCCL || {};
	window.WCCL.showNotification = showNotification;

} );

/**
 * Display notification toast.
 *
 * @param {string} message Message to display.
 * @param {string} type Notification type.
 */
function showNotification( message, type ) {

	type = type || 'success';

	const notification = document.createElement( 'div' );
	notification.className = 'wccl-notification wccl-' + type;

	notification.innerHTML =
		'<span>' + message + '</span>' +
		'<button type="button" class="wccl-notification-close">&times;</button>';

	document.body.appendChild( notification );

	setTimeout( function () {
		if ( notification ) {
			notification.remove();
		}
	}, 5000 );

	const closeButton = notification.querySelector( '.wccl-notification-close' );

	if ( closeButton ) {
		closeButton.addEventListener( 'click', function () {
			notification.remove();
		} );
	}

}

/**
 * Initialize product modal system.
 */
function initProductModal() {

	const modal = document.getElementById( 'wc-customer-lists-modal' );

	if ( ! modal ) {
		return;
	}

	const modalContent   = modal.querySelector( '.wccl-modal-body' );
    const closeButton    = modal.querySelector( '.wccl-modal-close' );  // ✅ Fixed
    const overlay        = modal.querySelector( '.wccl-modal-overlay' ); 
    const submitButton   = modal.querySelector( '.wccl-submit-btn' );   // ✅ Already good

	let currentProductId = null;

	/**
	 * Open modal.
	 */
	function openModal() {

		modal.classList.add( 'is-active' );
		modal.setAttribute( 'aria-hidden', 'false' );

	}

	/**
	 * Close modal.
	 */
	function closeModal() {

		modal.classList.remove( 'is-active' );
		modal.setAttribute( 'aria-hidden', 'true' );

		currentProductId = null;

		if ( modalContent ) {
			modalContent.innerHTML =
				'<p class="wc-customer-lists-loading">Loading lists...</p>';
		}

	}

	/**
	 * Fetch user lists via AJAX.
	 *
	 * @param {number} productId Product ID.
	 * @return {Promise}
	 */
	function fetchUserLists( productId ) {

		const params = new URLSearchParams();

		params.append( 'action', 'wccl_get_user_lists' );
		params.append( 'nonce', WCCL_Ajax.nonce );
		params.append( 'product_id', productId );

		return fetch(
			WCCL_Ajax.ajax_url,
			{
				method: 'POST',
				headers: {
					'Content-Type':
						'application/x-www-form-urlencoded',
				},
				body: params.toString(),
			}
		)
			.then( function ( response ) {

				if ( ! response.ok ) {
					throw new Error( 'Server error' );
				}

				return response.json();

			} )
			.then( function ( data ) {

				if ( ! data.success ) {
					throw data;
				}

				return data.data.html;

			} );

	}

	/**
	 * Handle product button clicks using event delegation.
	 */
	document.addEventListener( 'click', function ( event ) {

		const button = event.target.closest(
			'.wc-customer-lists-add-btn'
		);

		if ( ! button ) {
			return;
		}

		event.preventDefault();
		event.stopImmediatePropagation();

		const productId = parseInt(
			button.dataset.productId,
			10
		);

		if ( ! productId ) {
			return;
		}

		currentProductId = productId;

		if ( modalContent ) {
			modalContent.innerHTML =
				'<p class="wc-customer-lists-loading">Loading lists...</p>';
		}

		openModal();

		fetchUserLists( productId )
			.then( function ( html ) {

				if ( modalContent ) {
					modalContent.innerHTML = html;
				}

			} )
			.catch( function () {

				if ( modalContent ) {
					modalContent.innerHTML =
						'<p class="error">Failed to load lists.</p>';
				}

			} );

	} );

	/**
	 * Close button.
	 */
	if ( closeButton ) {
		closeButton.addEventListener(
			'click',
			function ( event ) {

				event.preventDefault();
				closeModal();

			}
		);
	}

	/**
	 * Overlay click closes modal.
	 */
	if ( overlay ) {
		overlay.addEventListener(
			'click',
			function () {

				closeModal();

			}
		);
	}

	/**
	 * Escape key closes modal.
	 */
	document.addEventListener(
		'keydown',
		function ( event ) {

			if (
				event.key === 'Escape' &&
				modal.classList.contains( 'open' )
			) {
				closeModal();
			}

		}
	);

	/**
	 * Submit add-to-list action.
	 */
	if ( submitButton ) {

		submitButton.addEventListener(
			'click',
			function ( event ) {

				event.preventDefault();

				if ( ! currentProductId ) {
					return;
				}

				const dropdown = modalContent.querySelector( 'select[name="wclistid"]' );

				if ( ! dropdown ) {
					showNotification( 'No lists available. Create one first.', 'error' );
					return;
				}

				if ( ! dropdown.value ) {
					showNotification( 'Please select a list.', 'error' );
					return;
				}

				const formData = new FormData();

				formData.append(
					'action',
					'wccl_add_product_to_list'
				);

				formData.append(
					'nonce',
					WCCL_Ajax.nonce
				);

				formData.append(
					'product_id',
					currentProductId
				);

				formData.append(
					'list_id',
					dropdown.value
				);

				fetch(
					WCCL_Ajax.ajax_url,
					{
						method: 'POST',
						body: formData,
					}
				)
					.then( function ( response ) {

						if ( ! response.ok ) {
							throw new Error(
								'Server error'
							);
						}

						return response.json();

					} )
					.then( function ( data ) {

						if ( data.success ) {

							showNotification(
								data.data.message
							);

							closeModal();

						} else {

							showNotification(
								data.data.message ||
									'Error adding product.',
								'error'
							);

						}

					} )
					.catch( function () {

						showNotification(
							'Network error.',
							'error'
						);

					} );

			}
		);

	}

}

/**
 * Initialize My Account page actions.
 */
function initMyAccountHandlers() {

	document.addEventListener( 'click', function ( event ) {

		if ( event.target.matches( '.toggle-list' ) ) {

			const listId = event.target.dataset.listId;
			const container =
				document.getElementById( 'list-' + listId );

			if ( ! container ) {
				return;
			}

			const hidden =
				container.style.display === 'none';

			container.style.display =
				hidden ? 'block' : 'none';

			event.target.textContent = hidden
				? 'Hide Products'
				: 'Show Products';

		}

		if ( event.target.matches( '.delete-list' ) ) {

			if (
				! confirm(
					'Delete this entire list?'
				)
			) {
				return;
			}

			deleteList(
				event.target.dataset.listId
			);

		}

		if ( event.target.matches( '.remove-item' ) ) {

			if (
				! confirm(
					'Remove this product from list?'
				)
			) {
				return;
			}

			removeProduct(
				event.target.dataset.listId,
				event.target.dataset.productId
			);

		}

	} );

}

/**
 * Delete list via AJAX.
 *
 * @param {number} listId List ID.
 */
function deleteList( listId ) {

	const formData = new FormData();

	formData.append(
		'action',
		'wccl_delete_list'
	);

	formData.append(
		'nonce',
		WCCL_Ajax.nonce
	);

	formData.append(
		'list_id',
		listId
	);

	fetch(
		WCCL_Ajax.ajax_url,
		{
			method: 'POST',
			body: formData,
		}
	)
		.then( function ( response ) {

			if ( ! response.ok ) {
				throw new Error(
					'Server error'
				);
			}

			return response.json();

		} )
		.then( function ( data ) {

			if ( data.success ) {

				const card =
					document.querySelector(
						'.wc-customer-lists-card[data-list-id="' +
							listId +
							'"]'
					);

				if ( card ) {
					card.remove();
				}

				showNotification(
					data.data.message
				);

			} else {

				showNotification(
					data.data.message ||
						'Delete failed.',
					'error'
				);

			}

		} )
		.catch( function () {

			showNotification(
				'Delete failed.',
				'error'
			);

		} );

}

/**
 * Remove product from list.
 *
 * @param {number} listId List ID.
 * @param {number} productId Product ID.
 */
function removeProduct( listId, productId ) {

	const formData = new FormData();

	formData.append(
		'action',
		'wccl_toggle_product'
	);

	formData.append(
		'nonce',
		WCCL_Ajax.nonce
	);

	formData.append(
		'list_id',
		listId
	);

	formData.append(
		'product_id',
		productId
	);

	fetch(
		WCCL_Ajax.ajax_url,
		{
			method: 'POST',
			body: formData,
		}
	)
		.then( function ( response ) {

			if ( ! response.ok ) {
				throw new Error(
					'Server error'
				);
			}

			return response.json();

		} )
		.then( function ( data ) {

			if ( data.success ) {

				const row =
					document.querySelector(
						'tr[data-product-id="' +
							productId +
							'"]'
					);

				if ( row ) {
					row.remove();
				}

				showNotification(
					data.data.message
				);

			} else {

				showNotification(
					data.data.message ||
						'Remove failed.',
					'error'
				);

			}

		} )
		.catch( function () {

			showNotification(
				'Remove failed.',
				'error'
			);

		} );

}
