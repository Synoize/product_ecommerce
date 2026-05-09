
<?php

$pageTitle = 'Terms of Service';
require_once __DIR__ . '/includes/header.php';

?>

<section class="py-12 md:py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl md:text-5xl font-luckiest text-primary-600 mb-3 scroll-animate-top">
                Terms of
                <span class="text-accent" style="-webkit-text-stroke: 1px black;">Service</span>
            </h1>
            <p class="text-gray-600 text-sm md:text-base max-w-2xl mx-auto animate-slide-bottom">
                Please read these terms carefully before using our website and placing orders.
            </p>
        </div>

        <div class="mt-10 bg-white md:border md:rounded-2xl md:shadow-sm p-6 md:p-10 text-sm text-gray-700 leading-relaxed">
            <div class="space-y-8">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">1. Acceptance of Terms</h2>
                    <p class="mt-2">By accessing or using this website, you agree to be bound by these Terms of Service and all applicable laws and regulations.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">2. Eligibility</h2>
                    <p class="mt-2">You must be legally capable of entering into a binding contract under applicable law. If you use this website on behalf of an entity, you represent that you have authority to bind that entity.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">3. Account & Security</h2>
                    <p class="mt-2">You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">4. Orders & Pricing</h2>
                    <p class="mt-2">Product prices and availability are subject to change without notice. We reserve the right to refuse or cancel any order, including orders with incorrect pricing or stock information.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">5. Payments</h2>
                    <p class="mt-2">Payments are processed through secure payment gateways. We do not store complete payment card information on our servers.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">6. Shipping & Delivery</h2>
                    <p class="mt-2">Delivery timelines are estimates and may vary based on location, courier operations, and unforeseen circumstances. Ownership and risk may transfer upon delivery.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">7. Returns & Refunds</h2>
                    <p class="mt-2">Returns and refunds are subject to our return policy. Some products may be non-returnable due to hygiene or safety reasons.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">8. Prohibited Use</h2>
                    <p class="mt-2">You agree not to misuse the website, including attempting unauthorized access, interfering with functionality, or using the website for unlawful purposes.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">9. Intellectual Property</h2>
                    <p class="mt-2">All content on this website—including text, images, logos, and design—is owned by or licensed to us and is protected by applicable intellectual property laws.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">10. Limitation of Liability</h2>
                    <p class="mt-2">To the fullest extent permitted by law, we are not liable for indirect, incidental, special, or consequential damages arising out of your use of the website or products.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">11. Changes to These Terms</h2>
                    <p class="mt-2">We may update these Terms of Service from time to time. Continued use of the website after changes are posted constitutes acceptance of the updated terms.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">12. Contact</h2>
                    <p class="mt-2">If you have any questions about these terms, please contact us via the contact page.</p>
                    <div class="mt-4">
                        <a href="<?php echo BASE_URL; ?>contact-us.php" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-3 px-6 rounded-lg transition hover:shadow-sm">
                            <i class="fas fa-envelope mr-2"></i>Contact Us
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-10 text-xs text-gray-500">
                Last updated: <?php echo date('F j, Y'); ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

