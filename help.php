
<?php

$pageTitle = 'Help & Support';
require_once __DIR__ . '/includes/header.php';

?>

<section class="py-12 md:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl md:text-5xl font-secondary text-primary-600 mb-3 scroll-animate-top" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">
                Help
                <span class="text-accent">&amp; Support</span>
            </h1>
            <p class="text-gray-600 text-sm md:text-base max-w-2xl mx-auto animate-slide-bottom">
                Find quick answers about orders, delivery, returns, payments, and your account.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 animate-slide-left">
                <div class="bg-white md:border md:rounded-2xl md:shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Links</h2>

                    <div class="space-y-2 text-sm">
                        <a href="#orders" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-receipt text-primary-600"></i>
                            <span>Orders</span>
                        </a>
                        <a href="#shipping" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-truck text-primary-600"></i>
                            <span>Shipping & Delivery</span>
                        </a>
                        <a href="#returns" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-undo text-primary-600"></i>
                            <span>Returns & Refunds</span>
                        </a>
                        <a href="#payments" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-credit-card text-primary-600"></i>
                            <span>Payments</span>
                        </a>
                        <a href="#account" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-user text-primary-600"></i>
                            <span>Account</span>
                        </a>
                        <a href="#contact" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-envelope text-primary-600"></i>
                            <span>Contact</span>
                        </a>
                    </div>

                    <div class="border-t mt-6 pt-6">
                        <div class="text-sm text-gray-700 font-semibold mb-2">Need more help?</div>
                        <a href="<?php echo BASE_URL; ?>contact-us.php" class="inline-flex items-center justify-center w-full bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-3 px-4 rounded-lg transition hover:shadow-sm">
                            <i class="fas fa-paper-plane mr-2"></i>Contact Us
                        </a>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 animate-slide-right">
                <div class="bg-white md:border md:rounded-2xl md:shadow-sm p-6 md:p-8">
                    <div id="orders" class="scroll-mt-28">
                        <h2 class="text-2xl font-bold text-gray-900">Orders</h2>
                        <div class="mt-4 space-y-4 text-sm text-gray-700 leading-relaxed">
                            <div>
                                <div class="font-semibold text-gray-900">How do I place an order?</div>
                                <div>Add products to your cart, proceed to checkout, choose your delivery address, and complete payment (or COD if available).</div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Where can I see my orders?</div>
                                <div>Login and open your account area to view order status and details.</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t my-8"></div>

                    <div id="shipping" class="scroll-mt-28">
                        <h2 class="text-2xl font-bold text-gray-900">Shipping & Delivery</h2>
                        <div class="mt-4 space-y-4 text-sm text-gray-700 leading-relaxed">
                            <div>
                                <div class="font-semibold text-gray-900">How long does delivery take?</div>
                                <div>Delivery timelines vary by location. Most orders are delivered within 2–7 business days after dispatch.</div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Can I change my delivery address after ordering?</div>
                                <div>If your order hasn’t been dispatched yet, contact support as soon as possible with your order details.</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t my-8"></div>

                    <div id="returns" class="scroll-mt-28">
                        <h2 class="text-2xl font-bold text-gray-900">Returns & Refunds</h2>
                        <div class="mt-4 space-y-4 text-sm text-gray-700 leading-relaxed">
                            <div>
                                <div class="font-semibold text-gray-900">Do you accept returns?</div>
                                <div>Returns are accepted for eligible items in unused condition and original packaging, subject to our policy.</div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">When will I receive my refund?</div>
                                <div>Refunds (if approved) are processed to the original payment method. Processing time depends on your bank/payment provider.</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t my-8"></div>

                    <div id="payments" class="scroll-mt-28">
                        <h2 class="text-2xl font-bold text-gray-900">Payments</h2>
                        <div class="mt-4 space-y-4 text-sm text-gray-700 leading-relaxed">
                            <div>
                                <div class="font-semibold text-gray-900">Which payment methods are supported?</div>
                                <div>We support online payments (cards, UPI, net banking, wallets where available) and COD for eligible orders.</div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Is it safe to pay online?</div>
                                <div>Payments are processed through a secure payment gateway. We do not store your full card details on our servers.</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t my-8"></div>

                    <div id="account" class="scroll-mt-28">
                        <h2 class="text-2xl font-bold text-gray-900">Account</h2>
                        <div class="mt-4 space-y-4 text-sm text-gray-700 leading-relaxed">
                            <div>
                                <div class="font-semibold text-gray-900">How do I create an account?</div>
                                <div>Go to the Sign Up page, enter your details, and complete registration.</div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">I forgot my password—what should I do?</div>
                                <div>If password reset is available on your login page, use it. Otherwise contact support and we’ll assist you.</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t my-8"></div>

                    <div id="contact" class="scroll-mt-28">
                        <h2 class="text-2xl font-bold text-gray-900">Contact</h2>
                        <div class="mt-4 text-sm text-gray-700 leading-relaxed">
                            For any questions, please use our contact form.
                            <div class="mt-4">
                                <a href="<?php echo BASE_URL; ?>contact-us.php" class="inline-flex items-center bg-accent hover:bg-accent-800 text-white text-sm font-semibold py-3 px-6 rounded-lg transition hover:shadow-sm">
                                    <i class="fas fa-envelope mr-2"></i>Open Contact Page
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/floating-icons.php'; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

