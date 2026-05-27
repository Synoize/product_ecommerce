
<?php

$pageTitle = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';

?>

<section class="py-12 md:py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl md:text-5xl font-luckiest text-primary-600 mb-3 scroll-animate-top" style="-webkit-text-stroke: 0.5px black;">
                Privacy
                <span class="text-accent">Policy</span>
            </h1>
            
            <p class="text-gray-600 text-sm md:text-base max-w-2xl mx-auto animate-slide-bottom">
                This policy explains what information we collect and how we use and protect it.
            </p>
        </div>

        <div class="mt-10 bg-white md:border md:rounded-2xl md:shadow-sm p-6 md:p-10 text-sm text-gray-700 leading-relaxed">
            <div class="space-y-8">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">1. Information We Collect</h2>
                    <p class="mt-2">We may collect information you provide directly, including your name, email address, phone number, delivery address, and order details. We may also collect basic technical data such as browser type, device information, and IP address for security and analytics.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">2. How We Use Your Information</h2>
                    <div class="mt-2 space-y-2">
                        <div>- To process orders, payments, delivery, and customer support.</div>
                        <div>- To communicate order updates, service messages, and support responses.</div>
                        <div>- To improve our website, products, and user experience.</div>
                        <div>- To prevent fraud and enhance security.</div>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">3. Cookies</h2>
                    <p class="mt-2">We may use cookies and similar technologies to keep you logged in, remember preferences, and understand site usage. You can control cookies through your browser settings.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">4. Payments</h2>
                    <p class="mt-2">Payments are processed through third-party payment gateways. We do not store complete card details on our servers. Payment providers may collect and process your data according to their own privacy policies.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">5. Sharing of Information</h2>
                    <p class="mt-2">We may share your information with trusted third parties only as needed to provide services (such as shipping partners and payment processors) or where required by law.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">6. Data Retention</h2>
                    <p class="mt-2">We retain information as long as necessary to provide services, meet legal requirements, resolve disputes, and enforce agreements.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">7. Security</h2>
                    <p class="mt-2">We take reasonable steps to protect your data. However, no method of transmission or storage is 100% secure.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">8. Your Choices</h2>
                    <p class="mt-2">You may update your profile information in your account. If you want to request deletion of your account or data (subject to legal requirements), please contact support.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">9. Changes to This Policy</h2>
                    <p class="mt-2">We may update this Privacy Policy from time to time. Continued use of the website after changes are posted indicates acceptance of the updated policy.</p>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-gray-900">10. Contact</h2>
                    <p class="mt-2">If you have questions about this Privacy Policy, please contact us.</p>
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

<?php require_once 'includes/floating-icons.php'; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

