<?php
    require_once __DIR__ . '/../../../models/Icon.php';

    $page->setTitle('Help Centre');

    /**
     * Help centre.
     *
     * Every question and answer from the original page is kept - they now live
     * in one array instead of 60 hand-written <details> blocks, so the markup
     * below is a single loop and a new question is one line of data.
     */
    $faq = [
        'account' => [
            'title' => 'Account',
            'icon'  => 'user',
            'items' => [
                ['How do I create an account?', 'Click the Sign Up or Register button, fill in the required information, and submit the registration form. Once your account is created, you can sign in using your registered email and password.'],
                ['How do I sign in?', 'Click the Sign In or Login button, enter your email address and password, then click Sign In to access your account.'],
                ['I forgot my password. What should I do?', 'Click Forgot Password on the login page, enter your registered email address, and follow the instructions to reset your password.'],
                ['How do I update my account information?', 'Go to My Account or Profile, edit your information, and click Save Changes.'],
                ['How do I change my password?', 'Go to Account Settings, select Change Password, enter your current password and your new password, then save your changes.'],
                ['Can I change my registered email address?', 'Yes. Update your email address in Account Settings and save the changes. Email verification may be required.'],
                ["Why can't I sign in to my account?", "Make sure your email and password are correct. If you still can't sign in, reset your password or contact customer support."],
                ['How do I verify my account?', 'Check your email after registration and click the verification link to activate your account.'],
                ['Can I use my account on multiple devices?', "Yes. You can sign in using the same account on multiple devices, depending on the system's security settings."],
                ['How do I delete my account?', 'Go to Account Settings and choose Delete Account, or contact customer support if the option is unavailable.'],
            ],
        ],
        'products' => [
            'title' => 'Games & products',
            'icon'  => 'gamepad',
            'items' => [
                ['How do I search for a product?', 'Use the Search box at the top of the store page. Type the product name or keyword and press Enter or click the Search button.'],
                ['How do I view product details?', 'Click on a product image or product name to open its details page, where you can view its description, price, stock availability, and images.'],
                ['How do I select a product quantity?', 'On the product page, use the quantity selector or enter the desired number of items before adding the product to your cart.'],
                ['How do I know if a product is in stock?', 'Product availability is shown on each product page.'],
                ['Can I filter products by category?', 'Yes. Use the category filter to narrow down the products displayed.'],
                ['Can I sort products by price?', 'Yes. You can sort products by price, popularity, or newest arrivals.'],
                ['What should I do if a product is out of stock?', 'You can check back later or contact customer support for availability updates.'],
                ['Can I view multiple product images?', 'Yes. Most products include multiple images to help you view them from different angles.'],
                ['Are product prices updated automatically?', 'Yes. The prices displayed on the website reflect the latest available pricing.'],
                ['Can I compare different products?', 'You can compare products by viewing their descriptions, prices, and specifications on their product pages.'],
            ],
        ],
        'cart' => [
            'title' => 'Cart',
            'icon'  => 'cart',
            'items' => [
                ['How do I add a product to my cart?', 'Open the product page, choose the quantity if needed, then click the Add to Cart button. The item will be added to your shopping cart.'],
                ['How do I view my shopping cart?', 'Click the Cart icon, usually located at the top-right corner of the page, to view all items you have added.'],
                ['How do I change the quantity of an item in my cart?', 'Open your cart, adjust the quantity using the plus (+) or minus (-) buttons or by entering a new quantity, then save or update the cart if required.'],
                ['How do I remove an item from my cart?', 'Open your shopping cart and click the Remove or Delete button next to the item you no longer want.'],
                ['Will my cart be saved if I leave the website?', 'If you are signed in, your cart may be saved for future visits. If you are not signed in, your cart may be cleared depending on the system settings.'],
                ['Can I clear my shopping cart?', 'Yes. Use the Clear Cart option, if available, to remove all items at once.'],
                ['Why did an item disappear from my cart?', 'The item may have become unavailable or been removed from the store.'],
                ['Can I continue shopping after adding items to my cart?', 'Yes. Your items will remain in your cart while you continue browsing.'],
                ['Is there a limit to how many items I can add to my cart?', 'The maximum quantity depends on product availability and system limits.'],
                ['Can I save items in my cart for later?', 'If your system supports it, you can save items for later without purchasing them immediately.'],
            ],
        ],
        'checkout' => [
            'title' => 'Checkout & payment',
            'icon'  => 'tag',
            'items' => [
                ['How do I proceed to checkout?', 'After reviewing your cart, click the Checkout button to continue with your purchase.'],
                ['How do I place an order?', 'Review your cart, provide your shipping information, select a payment method, and click Place Order or Confirm Order.'],
                ['What payment methods are accepted?', 'Available payment methods depend on the system and may include credit/debit cards, online banking, digital wallets, or cash on delivery.'],
                ['Can I cancel my order?', 'Orders may be cancelled before they are processed. Go to My Orders, select the order, and click Cancel Order if the option is available.'],
                ['Can I change my shipping address before placing an order?', 'Yes. Update your shipping address during checkout before confirming your order.'],
                ['Can I apply a discount or promo code?', 'Yes. Enter your discount or promo code during checkout before completing your payment.'],
                ['Will I receive an order confirmation?', 'Yes. You will receive an order confirmation after your purchase is successfully completed.'],
                ['Can I edit my order before making payment?', 'Yes. You can return to your cart and update your order before completing payment.'],
                ['Is my payment information secure?', 'Yes. Your payment information is processed using secure payment methods to protect your data.'],
                ['Why was my payment declined?', 'This may happen because of incorrect payment details, insufficient funds, or bank restrictions.'],
            ],
        ],
        'orders' => [
            'title' => 'Orders',
            'icon'  => 'folder',
            'items' => [
                ['How can I check my order status?', 'Sign in to your account, go to My Orders, and view the current status of your order.'],
                ['Can I view my previous orders?', 'Yes. Go to My Orders or Order History to view all completed and previous purchases.'],
                ['How do I print or download my receipt?', 'Open your completed order and click Print Receipt or Download Receipt, if available.'],
                ['Can I cancel an order after placing it?', "You may cancel your order before it has been processed, depending on the store's policy."],
                ['How do I track my order?', 'Open My Orders to view the current delivery or processing status of your order.'],
                ['Can I reorder a previous purchase?', 'Yes. Open your order history and select the items you want to purchase again.'],
                ['What should I do if I receive the wrong item?', 'Contact customer support immediately and provide your order number and details of the incorrect item.'],
                ['What should I do if my order is delayed?', 'Check your order status first. If the delay continues, contact customer support for assistance.'],
                ['Can I request an invoice for my order?', 'Yes. If available, you can download or request an invoice from your order details.'],
                ['How will I know when my order has been shipped?', 'You will receive a notification or email once your order has been shipped.'],
            ],
        ],
        'general' => [
            'title' => 'General',
            'icon'  => 'info',
            'items' => [
                ["Why can't I add a product to my cart?", 'This may happen because the product is out of stock, the selected quantity exceeds the available stock, or there is a temporary system issue.'],
                ["Why can't I complete my purchase?", 'Check that all required information has been entered correctly, ensure your payment method is valid, and verify that your internet connection is stable.'],
                ['How do I sign out?', 'Click your profile icon or account menu and select Log Out to securely end your session.'],
                ['Who should I contact if I need assistance?', 'If you experience any issues while using the system, please contact the administrator or customer support team for assistance.'],
                ['Which web browsers are supported?', 'The website works best on the latest versions of Chrome, Firefox, Microsoft Edge, and Safari.'],
                ['Why is the website loading slowly?', 'This may be caused by a slow internet connection, browser issues, or temporary server maintenance.'],
                ['How do I refresh the page?', "Press F5 or click your browser's Refresh button to reload the page."],
                ['Is my personal information kept private?', "Yes. Your personal information is protected and handled according to the website's privacy policy."],
                ['How can I report a problem with the website?', 'Contact customer support and describe the issue, including any error messages if possible.'],
                ['Is an internet connection required to use the website?', 'Yes. A stable internet connection is required to browse products, manage your account, and place orders.'],
            ],
        ],
    ];

    $questionCount = array_sum(array_map(fn(array $section) => count($section['items']), $faq));
?>

<div class="page support-page">
    <header class="support-hero reveal">
        <span class="eyebrow">Help centre</span>
        <h1 class="contact-title">How can we <span class="gradient-text">help?</span></h1>
        <p class="page-subtitle">
            <?= $questionCount ?> answers covering accounts, games, carts, payments and orders.
            Start typing to jump straight to yours.
        </p>

        <div class="search-box support-search">
            <span class="search-icon"><?= Icon::get('search', 17) ?></span>
            <input type="search" id="faq-search" class="field-input"
                   placeholder="Search the help centre…" autocomplete="off"
                   aria-label="Search the help centre">
        </div>
    </header>

    <nav class="support-nav reveal" aria-label="Help topics">
        <?php foreach ($faq as $key => $section): ?>
            <a class="support-chip" href="#faq-<?= $key ?>">
                <?= Icon::get($section['icon'], 15) ?> <?= htmlspecialchars($section['title']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="support-sections">
        <?php foreach ($faq as $key => $section): ?>
            <section class="support-section card reveal" id="faq-<?= $key ?>">
                <header class="support-section-head">
                    <span class="support-section-icon"><?= Icon::get($section['icon'], 20) ?></span>
                    <h2 class="section-title"><?= htmlspecialchars($section['title']) ?></h2>
                    <span class="badge"><?= count($section['items']) ?></span>
                </header>

                <div class="faq-list">
                    <?php foreach ($section['items'] as [$question, $answer]): ?>
                        <details class="faq-item">
                            <summary class="faq-question">
                                <span><?= htmlspecialchars($question) ?></span>
                                <?= Icon::get('chevron-down', 17) ?>
                            </summary>
                            <p class="faq-answer"><?= htmlspecialchars($answer) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="empty-state" id="faq-empty" hidden>
        No answer matches that yet.<br>
        <a href="<?= BASE_URL ?>/contact">Contact the team directly</a> and we will help.
    </div>

    <section class="support-cta card reveal">
        <div class="flex-col gap-2">
            <h2 class="section-title">Still stuck?</h2>
            <p class="text-muted text-body">
                Send us the details and we will get back to you within two working days.
            </p>
        </div>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Contact support</a>
    </section>
</div>

<script>
(() => {
    const search = document.getElementById('faq-search');
    const sections = Array.from(document.querySelectorAll('.support-section'));
    const empty = document.getElementById('faq-empty');

    /* Filters the questions in place: matching entries stay open so the answer
       is visible, empty sections hide themselves. */
    function filter() {
        const term = search.value.trim().toLowerCase();
        let matches = 0;

        sections.forEach(section => {
            let visibleInSection = 0;

            section.querySelectorAll('.faq-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                const hit = term === '' || text.includes(term);

                item.hidden = !hit;
                item.open = hit && term.length > 2;
                if (hit) visibleInSection++;
            });

            section.hidden = visibleInSection === 0;
            matches += visibleInSection;
        });

        empty.hidden = matches > 0;
    }

    search.addEventListener('input', WASD.debounce(filter, 180));
})();
</script>
