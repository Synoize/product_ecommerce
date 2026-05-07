<?php
/**
 * About Us Page
 */

$pageTitle = 'About Us';
require_once 'includes/header.php';
?>

<!-- About Hero -->
<section class="bg-gradient-to-r from-pink-100 to-purple-100 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">About WebStore</h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">Your trusted destination for quality products and exceptional shopping experience</p>
    </div>
</section>

<!-- About Content -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-20">
            <div>
                <img src="https://img.freepik.com/free-vector/ecommerce-checkout-laptop-concept-illustration_114360-8233.jpg" 
                     alt="About WebStore" class="w-full rounded-3xl shadow-xl">
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Who We Are</h2>
                <p class="text-gray-600 mb-4 leading-relaxed">WebStore is a leading e-commerce platform dedicated to providing customers with a seamless and enjoyable shopping experience. Founded with a vision to make quality products accessible to everyone, we have grown into a trusted marketplace serving thousands of satisfied customers.</p>
                <p class="text-gray-600 mb-6 leading-relaxed">Our team is passionate about curating the best products, ensuring competitive prices, and delivering exceptional customer service. We believe in building lasting relationships with our customers based on trust, transparency, and mutual respect.</p>
                <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3 px-6 rounded-full transition shadow-lg hover:shadow-xl">
                    <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
                </a>
            </div>
        </div>
        
        <!-- Mission & Vision -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-20">
            <div class="bg-white rounded-2xl shadow-md p-8">
                <div class="flex items-center mb-4">
                    <div class="w-14 h-14 bg-primary-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-bullseye text-2xl text-primary-500"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900">Our Mission</h4>
                </div>
                <p class="text-gray-600 leading-relaxed">To provide customers with a convenient, secure, and enjoyable online shopping experience while offering quality products at competitive prices. We strive to exceed customer expectations through exceptional service and continuous innovation.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-8">
                <div class="flex items-center mb-4">
                    <div class="w-14 h-14 bg-primary-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-eye text-2xl text-primary-500"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900">Our Vision</h4>
                </div>
                <p class="text-gray-600 leading-relaxed">To become the most trusted and preferred online shopping destination, known for our commitment to quality, customer satisfaction, and sustainable business practices. We envision a future where shopping is effortless and enjoyable for everyone.</p>
            </div>
        </div>
        
        <!-- Why Choose Us -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Choose Us</h2>
            <p class="text-gray-600">Discover what makes WebStore the preferred choice for online shoppers</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-20">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-3xl text-primary-500"></i>
                </div>
                <h5 class="text-lg font-bold text-gray-900 mb-2">Secure Shopping</h5>
                <p class="text-gray-600 text-sm">Your security is our priority. We use advanced encryption and secure payment gateways to protect your data.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shipping-fast text-3xl text-primary-500"></i>
                </div>
                <h5 class="text-lg font-bold text-gray-900 mb-2">Fast Delivery</h5>
                <p class="text-gray-600 text-sm">We partner with reliable logistics providers to ensure your orders reach you quickly and safely.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-undo text-3xl text-primary-500"></i>
                </div>
                <h5 class="text-lg font-bold text-gray-900 mb-2">Easy Returns</h5>
                <p class="text-gray-600 text-sm">Not satisfied? Our hassle-free 30-day return policy ensures you can shop with confidence.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-3xl text-primary-500"></i>
                </div>
                <h5 class="text-lg font-bold text-gray-900 mb-2">24/7 Support</h5>
                <p class="text-gray-600 text-sm">Our dedicated customer support team is available round the clock to assist you with any queries.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-tags text-3xl text-primary-500"></i>
                </div>
                <h5 class="text-lg font-bold text-gray-900 mb-2">Best Prices</h5>
                <p class="text-gray-600 text-sm">We offer competitive prices and regular promotions to help you get the best value for your money.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-award text-3xl text-primary-500"></i>
                </div>
                <h5 class="text-lg font-bold text-gray-900 mb-2">Quality Guaranteed</h5>
                <p class="text-gray-600 text-sm">Every product in our store is carefully selected and verified to meet our high quality standards.</p>
            </div>
        </div>
        
        <!-- Team Section -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Meet Our Team</h2>
            <p class="text-gray-600">The passionate people behind WebStore</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <img src="https://ui-avatars.com/api/?name=John+Doe&background=f84183&color=fff&size=128" 
                     class="w-24 h-24 rounded-full mx-auto mb-4" alt="Team Member">
                <h5 class="font-bold text-gray-900 mb-1">John Doe</h5>
                <p class="text-primary-500 mb-2">Founder & CEO</p>
                <p class="text-gray-600 text-sm">Visionary leader with 15+ years of experience in e-commerce and retail.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <img src="https://ui-avatars.com/api/?name=Jane+Smith&background=f84183&color=fff&size=128" 
                     class="w-24 h-24 rounded-full mx-auto mb-4" alt="Team Member">
                <h5 class="font-bold text-gray-900 mb-1">Jane Smith</h5>
                <p class="text-primary-500 mb-2">Operations Manager</p>
                <p class="text-gray-600 text-sm">Expert in supply chain management and customer experience optimization.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md p-6 text-center">
                <img src="https://ui-avatars.com/api/?name=Mike+Johnson&background=f84183&color=fff&size=128" 
                     class="w-24 h-24 rounded-full mx-auto mb-4" alt="Team Member">
                <h5 class="font-bold text-gray-900 mb-1">Mike Johnson</h5>
                <p class="text-primary-500 mb-2">Tech Lead</p>
                <p class="text-gray-600 text-sm">Technology enthusiast driving innovation and platform development.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-16 bg-gradient-to-r from-pink-100 to-purple-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to Start Shopping?</h2>
        <p class="text-gray-600 mb-6">Explore our wide range of products and experience the WebStore difference today.</p>
        <a href="<?php echo BASE_URL; ?>shop.php" class="inline-flex items-center bg-primary-500 hover:bg-primary-600 text-white font-semibold py-4 px-8 rounded-full transition shadow-lg hover:shadow-xl">
            <i class="fas fa-shopping-bag mr-2"></i>Browse Products
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
