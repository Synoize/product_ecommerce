<?php

/**
 * Header Template
 * Included on all frontend pages
 */

// Ensure session and database are loaded
require_once __DIR__ . '/../../includes/db_connect.php';

// Get cart count
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Get all categories for navigation
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Categories table might not exist yet
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>Earthence</title>
    <link rel="icon" href="<?= ASSETS_URL; ?>/public/favicon.ico">
    <link rel="stylesheet" href="./../assets/public/css/style.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#2E7D32',
                            50: '#e8f5e9',
                            100: '#c8e6c9',
                            200: '#a5d6a7',
                            300: '#81c784',
                            400: '#66bb6a',
                            500: '#4caf50',
                            600: '#43a047',
                            700: '#388e3c',
                            800: '#2e7d32',
                            900: '#1b5e20',
                        },
                        accent: {
                            DEFAULT: '#FBC02D',
                            50: '#fffde7',
                            100: '#fff9c4',
                            200: '#fff59d',
                            300: '#fff176',
                            400: '#ffee58',
                            500: '#ffeb3b',
                            600: '#fdd835',
                            700: '#fbc02d',
                            800: '#f9a825',
                            900: '#f57f17',
                        },
                        spice: {
                            DEFAULT: '#D84315',
                            500: '#D84315',
                            600: '#BF360C',
                        },
                        neutral: {
                            light: '#F9FBF7',
                            dark: '#1F2937',
                        }
                    },

                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    }
                }

            }
        }
    </script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 bg-slate-50 shadow-sm z-40">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>" class="flex items-center text-primary-500 font-bold text-xl">
                    <img src="<?php echo ASSETS_URL; ?>/public/logo.png" alt="logo" class="h-20">
                </a>

                <!-- Right Side Actions -->
                <div class="flex items-center space-x-4">
                    <!-- User Account -->
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group hidden md:block">

                            <!-- User Button -->
                            <button class="flex items-center gap-2 text-gray-700 hover:text-primary-600 transition">

                                <img
                                    src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'User'); ?>&background=66bb6a&color=fff"
                                    class="w-9 h-9 rounded-full object-cover border border-gray-200">

                                <span class="hidden sm:block text-sm font-medium">
                                    <?php echo e($_SESSION['user_name'] ?? 'User'); ?>
                                </span>

                                <i class="fas fa-chevron-down text-xs transition-transform duration-300 group-hover:rotate-180"></i>
                            </button>

                            <!-- Dropdown -->
                            <div class="
                absolute right-0 mt-3 w-52 
                bg-white rounded-lg border border-gray-100
                
                opacity-0 invisible translate-y-3
                transition-all duration-300 ease-out
                
                group-hover:opacity-100 
                group-hover:visible 
                group-hover:translate-y-0
            ">

                                <div class="py-2 text-sm">

                                    <a href="<?php echo BASE_URL; ?>user/profile.php"
                                        class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition">
                                        <i class="fas fa-user mr-3"></i> My Profile
                                    </a>

                                    <a href="<?php echo BASE_URL; ?>help.php"
                                        class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition">
                                        <i class="fas fa-question-circle mr-3"></i> Help & Support
                                    </a>

                                    <div class="border-t border-gray-100 my-1"></div>

                                    <a href="<?php echo BASE_URL; ?>user/logout.php"
                                        class="flex items-center px-4 py-2 text-red-500 hover:bg-red-50 transition">
                                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                                    </a>

                                </div>
                            </div>
                        </div>

                    <?php else: ?>

                        <!-- Login Button -->
                        <a href="<?php echo BASE_URL; ?>user/login.php"
                            class="
           bg-primary-500 hover:bg-primary-600 
           text-white px-5 py-2.5 rounded-full 
           text-sm font-medium 
           shadow-sm hover:shadow-md
           transition-all
           "> Register/Login
                        </a>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </nav>