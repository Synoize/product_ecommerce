--
-- Database Seed Data
-- WebStore eCommerce Platform
-- Run this after database.sql to populate sample data
--

USE ecommerce_db;

-- --------------------------------------------------------

--
-- Dumping data for table `users`
--

-- Default admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `mobile`, `password`, `role`, `status`, `address`, `city`, `state`, `pincode`, `created_at`) VALUES
('Administrator', 'admin@gmail.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '123 Admin Street', 'Mumbai', 'Maharashtra', '400001', NOW());

-- --------------------------------------------------------

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`name`, `image`) VALUES
('Snacks & Namkeen', 'snacks.png'),
('Masala & Spices', 'spices.png');

-- --------------------------------------------------------

--
-- Dumping data for table `products`
--

-- Snacks & Namkeen (Category 1)
INSERT INTO `products` (`name`, `description`, `category_id`, `price`, `original_price`, `stock`, `image`, `gallery`, `status`, `created_at`) VALUES
('Roasted Makhana Premium', 'Crispy roasted fox nuts (makhana) seasoned with Himalayan pink salt. High in protein, gluten-free, perfect healthy snack. 100g pack.', 1, 149.00, 249.00, 100, 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?w=400', NULL, 1, NOW()),
('Masala Makhana', 'Spicy and flavorful roasted makhana with authentic Indian masala. Crunchy low-calorie snack. 100g pack.', 1, 159.00, 259.00, 80, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Aloo Bhujia', 'Classic crispy potato noodles (bhujia) with traditional spices. Haldiram style taste. 400g pack.', 1, 129.00, 199.00, 150, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Moong Dal Namkeen', 'Crunchy fried moong dal with mild spices. Popular Indian tea-time snack. 400g pack.', 1, 139.00, 219.00, 120, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Chana Choor Garam', 'Spicy and tangy roasted chickpea flakes. Street style chana chor with masala. 500g pack.', 1, 119.00, 189.00, 90, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Kurkure Masala', 'Crunchy corn curls with desi masala flavor. Perfect party snack. 100g pack.', 1, 20.00, 35.00, 200, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Soya Sticks Crunchy', 'Crispy soya sticks with spicy seasoning. High protein crunchy snack. 200g pack.', 1, 89.00, 149.00, 75, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Peanut Chikki', 'Traditional gajak made with roasted peanuts and jaggery. Healthy winter sweet snack. 250g pack.', 1, 99.00, 159.00, 60, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Khatta Meetha Mixture', 'Sweet and sour mixture with sev, peanuts, and raisins. Popular namkeen mix. 400g pack.', 1, 149.00, 229.00, 85, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),
('Methi Mathri', 'Crispy fenugreek flavored savory crackers. Traditional Rajasthani snack. 500g pack.', 1, 179.00, 279.00, 70, 'https://images.unsplash.com/photo-1604467707321-70c1b85d7cd6?w=400', NULL, 1, NOW()),

-- Masala & Spices (Category 2)
('Turmeric Powder Premium', 'High-curcumin Lakadong turmeric powder. Organic, pure, and aromatic. 250g pack.', 2, 89.00, 149.00, 100, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Red Chili Powder', 'Premium Kashmiri red chili powder. Rich color, mild heat. Perfect for curries. 250g pack.', 2, 129.00, 199.00, 80, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Garam Masala', 'Authentic North Indian garam masala blend. Freshly ground whole spices. 100g pack.', 2, 149.00, 229.00, 90, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Cumin Seeds Whole', 'Premium quality whole jeera. Aromatic and flavorful. Essential for tadka. 100g pack.', 2, 79.00, 129.00, 110, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Coriander Powder', 'Freshly ground dhaniya powder. Pure and aromatic. No additives. 250g pack.', 2, 69.00, 119.00, 95, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Pav Bhaji Masala', 'Authentic Mumbai-style pav bhaji masala. Perfect blend of spices. 100g pack.', 2, 59.00, 99.00, 120, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Chole Masala', 'Punjabi style chole masala for rich and spicy chickpea curry. 100g pack.', 2, 69.00, 109.00, 85, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Kitchen King Masala', 'All-purpose curry masala. Perfect for everyday Indian cooking. 100g pack.', 2, 89.00, 139.00, 100, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Kasuri Methi', 'Dried fenugreek leaves. Authentic aroma for Indian curries and parathas. 50g pack.', 2, 49.00, 79.00, 75, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Mustard Seeds', 'Yellow mustard seeds (rai) for tempering and pickles. Fresh and potent. 100g pack.', 2, 39.00, 69.00, 130, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Cardamom Green Whole', 'Premium green elaichi whole. Aromatic and flavorful. 50g pack.', 2, 199.00, 299.00, 50, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW()),
('Black Pepper Powder', 'Freshly ground kali mirch. Strong aroma and pungent flavor. 100g pack.', 2, 119.00, 179.00, 80, 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400', NULL, 1, NOW());

UPDATE `products` p
JOIN `categories` c ON p.category_id = c.id
SET
  p.ingredients = CASE
    WHEN c.name LIKE '%Spice%' OR c.name LIKE '%Masala%' THEN CONCAT(p.name, '. Packed fresh to preserve natural aroma and flavor. Please check the product pack for the complete ingredient list.')
    ELSE CONCAT(p.name, ', seasoning, spices, and permitted ingredients as applicable. Please check the product pack for complete ingredients and allergen information.')
  END,
  p.shipping_return = 'Orders are usually shipped within 2-4 business days. Returns are accepted for eligible unopened products as per store policy.',
  p.legal_mandatories = CONCAT('Product: ', p.name, '\nCategory: ', c.name, '\nCountry of Origin: India\nPlease refer to the product packaging for manufacturer details, batch number, expiry date, MRP, net quantity, and other statutory information.')
WHERE p.ingredients IS NULL
   OR p.shipping_return IS NULL
   OR p.legal_mandatories IS NULL;

-- --------------------------------------------------------

--
-- Dumping data for table `product_weights`
--

-- Roasted Makhana Premium (Product ID 1)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(1, '100g', 149.00, 100, 0),
(1, '250g', 349.00, 80, 1),
(1, '500g', 649.00, 60, 2),
(1, '1kg', 1199.00, 40, 3);

-- Masala Makhana (Product ID 2)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(2, '100g', 159.00, 80, 0),
(2, '250g', 379.00, 70, 1),
(2, '500g', 699.00, 50, 2);

-- Aloo Bhujia (Product ID 3)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(3, '200g', 79.00, 100, 0),
(3, '400g', 129.00, 150, 1),
(3, '800g', 239.00, 80, 2),
(3, '1kg', 299.00, 50, 3);

-- Moong Dal Namkeen (Product ID 4)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(4, '200g', 79.00, 100, 0),
(4, '400g', 139.00, 120, 1),
(4, '800g', 259.00, 80, 2);

-- Chana Choor Garam (Product ID 5)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(5, '250g', 79.00, 90, 0),
(5, '500g', 119.00, 85, 1),
(5, '1kg', 219.00, 60, 2);

-- Kurkure Masala (Product ID 6)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(6, '40g', 15.00, 150, 0),
(6, '100g', 20.00, 200, 1),
(6, '200g', 35.00, 100, 2);

-- Soya Sticks Crunchy (Product ID 7)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(7, '100g', 49.00, 60, 0),
(7, '200g', 89.00, 75, 1),
(7, '400g', 159.00, 50, 2);

-- Peanut Chikki (Product ID 8)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(8, '100g', 49.00, 50, 0),
(8, '250g', 99.00, 60, 1),
(8, '500g', 179.00, 40, 2);

-- Khatta Meetha Mixture (Product ID 9)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(9, '200g', 79.00, 70, 0),
(9, '400g', 149.00, 85, 1),
(9, '800g', 279.00, 60, 2);

-- Methi Mathri (Product ID 10)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(10, '250g', 99.00, 60, 0),
(10, '500g', 179.00, 70, 1),
(10, '1kg', 329.00, 50, 2);

-- Turmeric Powder Premium (Product ID 11)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(11, '100g', 49.00, 80, 0),
(11, '250g', 89.00, 100, 1),
(11, '500g', 159.00, 60, 2),
(11, '1kg', 299.00, 40, 3);

-- Red Chili Powder (Product ID 12)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(12, '100g', 79.00, 70, 0),
(12, '250g', 129.00, 80, 1),
(12, '500g', 239.00, 50, 2);

-- Garam Masala (Product ID 13)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(13, '50g', 79.00, 70, 0),
(13, '100g', 149.00, 90, 1),
(13, '200g', 279.00, 60, 2);

-- Cumin Seeds Whole (Product ID 14)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(14, '50g', 39.00, 100, 0),
(14, '100g', 79.00, 110, 1),
(14, '250g', 189.00, 80, 2),
(14, '500g', 359.00, 50, 3);

-- Coriander Powder (Product ID 15)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(15, '100g', 39.00, 85, 0),
(15, '250g', 69.00, 95, 1),
(15, '500g', 129.00, 70, 2);

-- Pav Bhaji Masala (Product ID 16)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(16, '50g', 39.00, 100, 0),
(16, '100g', 59.00, 120, 1),
(16, '200g', 109.00, 80, 2);

-- Chole Masala (Product ID 17)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(17, '50g', 39.00, 75, 0),
(17, '100g', 69.00, 85, 1),
(17, '200g', 129.00, 60, 2);

-- Kitchen King Masala (Product ID 18)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(18, '50g', 49.00, 80, 0),
(18, '100g', 89.00, 100, 1),
(18, '200g', 169.00, 70, 2);

-- Kasuri Methi (Product ID 19)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(19, '20g', 29.00, 60, 0),
(19, '50g', 49.00, 75, 1),
(19, '100g', 89.00, 50, 2);

-- Mustard Seeds (Product ID 20)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(20, '50g', 25.00, 110, 0),
(20, '100g', 39.00, 130, 1),
(20, '250g', 89.00, 90, 2);

-- Cardamom Green Whole (Product ID 21)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(21, '25g', 99.00, 40, 0),
(21, '50g', 199.00, 50, 1),
(21, '100g', 379.00, 35, 2);

-- Black Pepper Powder (Product ID 22)
INSERT INTO `product_weights` (`product_id`, `weight`, `price`, `stock`, `sort_order`) VALUES
(22, '50g', 59.00, 65, 0),
(22, '100g', 119.00, 80, 1),
(22, '200g', 229.00, 60, 2);

-- --------------------------------------------------------

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`code`, `type`, `value`, `min_order`, `max_discount`, `expiry_date`, `usage_limit`, `used_count`, `status`, `created_at`) VALUES
('WELCOME10', 'percent', 10.00, 500.00, 500.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 100, 0, 1, NOW()),
('FLAT10', 'percent', 10.00, 200.00, 50.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 100, 0, 1, NOW());

-- --------------------------------------------------------

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 5, 'Best makhana I have tasted! Crispy and perfectly salted. Great healthy snack option.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(16, 1, 4, 'Pav bhaji masala is excellent! Tastes just like Mumbai street food.', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- --------------------------------------------------------

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`name`, `email`, `phone`, `message`, `created_at`) VALUES
('Rahul Sharma', 'rahul@example.com', '9876543210', 'I am interested in becoming a seller on your platform. Please provide more information about the vendor registration process.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('Vikram Singh', 'vikram@example.com', '9876543214', 'Do you offer cash on delivery option? I prefer paying after receiving the product.', NOW());


INSERT INTO hero_features (images)
VALUES 
(
    '["pkg1.png","pkg2.png","pkg3.png"]'
);