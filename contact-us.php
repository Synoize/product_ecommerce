<?php

/**
 * Contact Us Page
 */

$pageTitle = 'Contact Us';
require_once 'includes/header.php';

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    $phone = preg_replace('/\\s+/', '', $phone);

    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($phone) || !preg_match('/^\\+[1-9]\\d{7,14}$/', $phone)) $errors[] = 'Valid phone number with country code is required (e.g. +919876543210)';
    if (empty($message)) $errors[] = 'Message is required';

    if (empty($errors)) {
        // Check if table exists, create if not
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL,
                phone VARCHAR(30) NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Insert message
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $message]);

            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Failed to send message. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<section class="py-12 md:py-20">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">
        <h1 class="text-3xl sm:text-5xl text-primary-600 leading-[1.2] mb-6 font-secondary animate-slide-top" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">
            Contact
            <span class="text-accent">
                Us
            </span>
        </h1>
        <p class="text-sm md:text-base text-gray-500 max-w-2xl mx-auto animate-slide-bottom">We'd love to hear from you. Get in touch with us.</p>
    </div>
</section>

<!-- Contact Content -->
<section class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-12">
    <div class="flex flex-col-reverse md:grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Contact Info -->
        <div class="lg:col-span-1 animate-slide-left">
            <div class="bg-white h-full">
                <h4 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-secondary" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">
                    Get In
                    <span class="text-accent">
                        Touch
                    </span>
                </h4>

                <!-- Address -->
                <div class="mb-6 flex items-start">
                    <div class="w-11 h-11 bg-red-100 rounded-2xl flex items-center justify-center mr-4 flex-shrink-0 shadow-sm">
                        <i class="fas fa-map-marker-alt text-red-500"></i>
                    </div>

                    <div>
                        <h6 class="font-bold text-gray-900 mb-1">Address</h6>
                        <p class="text-gray-600 text-sm leading-relaxed">
                           Address is Plot No. 04, Samta Colony, <br>
                            Pawanputra Society, Hudkeshwar Khurd, <br>
                            Outer Ring Road, Nagpur, Maharashtra, <br>
                            India  - 440037
                        </p>
                    </div>
                </div>

                <!-- Phone -->
                <div class="mb-6 flex items-start">
                    <div class="w-11 h-11 bg-green-100 rounded-2xl flex items-center justify-center mr-4 flex-shrink-0 shadow-sm">
                        <i class="fas fa-phone-alt text-green-500"></i>
                    </div>

                    <div>
                        <h6 class="font-bold text-gray-900 mb-1">Phone</h6>
                        <p class="text-gray-600 text-sm leading-relaxed">
                           +91 9665661603, +91 7218927236
                        </p>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-6 flex items-start">
                    <div class="w-11 h-11 bg-blue-100 rounded-2xl flex items-center justify-center mr-4 flex-shrink-0 shadow-sm">
                        <i class="fas fa-envelope text-blue-500"></i>
                    </div>

                    <div>
                        <h6 class="font-bold text-gray-900 mb-1">Email</h6>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            support@earthence.com <br>
                            info@earthence.com
                        </p>
                    </div>
                </div>

                <!-- Working Hours -->
                <div class="flex items-start">
                    <div class="w-11 h-11 bg-yellow-100 rounded-2xl flex items-center justify-center mr-4 flex-shrink-0 shadow-sm">
                        <i class="fas fa-clock text-yellow-500"></i>
                    </div>

                    <div>
                        <h6 class="font-bold text-gray-900 mb-1">Working Hours</h6>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Mon - Fri: 9AM - 6PM <br>
                            Sat: 10AM - 4PM <br>
                            Sun: Closed
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="lg:col-span-2 animate-slide-right">
            <div class="bg-white md:border md:rounded-2xl px-3 md:p-8">
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Thank you for your message! We'll get back to you soon.
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <ul class="mb-0 list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>contact-us.php" method="POST" id="contactForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                            <input type="text" name="name" required
                                value="<?php echo isset($_POST['name']) ? e($_POST['name']) : ''; ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-600 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" name="email" required
                                value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-600 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                            <input type="tel" name="phone" required
                                pattern="\+[0-9]{8,15}" maxlength="16" placeholder="e.g. +919876543210"
                                value="<?php echo isset($_POST['phone']) ? e($_POST['phone']) : ''; ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-600 transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                        <textarea name="message" rows="4" required
                            placeholder="How can we help you?"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-600 transition"><?php echo isset($_POST['message']) ? e($_POST['message']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="inline-flex items-center bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold py-4 px-8 rounded-lg transition hover:shadow-md animate-slide-pop">
                        <i class="fas fa-paper-plane mr-2"></i>Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    // Phone validation
    document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
        this.value = this.value.replace(/\s+/g, '');
        if (this.value.length > 0 && this.value[0] !== '+') {
            this.value = '+' + this.value.replace(/[^0-9]/g, '');
        } else {
            this.value = '+' + this.value.substring(1).replace(/[^0-9]/g, '');
        }
        this.value = this.value.slice(0, 16);
    });

    // Form validation
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        var phone = document.querySelector('input[name="phone"]');

        if (!/^\+[1-9]\d{7,14}$/.test(phone.value.replace(/\s+/g, ''))) {
            e.preventDefault();
            alert('Please enter a valid phone number with country code (e.g. +919876543210)');
            phone.focus();
            return false;
        }

        return true;
    });
</script>

<!-- FAQ Section -->
<section class="mt-20">

    <div class="relative max-w-5xl mx-auto px-4">

        <!-- Heading -->
        <div class="text-center mb-14">
            <h2 class="text-2xl sm:text-3xl font-secondary text-primary-600 leading-tight scroll-animate-top">
               
                <span class="text-accent" style="-webkit-text-stroke:1px black;">
                   
                </span>
            </h2>
            <h4 class="text-2xl sm:text-3xl text-primary-600 leading-[1.2] mb-6 font-secondary animate-slide-top" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.25);">
                Frequently
                <span class="text-accent">
                    Asked Questions
                </span>
            </h4>


            <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-sm md:text-base leading-relaxed scroll-animate-top">
                Everything you need to know about our premium makhana, spices, delivery, freshness, and healthy snacking experience.
            </p>
        </div>

        <!-- FAQ Items -->
        <div class="space-y-5">

            <!-- FAQ Item -->
            <div class="faq-item bg-white/80 backdrop-blur-xl border border-orange-100 rounded-lg overflow-hidden hover:shadow-sm transition duration-300 animate-slide-bottom">

                <button class="faq-btn w-full flex items-center justify-between gap-4 p-3 md:px-6 md:py-5 text-left">

                    <div class="flex items-center gap-4">

                        <div class="w-8 h-8 md:w-12 md:h-12 rounded-lg md:rounded-2xl bg-orange-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-seedling text-orange-500"></i>
                        </div>

                        <span class="font-semibold text-gray-900 text-start sm:text-base text-sm">
                            Are your makhana products healthy?
                        </span>

                    </div>

                    <i class="fas fa-plus faq-icon text-orange-500 transition duration-300"></i>

                </button>

                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500">
                    <div class="px-4 pb-3 md:px-6 md:pb-6 text-gray-600 text-xs md:text-sm leading-relaxed">
                        Yes! Our makhana is rich in protein, fiber, and antioxidants, making it a perfect guilt-free snack for every age group. We focus on freshness, taste, and nutrition in every pack.
                    </div>
                </div>

            </div>

            <!-- FAQ Item -->
            <div class="faq-item bg-white/80 backdrop-blur-xl border border-red-100 rounded-lg overflow-hidden hover:shadow-sm transition duration-300 animate-slide-bottom">

                <button class="faq-btn w-full flex items-center justify-between gap-4 p-3 md:px-6 md:py-5">

                    <div class="flex items-center gap-4">

                        <div class="w-8 h-8 md:w-12 md:h-12 rounded-lg md:rounded-2xl bg-red-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-pepper-hot text-red-500"></i>
                        </div>

                        <span class="font-semibold text-gray-900 text-start sm:text-base text-sm">
                            Are your spices pure and authentic?
                        </span>

                    </div>

                    <i class="fas fa-plus faq-icon text-red-500 transition duration-300"></i>

                </button>

                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500">
                    <div class="px-4 pb-3 md:px-6 md:pb-6 text-gray-600 text-xs md:text-sm leading-relaxed">
                        Absolutely! Our spices are carefully sourced and hygienically packed to preserve natural aroma, flavor, and purity without compromising quality.
                    </div>
                </div>

            </div>

            <!-- FAQ Item -->
            <div class="faq-item bg-white/80 backdrop-blur-xl border border-green-100 rounded-lg overflow-hidden hover:shadow-sm transition duration-300 animate-slide-bottom">

                <button class="faq-btn w-full flex items-center justify-between gap-4 p-3 md:px-6 md:py-5">

                    <div class="flex items-center gap-4">

                        <div class="w-8 h-8 md:w-12 md:h-12 rounded-lg md:rounded-2xl bg-green-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-truck text-green-500"></i>
                        </div>

                        <span class="font-semibold text-gray-900 text-start sm:text-base text-sm">
                            How long does delivery take?
                        </span>

                    </div>

                    <i class="fas fa-plus faq-icon text-green-500 transition duration-300"></i>

                </button>

                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500">
                    <div class="px-4 pb-3 md:px-6 md:pb-6 text-gray-600 text-xs md:text-sm leading-relaxed">
                        Orders are usually delivered within 2–7 business days depending on your location. You will receive tracking updates once your order is shipped.
                    </div>
                </div>

            </div>

            <!-- FAQ Item -->
            <div class="faq-item bg-white/80 backdrop-blur-xl border border-blue-100 rounded-lg overflow-hidden hover:shadow-sm transition duration-300 animate-slide-bottom">

                <button class="faq-btn w-full flex items-center justify-between gap-4 p-3 md:px-6 md:py-5">

                    <div class="flex items-center gap-4">

                        <div class="w-8 h-8 md:w-12 md:h-12 rounded-lg md:rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-credit-card text-blue-500"></i>
                        </div>

                        <span class="font-semibold text-gray-900 text-start sm:text-base text-sm">
                            Which payment methods are accepted?
                        </span>

                    </div>

                    <i class="fas fa-plus faq-icon text-blue-500 transition duration-300"></i>

                </button>

                <div class="faq-content max-h-0 overflow-hidden transition-all duration-500">
                    <div class="px-4 pb-3 md:px-6 md:pb-6 text-gray-600 text-xs md:text-sm leading-relaxed">
                        We accept UPI, debit/credit cards, net banking, wallets, and Cash on Delivery (COD) for eligible orders.
                    </div>
                </div>

            </div>

        </div>

    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", () => {

        const items = document.querySelectorAll(".faq-item");

        items.forEach(item => {

            const btn = item.querySelector(".faq-btn");
            const content = item.querySelector(".faq-content");
            const icon = item.querySelector(".faq-icon");

            btn.addEventListener("click", () => {

                const isOpen = content.style.maxHeight;

                // Close All
                document.querySelectorAll(".faq-content").forEach(c => {
                    c.style.maxHeight = null;
                });

                document.querySelectorAll(".faq-icon").forEach(i => {
                    i.classList.remove("rotate-45");
                });

                // Open Current
                if (!isOpen) {
                    content.style.maxHeight = content.scrollHeight + "px";
                    icon.classList.add("rotate-45");
                }

            });

        });

    });
</script>
<?php require_once 'includes/floating-icons.php'; ?>
<?php require_once 'includes/footer.php'; ?>