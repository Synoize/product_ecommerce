# WebStore - eCommerce Website

A complete, modern eCommerce website built with Core PHP, MySQL, Bootstrap 5, and MDB UI Kit.

## Features

### Frontend (User)
- **Home Page**: Hero section, featured products, categories, newsletter
- **Shop Page**: Product listing with filters (category, price, sort), search, pagination
- **Product Detail**: Image gallery, reviews, add to cart
- **Shopping Cart**: Update quantities, remove items, apply coupons
- **Checkout**: Billing/shipping form with mobile field, Razorpay payment integration
- **User Dashboard**: Profile management, order history
- **About Us**: Company information, team section
- **Contact Us**: Contact form with database storage

### Admin Panel
- **Dashboard**: Statistics, sales chart (Chart.js), recent orders
- **Products**: Add, edit, delete products with image gallery support
- **Categories**: Manage categories with image upload
- **Orders**: View orders, update status, payment details
- **Coupons**: Create discount coupons (percentage/fixed), usage limits
- **Users**: Manage registered users
- **Messages**: View contact form submissions

### Payment Integration
- **Razorpay**: Complete payment gateway integration with signature verification

## Tech Stack

- **Backend**: Core PHP (PDO), MySQL
- **Frontend**: HTML5, CSS3, JavaScript, jQuery
- **UI Framework**: Bootstrap 5, MDB UI Kit (Material Design)
- **Fonts**: Poppins, Montserrat (Google Fonts)
- **Icons**: Font Awesome
- **Charts**: Chart.js

## Installation

### Prerequisites
- XAMPP/WAMP/MAMP installed
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Steps

1. **Clone/Extract to XAMPP htdocs**
   ```
   Extract to: C:\xampp\htdocs\kd\
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database: `ecommerce_db`
   - Import `database.sql` file (schema only)
   - (Optional) Import `database_seed.sql` for sample data

3. **Configure Database Connection**
   - Edit `config/database.php` if needed
   - Default: host=localhost, user=root, password='', db=ecommerce_db

4. **Create Required Directories**
   The following directories should be writable:
   - `assets/images/products/`
   - `assets/images/categories/`
   - `assets/images/uploads/`
   - `assets/images/logo/`

5. **Access the Website**
   - Frontend: http://localhost/kd/
   - Admin Panel: http://localhost/kd/admin/login.php

### Default Login Credentials

**Admin:**
- Email: admin@webstore.com
- Password: admin123

## Folder Structure

```
kd/
├── admin/              # Admin panel files
├── assets/             # Static assets
│   ├── css/
│   ├── js/
│   └── images/
│       ├── products/   # Product images
│       ├── categories/ # Category images
│       ├── uploads/    # General uploads
│       └── logo/       # Site logo
├── config/             # Configuration files
├── includes/           # Header, footer, db connection
├── payments/           # Razorpay payment handlers
├── user/              # User account pages
├── about-us.php       # About page
├── cart.php           # Shopping cart
├── checkout.php       # Checkout page
├── contact-us.php     # Contact page
├── database.sql       # Database schema
├── index.php          # Home page
├── order-success.php  # Order confirmation
├── product.php        # Product detail
└── shop.php           # Product listing
```

## Key Features Explained

### Product Gallery
Products support multiple images via a JSON gallery field. The main product page displays a thumbnail gallery with click-to-change functionality.

### Coupon System
- Two types: Percentage discount or Fixed amount
- Minimum order amount requirement
- Maximum discount cap (for percentage coupons)
- Usage limit tracking
- Expiry date validation

### Razorpay Payment
- Server-side order creation
- Payment signature verification
- Automatic order creation on successful payment
- Stock decrement and cart clearing

### Shiprocket Shipping
- Automatic Shiprocket order creation after local order placement
- AWB assignment and courier tracking fields on admin/user order screens
- Webhook endpoint for live carrier status updates
- Admin fallback buttons for Create Shipment and Sync Now

### Image Handling
- Supports both external URLs (http/https) and local uploads
- Automatic path resolution based on image source
- Fallback to placeholder for missing images

## Security Features

- PDO prepared statements (SQL injection prevention)
- Password hashing (bcrypt)
- CSRF protection on forms
- XSS protection via output escaping
- File upload validation (type, size)
- Session-based authentication

## Customization

### Changing Primary Color
Edit `assets/css/style.css`:
```css
:root {
    --primary-color: #f84183;  /* Change this */
}
```

### Adding Logo
Place your logo at:
- `assets/images/logo/logo.png`

Logo will be automatically detected and displayed in the header.

### Razorpay Credentials
Edit `includes/razorpay.php`:
```php
define('RAZORPAY_KEY_ID', 'your_key_id');
define('RAZORPAY_KEY_SECRET', 'your_key_secret');
```

### Shiprocket Credentials
Edit `config/database.php` or set environment variables:
```php
SHIPROCKET_EMAIL
SHIPROCKET_PASSWORD
SHIPROCKET_PICKUP_LOCATION
SHIPROCKET_CHANNEL_ID
SHIPROCKET_WEBHOOK_TOKEN
```

Set this webhook URL in Shiprocket and send the token in the `x-api-key` header:
```text
http://your-domain/product_ecommerce/api/shipment_status_webhook.php
```

## Troubleshooting

### "Database connection failed"
- Check XAMPP MySQL service is running
- Verify credentials in `config/database.php`
- Ensure database `ecommerce_db` exists

### "Upload failed" errors
- Check directory permissions (777 for images folders)
- Verify PHP upload limits in php.ini
- Ensure GD extension is enabled

### Payment not working
- Verify Razorpay credentials
- Check if cURL extension is enabled
- Test with Razorpay test keys first

## License

This project is open source. Feel free to use and modify as needed.

## Support

For issues or questions, please contact support or create an issue in the repository.
