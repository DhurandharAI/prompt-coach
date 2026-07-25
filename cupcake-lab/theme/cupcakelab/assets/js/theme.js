/**
 * Cupcake Lab theme behaviour.
 *
 * Two things only: the mobile nav, and the Quick Order dialog. No framework, no
 * jQuery dependency of our own -- the site has to stay quick on a phone opened
 * from an Instagram link, often on mobile data.
 */

(function () {
	'use strict';

	/* --------------------------------------------------------------- nav --- */

	var toggle = document.querySelector('.nav-toggle');
	var nav = document.getElementById('site-nav');

	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	/* ------------------------------------------------------- quick order --- */

	var modal = document.getElementById('cupcakelab-quick-order');

	if (!modal || typeof window.cupcakelabData === 'undefined') {
		return;
	}

	var data = window.cupcakelabData;
	var titleEl = modal.querySelector('[id="cupcakelab-quick-order-title"]');
	var sizesEl = modal.querySelector('[data-quick-order-sizes]');
	var submitEl = modal.querySelector('[data-quick-order-submit]');
	var statusEl = modal.querySelector('[data-quick-order-status]');
	var closeEl = modal.querySelector('[data-quick-order-close]');

	var state = { productId: 0, variationId: null, lastFocus: null };

	function setStatus(message, isError) {
		statusEl.textContent = message || '';
		statusEl.style.color = isError ? 'var(--brand)' : 'var(--muted)';
	}

	function open(productId, trigger) {
		state.productId = productId;
		state.variationId = null;
		state.lastFocus = trigger || document.activeElement;

		titleEl.textContent = '';
		sizesEl.innerHTML = '';
		submitEl.disabled = true;
		setStatus('');
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		closeEl.focus();

		fetch(data.restUrl + 'product/' + productId + '/options', {
			headers: { 'X-WP-Nonce': data.nonce }
		})
			.then(function (res) {
				if (!res.ok) {
					throw new Error('lookup failed');
				}
				return res.json();
			})
			.then(render)
			.catch(function () {
				setStatus(data.i18n.failed, true);
			});
	}

	function render(payload) {
		titleEl.textContent = payload.name;

		if (!payload.options || !payload.options.length) {
			setStatus(data.i18n.failed, true);
			return;
		}

		// A product with exactly one option needs no choice made.
		if (payload.options.length === 1) {
			state.variationId = payload.options[0].variationId;
			submitEl.disabled = false;
		}

		payload.options.forEach(function (option) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'quick-order__size';
			button.setAttribute('aria-pressed', payload.options.length === 1 ? 'true' : 'false');
			button.innerHTML =
				'<span></span><b></b>';
			button.querySelector('span').textContent = option.label;
			button.querySelector('b').textContent = option.priceHtml;

			button.addEventListener('click', function () {
				state.variationId = option.variationId;
				submitEl.disabled = false;
				setStatus('');
				sizesEl.querySelectorAll('.quick-order__size').forEach(function (other) {
					other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
				});
			});

			sizesEl.appendChild(button);
		});

		if (payload.options.length > 1) {
			sizesEl.querySelector('.quick-order__size').focus();
		}
	}

	function close() {
		modal.hidden = true;
		document.body.style.overflow = '';
		if (state.lastFocus && typeof state.lastFocus.focus === 'function') {
			state.lastFocus.focus();
		}
	}

	function addToCart() {
		if (state.variationId === null) {
			setStatus(data.i18n.pickOne, true);
			return;
		}

		submitEl.disabled = true;
		setStatus(data.i18n.adding);

		fetch(data.restUrl + 'cart/add', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': data.nonce
			},
			body: JSON.stringify({
				product_id: state.productId,
				variation_id: state.variationId || 0,
				quantity: 1
			})
		})
			.then(function (res) {
				return res.json().then(function (body) {
					if (!res.ok) {
						throw new Error(body && body.message ? body.message : data.i18n.failed);
					}
					return body;
				});
			})
			.then(function (body) {
				setStatus(data.i18n.added);

				// Let WooCommerce refresh the mini cart and header count.
				if (window.jQuery) {
					window.jQuery(document.body).trigger('wc_fragment_refresh');
				}

				window.location.href = body.cartUrl || data.cartUrl;
			})
			.catch(function (error) {
				submitEl.disabled = false;
				setStatus(error.message || data.i18n.failed, true);
			});
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-quick-order]');
		if (trigger) {
			event.preventDefault();
			open(parseInt(trigger.getAttribute('data-quick-order'), 10), trigger);
			return;
		}

		// Click on the backdrop, not the dialog.
		if (event.target === modal) {
			close();
		}
	});

	closeEl.addEventListener('click', close);
	submitEl.addEventListener('click', addToCart);

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !modal.hidden) {
			close();
		}
	});
})();
