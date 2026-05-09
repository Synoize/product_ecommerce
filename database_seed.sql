-- =========================================================
-- Database Seed Data
-- WebStore eCommerce Platform
-- Run this after database.sql
-- =========================================================

USE ecommerce_db;

-- =========================================================
-- USERS
-- =========================================================

-- Default admin user
-- Password: admin123

INSERT INTO `users`
(
    `name`,
    `email`,
    `mobile`,
    `password`,
    `role`,
    `status`,
    `address`,
    `city`,
    `state`,
    `pincode`,
    `created_at`
)
VALUES
(
    'Administrator',
    'admin@gmail.com',
    '9876543210',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    1,
    '123 Admin Street',
    'Mumbai',
    'Maharashtra',
    '400001',
    NOW()
);

-- =========================================================
-- ADDRESSES
-- =========================================================

INSERT INTO `addresses`
(
    `user_id`,
    `name`,
    `mobile`,
    `address`,
    `city`,
    `state`,
    `pincode`,
    `is_default`
)
VALUES
(
    1,
    'Administrator',
    '9876543210',
    '123 Admin Street',
    'Mumbai',
    'Maharashtra',
    '400001',
    1
);

-- =========================================================
-- CATEGORIES
-- =========================================================

INSERT INTO `categories`
(
    `name`,
    `image`
)
VALUES
('Snacks & Namkeen', 'snacks.png'),
('Masala & Spices', 'spices.png');

-- =========================================================
-- PRODUCTS
-- =========================================================

INSERT INTO `products`
(
    `name`,
    `description`,
    `ingredients`,
    `shipping_return`,
    `legal_mandatories`,
    `category_id`,
    `price`,
    `weight`,
    `original_price`,
    `stock`,
    `image`,
    `gallery`,
    `status`,
    `created_at`
)
VALUES

-- =========================================================
-- Snacks & Namkeen
-- =========================================================

(
    'Roasted Makhana Premium',
    'Crispy roasted fox nuts seasoned with Himalayan pink salt.',
    'Fox nuts, pink salt, edible oil.',
    'Orders shipped within 2-4 business days.',
    'Country of Origin: India.',
    1,
    149.00,
    '100g',
    249.00,
    100,
    'makhana-premium.png',
    '["makhana1.png","makhana2.png"]',
    1,
    NOW()
),

(
    'Masala Makhana',
    'Crunchy roasted makhana with spicy Indian masala.',
    'Fox nuts, spices, edible oil.',
    'Eligible for replacement if damaged.',
    'Packed hygienically.',
    1,
    159.00,
    '100g',
    259.00,
    80,
    'masala-makhana.png',
    '["masala1.png","masala2.png"]',
    1,
    NOW()
),

(
    'Aloo Bhujia',
    'Traditional crispy potato bhujia snack.',
    'Potato, gram flour, spices.',
    'Non-returnable after opening.',
    'Store in cool dry place.',
    1,
    129.00,
    '400g',
    199.00,
    150,
    'aloo-bhujia.png',
    '["bhujia1.png","bhujia2.png"]',
    1,
    NOW()
),

(
    'Moong Dal Namkeen',
    'Crunchy fried moong dal snack.',
    'Moong dal, edible oil, spices.',
    'Return accepted for damaged items.',
    'Best before 6 months.',
    1,
    139.00,
    '400g',
    219.00,
    120,
    'moongdal.png',
    '["moong1.png","moong2.png"]',
    1,
    NOW()
),

(
    'Khatta Meetha Mixture',
    'Sweet and tangy namkeen mixture.',
    'Sev, peanuts, raisins, spices.',
    'Replacement available if damaged.',
    'Contains peanuts.',
    1,
    149.00,
    '400g',
    229.00,
    85,
    'khatta-meetha.png',
    '["mix1.png","mix2.png"]',
    1,
    NOW()
),

-- =========================================================
-- Masala & Spices
-- =========================================================

(
    'Turmeric Powder Premium',
    'Organic Lakadong turmeric powder.',
    'Pure turmeric powder.',
    'Returns accepted only for sealed packs.',
    'No artificial colors added.',
    2,
    89.00,
    '250g',
    149.00,
    100,
    'turmeric.png',
    '["turmeric1.png","turmeric2.png"]',
    1,
    NOW()
),

(
    'Red Chili Powder',
    'Premium Kashmiri red chili powder.',
    'Ground Kashmiri chilies.',
    'Store in airtight container.',
    '100% pure spice.',
    2,
    129.00,
    '250g',
    199.00,
    80,
    'red-chilli.png',
    '["chilli1.png","chilli2.png"]',
    1,
    NOW()
),

(
    'Garam Masala',
    'Traditional Indian spice blend.',
    'Coriander, cumin, cloves.',
    'Replacement for damaged items only.',
    'Packed fresh.',
    2,
    149.00,
    '100g',
    229.00,
    90,
    'garam-masala.png',
    '["garam1.png","garam2.png"]',
    1,
    NOW()
),

(
    'Kitchen King Masala',
    'All-purpose curry masala.',
    'Mixed Indian spices.',
    'No returns after opening.',
    'Store in cool and dry place.',
    2,
    89.00,
    '100g',
    139.00,
    100,
    'kitchen-king.png',
    '["kitchen1.png","kitchen2.png"]',
    1,
    NOW()
),

(
    'Black Pepper Powder',
    'Freshly ground black pepper powder.',
    'Black pepper.',
    'Eligible for replacement if damaged.',
    'Strong aroma and flavor.',
    2,
    119.00,
    '100g',
    179.00,
    80,
    'black-pepper.png',
    '["pepper1.png","pepper2.png"]',
    1,
    NOW()
);

-- =========================================================
-- PRODUCT WEIGHTS
-- =========================================================

INSERT INTO `product_weights`
(
    `product_id`,
    `weight`,
    `price`,
    `stock`,
    `sort_order`
)
VALUES

(1, '100g', 149.00, 100, 0),
(1, '250g', 349.00, 80, 1),
(1, '500g', 649.00, 60, 2),

(2, '100g', 159.00, 80, 0),
(2, '250g', 379.00, 70, 1),

(3, '200g', 79.00, 100, 0),
(3, '400g', 129.00, 150, 1),

(4, '200g', 79.00, 100, 0),
(4, '400g', 139.00, 120, 1),

(5, '200g', 79.00, 70, 0),
(5, '400g', 149.00, 85, 1),

(6, '100g', 89.00, 100, 0),
(6, '250g', 159.00, 70, 1),

(7, '100g', 129.00, 90, 0),
(7, '250g', 229.00, 80, 1),

(8, '100g', 89.00, 100, 0),
(8, '250g', 149.00, 80, 1),

(9, '50g', 49.00, 100, 0),
(9, '100g', 89.00, 80, 1),

(10, '50g', 59.00, 100, 0),
(10, '100g', 119.00, 80, 1);

-- =========================================================
-- COUPONS
-- =========================================================

INSERT INTO `coupons`
(
    `code`,
    `type`,
    `value`,
    `min_order`,
    `max_discount`,
    `expiry_date`,
    `usage_limit`,
    `used_count`,
    `status`,
    `created_at`
)
VALUES

(
    'WELCOME10',
    'percent',
    10.00,
    500.00,
    500.00,
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    100,
    0,
    1,
    NOW()
),

(
    'FLAT10',
    'percent',
    10.00,
    200.00,
    50.00,
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    100,
    0,
    1,
    NOW()
);

-- =========================================================
-- ORDERS
-- =========================================================

INSERT INTO `orders`
(
    `user_id`,
    `total_amount`,
    `discount_amount`,
    `coupon_code`,
    `status`,
    `payment_method`,
    `payment_status`,
    `name`,
    `email`,
    `mobile`,
    `address`,
    `city`,
    `state`,
    `pincode`,
    `created_at`
)
VALUES
(
    1,
    599.00,
    50.00,
    'FLAT10',
    'delivered',
    'online',
    'paid',
    'Administrator',
    'admin@gmail.com',
    '9876543210',
    '123 Admin Street',
    'Mumbai',
    'Maharashtra',
    '400001',
    DATE_SUB(NOW(), INTERVAL 5 DAY)
);

-- =========================================================
-- ORDER ITEMS
-- =========================================================

INSERT INTO `order_items`
(
    `order_id`,
    `product_id`,
    `weight_id`,
    `weight`,
    `quantity`,
    `price`
)
VALUES

(1, 1, 1, '100g', 2, 149.00),
(1, 3, 6, '400g', 1, 129.00),
(1, 6, 11, '100g', 1, 89.00);

-- =========================================================
-- REVIEWS
-- =========================================================

INSERT INTO `reviews`
(
    `product_id`,
    `user_id`,
    `rating`,
    `comment`,
    `created_at`
)
VALUES

(
    1,
    1,
    5,
    'Best makhana I have tasted! Crispy and fresh.',
    DATE_SUB(NOW(), INTERVAL 5 DAY)
),

(
    8,
    1,
    4,
    'Kitchen King Masala tastes amazing.',
    DATE_SUB(NOW(), INTERVAL 3 DAY)
);

-- =========================================================
-- WISHLIST
-- =========================================================

INSERT INTO `wishlist`
(
    `user_id`,
    `product_id`
)
VALUES

(1, 1),
(1, 3),
(1, 6);

-- =========================================================
-- CONTACT MESSAGES
-- =========================================================

INSERT INTO `contact_messages`
(
    `name`,
    `email`,
    `phone`,
    `message`,
    `created_at`
)
VALUES

(
    'Rahul Sharma',
    'rahul@example.com',
    '9876543210',
    'I want to become a seller on your platform.',
    DATE_SUB(NOW(), INTERVAL 5 DAY)
),

(
    'Vikram Singh',
    'vikram@example.com',
    '9876543214',
    'Do you provide cash on delivery?',
    NOW()
);

-- =========================================================
-- HERO FEATURES
-- =========================================================

INSERT INTO `hero_features`
(
    `images`
)
VALUES
(
    '["pkg1.png","pkg2.png","pkg3.png"]'
);