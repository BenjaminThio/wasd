<?php
    require_once __DIR__ . '/../../lib/Auth.php';
    require_once __DIR__ . '/../../lib/View.php';
    require_once __DIR__ . '/../../models/Icon.php';

    if (!Auth::getCurrentUser()) {
        echo '<div class="page"><div class="empty-state">'
           . 'Sign in to check out.<br>'
           . '<a href="' . BASE_URL . '/sign-in">Sign in</a></div></div>';
        return;
    }
?>
<div class="page checkout-page">
    <header class="page-head reveal">
        <div class="flex-col gap-2">
            <span class="eyebrow">Almost yours</span>
            <h1 class="page-title">Checkout</h1>
        </div>
    </header>

    <div class="cart-layout" id="checkout-main">
        <div class="cart-list" id="checkout-list"></div>

        <aside class="order card reveal">
            <h2 class="section-title">Payment</h2>

            <div class="field">
                <label class="field-label" for="card-name">Name on card</label>
                <input type="text" id="card-name" class="field-input" placeholder="AHMAD BIN ALI"
                       autocomplete="cc-name">
            </div>

            <div class="field">
                <label class="field-label" for="card-number">Card number</label>
                <input type="text" id="card-number" class="field-input" placeholder="4111 1111 1111 1111"
                       inputmode="numeric" autocomplete="cc-number" maxlength="23">
            </div>

            <div class="checkout-pair">
                <div class="field">
                    <label class="field-label" for="card-expiry">Expiry</label>
                    <input type="text" id="card-expiry" class="field-input" placeholder="MM/YY"
                           inputmode="numeric" autocomplete="cc-exp" maxlength="5">
                </div>
                <div class="field">
                    <label class="field-label" for="card-cvv">CVV</label>
                    <input type="password" id="card-cvv" class="field-input" placeholder="123"
                           inputmode="numeric" autocomplete="cc-csc" maxlength="4">
                </div>
            </div>

            <div class="divider"></div>

            <div class="order-row">
                <span>Items</span>
                <span id="order-count">0 items</span>
            </div>
            <div class="order-row">
                <span>Price</span>
                <span id="order-price">RM0.00</span>
            </div>
            <div class="order-row">
                <span>Sale discount</span>
                <span class="order-discount" id="order-discount">-RM0.00</span>
            </div>

            <div class="divider"></div>

            <div class="order-row order-total">
                <span>Total</span>
                <span id="order-subtotal">RM0.00</span>
            </div>

            <button type="button" class="btn btn-primary btn-block" id="pay-btn" onclick="payNow()" disabled>
                Place order
            </button>
            <button type="button" class="btn btn-ghost btn-block" onclick="goToCart()">
                Back to cart
            </button>
        </aside>
    </div>

    <div class="receipt card reveal" id="receipt" hidden>
        <span class="eyebrow">Order complete</span>
        <h2 class="section-title">Thank you for your purchase</h2>

        <div class="order-row">
            <span>Games bought</span>
            <span id="paid-count">0 items</span>
        </div>
        <div class="order-row order-total">
            <span>Paid</span>
            <span id="paid-total">RM0.00</span>
        </div>

        <div class="divider"></div>

        <p class="text-body text-muted">
            They are in your library now, ready to download.
        </p>

        <div class="receipt-actions">
            <a class="btn btn-primary" href="<?= BASE_URL ?>/library">Open my library</a>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/store">Keep browsing</a>
        </div>
    </div>
</div>

<script>
(() => {
    const API = '/src/app/api/cart/index.php';
    const ICONS = <?= json_encode(View::platformIcons()) ?>;
    const LIMIT = 12;

    const list = document.getElementById('checkout-list');
    let offset = 0;

    function cardMarkup(item) {
        const cover = WASD.cover(item, 'list-card-media');

        const price = (item.discount > 0
            ? `<span class="magenta game-tag">-${item.discount}%</span>
               <span class="original">${WASD.money(item.price)}</span>`
            : '') + `<span class="current">${WASD.money(item.final_price)}</span>`;

        const tags = item.categories
            .map(name => `<span class="magenta game-tag">${WASD.escapeHtml(name)}</span>`).join('');

        return `<div class="list-card">
            ${cover}
            <div class="list-card-body">
                <div class="list-card-title">${WASD.escapeHtml(item.title)}</div>
                <div class="chip-list">${tags}</div>
                <div class="list-card-platforms">${item.platforms.map(p => ICONS[p] || '').join('')}</div>
            </div>
            <div class="list-card-price">${price}</div>
        </div>`;
    }

    function showTotals(totals) {
        document.getElementById('order-count').textContent =
            totals.items + ' item' + (totals.items === 1 ? '' : 's');
        document.getElementById('order-price').textContent = WASD.money(totals.price);
        document.getElementById('order-discount').textContent = '-' + WASD.money(totals.discount);
        document.getElementById('order-subtotal').textContent = WASD.money(totals.subtotal);
        document.getElementById('pay-btn').disabled = totals.items === 0;
    }

    async function loadAll() {
        list.innerHTML = WASD.skeletonRows(2);

        let more = true;
        let first = true;

        while (more) {
            const result = await WASD.api(`${API}?offset=${offset}&limit=${LIMIT}`);

            if (result.status === 204 || !result.data || !result.data.items.length) {
                if (first) {
                    list.innerHTML = `<div class="empty-state">
                        There is nothing to check out.<br>
                        <a href="${WASD.url('/store')}">Browse the store</a> to find a game first.
                    </div>`;
                    showTotals({ items: 0, price: 0, discount: 0, subtotal: 0 });
                }
                return;
            }

            if (first) list.innerHTML = '';
            first = false;

            list.insertAdjacentHTML('beforeend', result.data.items.map(cardMarkup).join(''));
            WASD.lazyImages(list);

            offset += result.data.items.length;
            showTotals(result.data.totals);
            more = result.data.items.length === LIMIT;
        }
    }

    window.goToCart = () => window.wasdNavigate(WASD.url('/cart'));

    /* ------------------------------------------------------ card validation */

    /*
       No card details are sent anywhere and nothing is stored, but the fields
       are still checked properly. A form that accepts "aaaa" as a card number
       and then reports success teaches the user nothing about what went wrong,
       and the checks below are the ones a real payment form would run before
       spending a round trip on a number that cannot be right.
    */

    /** Groups digits in fours as the user types, so a long number stays readable. */
    function formatCardNumber(value) {
        return value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim();
    }

    /** Inserts the slash in MM/YY once two digits are in. */
    function formatExpiry(value) {
        const digits = value.replace(/\D/g, '').slice(0, 4);
        return digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
    }

    /*
       The Luhn checksum. Every real card number satisfies it, so a typo in one
       digit is caught here rather than by the bank. Doubling every second digit
       from the right and subtracting 9 from anything over 9 must leave a total
       divisible by ten.
    */
    function passesLuhn(number) {
        const digits = number.replace(/\D/g, '');
        if (digits.length < 13 || digits.length > 19) return false;

        let sum = 0;
        let double = false;

        for (let i = digits.length - 1; i >= 0; i--) {
            let digit = Number(digits[i]);
            if (double) {
                digit *= 2;
                if (digit > 9) digit -= 9;
            }
            sum += digit;
            double = !double;
        }

        return sum % 10 === 0;
    }

    /** True when MM/YY is a real month that has not already passed. */
    function expiryIsValid(value) {
        const match = /^(\d{2})\/(\d{2})$/.exec(value.trim());
        if (!match) return false;

        const month = Number(match[1]);
        const year = 2000 + Number(match[2]);
        if (month < 1 || month > 12) return false;

        const now = new Date();
        // The card is good until the last day of its month, so compare against
        // the first day of the month after it.
        return new Date(year, month) > now;
    }

    const cardNumber = document.getElementById('card-number');
    const cardExpiry = document.getElementById('card-expiry');
    const cardCvv = document.getElementById('card-cvv');

    cardNumber.addEventListener('input', () => {
        cardNumber.value = formatCardNumber(cardNumber.value);
    });

    cardExpiry.addEventListener('input', () => {
        cardExpiry.value = formatExpiry(cardExpiry.value);
    });

    cardCvv.addEventListener('input', () => {
        cardCvv.value = cardCvv.value.replace(/\D/g, '').slice(0, 4);
    });

    // Each field reports its own problem the moment the user leaves it, rather
    // than saving up four complaints until the order button is pressed.
    const rules = {
        'card-name': [v => v.trim().length >= 2, 'Enter the name printed on the card.'],
        'card-number': [passesLuhn, 'That card number is not valid. Check the digits.'],
        'card-expiry': [expiryIsValid, 'Enter a future expiry date as MM/YY.'],
        'card-cvv': [v => /^\d{3,4}$/.test(v.trim()), 'The CVV is the 3 or 4 digits on the card.'],
    };

    function checkField(id, quiet) {
        const field = document.getElementById(id);
        const [test, message] = rules[id];
        const ok = test(field.value);

        // Nothing is marked wrong while the field is still empty and untouched.
        field.classList.toggle('is-invalid', !ok && field.value.trim() !== '');

        if (!ok && !quiet && field.value.trim() !== '') WASD.toast(message, 'error');
        return ok;
    }

    Object.keys(rules).forEach(id => {
        document.getElementById(id).addEventListener('blur', () => checkField(id, true));
    });

    window.payNow = async function () {
        let valid = true;
        let firstProblem = null;

        Object.keys(rules).forEach(id => {
            const field = document.getElementById(id);

            if (field.value.trim() === '') {
                field.classList.add('is-invalid');
                valid = false;
                firstProblem = firstProblem || ['Fill in every payment detail first.', field];
                return;
            }

            if (!checkField(id, true)) {
                valid = false;
                firstProblem = firstProblem || [rules[id][1], field];
            }
        });

        if (!valid) {
            WASD.toast(firstProblem[0], 'error');
            firstProblem[1].focus();
            return;
        }

        const button = document.getElementById('pay-btn');
        button.disabled = true;
        button.textContent = 'Placing order…';

        const result = await WASD.api(API, { json: { action: 'checkout' } });

        if (!result.ok || !result.data) {
            button.disabled = false;
            button.textContent = 'Place order';
            WASD.toast('The order could not be placed.', 'error');
            return;
        }

        document.getElementById('paid-count').textContent =
            result.data.bought + ' item' + (result.data.bought === 1 ? '' : 's');
        document.getElementById('paid-total').textContent = WASD.money(result.data.paid);

        document.getElementById('checkout-main').hidden = true;
        document.getElementById('receipt').hidden = false;

        window.wasdSetBadge?.('cart', 0);
    };

    loadAll();
})();
</script>
