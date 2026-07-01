<?php
/**
 * StaySphere Property Rental System - Landing Page
 * Modern, responsive landing page built with PHP and Tailwind CSS
 * Based on the design guide provided
 */
session_start();
$isUserLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StaySphere - Find Available Properties Near You</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/premium-hero.css">
    <!-- Guest User Protection: Auto-redirect to login -->
    <meta name="user-authenticated" content="<?= $isUserLoggedIn ? 'true' : 'false' ?>">
</head>
<body class="bg-white" data-user-authenticated="<?= $isUserLoggedIn ? 'true' : 'false' ?>\">
    <?php include 'includes/navbar.php'; ?>

    <!-- Premium Property Showcase Hero Section -->
    <?php include 'includes/premium_hero_section.php'; ?>

    <!-- Why Choose StaySphere -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Why Choose StaySphere?</h2>
                <p class="text-xl text-gray-600">Everything you need for seamless property discovery</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Smart Property Discovery -->
                <div class="gradient-blue-light p-8 rounded-xl hover-shadow">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-search text-white text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Smart Property Discovery</h3>
                    <p class="text-gray-700">Search for available properties by location, category, and price with ease.</p>
                </div>
                
                <!-- Live Map Viewing -->
                <div class="gradient-green-light p-8 rounded-xl hover-shadow">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-map text-white text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Live Map Viewing</h3>
                    <p class="text-gray-700">Explore listings visually and understand where properties are located before contacting the owner.</p>
                </div>
                
                <!-- Direct Communication -->
                <div class="gradient-purple-light p-8 rounded-xl hover-shadow">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-comments text-white text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Direct Communication</h3>
                    <p class="text-gray-700">Chat directly with landlords through the platform without stress or delays.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- What Users Can Do -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Everything You Need to Find the Right Property</h2>
            </div>
            
            <div class="grid md:grid-cols-2 gap-12">
                <!-- For Property Seekers -->
                <div class="bg-white p-8 rounded-xl shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                        <h3 class="text-2xl font-bold text-gray-900">For Property Seekers</h3>
                    </div>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <span class="text-blue-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Discover nearby available properties</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-blue-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Search by city, type, price, and availability</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-blue-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Save favorite listings</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-blue-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">View listings on the map</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-blue-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Contact landlords instantly</span>
                        </li>
                    </ul>
                </div>
                
                <!-- For Landlords -->
                <div class="bg-white p-8 rounded-xl shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <i class="fas fa-shield-alt text-green-600 text-2xl"></i>
                        <h3 class="text-2xl font-bold text-gray-900">For Landlords</h3>
                    </div>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Add and manage property listings</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Show available rooms and property details</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Pin listings on the map</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Highlight featured or VIP listings</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-600 font-bold mt-1">✓</span>
                            <span class="text-gray-700">Communicate directly with interested clients</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">How StaySphere Works</h2>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="step-circle">1</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Search</h3>
                    <p class="text-gray-600">Browse properties by city, category, room type, or location.</p>
                </div>
                
                <!-- Step 2 -->
                <div class="text-center">
                    <div class="step-circle">2</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Explore</h3>
                    <p class="text-gray-600">View detailed property information, room availability, images, and map location.</p>
                </div>
                
                <!-- Step 3 -->
                <div class="text-center">
                    <div class="step-circle">3</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Connect</h3>
                    <p class="text-gray-600">Chat directly with the landlord and move forward easily.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Features Showcase -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Built for Modern Property Discovery</h2>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <i class="fas fa-map-marker-alt text-blue-600 text-3xl mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Location-based Search</h3>
                    <p class="text-gray-600 text-sm">Find properties near you instantly</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card">
                    <i class="fas fa-map text-blue-600 text-3xl mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Live Google Maps Integration</h3>
                    <p class="text-gray-600 text-sm">Visualize property locations</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card">
                    <i class="fas fa-heart text-blue-600 text-3xl mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Favorites System</h3>
                    <p class="text-gray-600 text-sm">Save and manage your favorites</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="feature-card">
                    <i class="fas fa-star text-blue-600 text-3xl mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Featured Listings</h3>
                    <p class="text-gray-600 text-sm">Discover premium properties</p>
                </div>
                
                <!-- Feature 5 -->
                <div class="feature-card">
                    <i class="fas fa-shield-alt text-blue-600 text-3xl mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">VIP Room Visibility</h3>
                    <p class="text-gray-600 text-sm">Highlight exclusive listings</p>
                </div>
                
                <!-- Feature 6 -->
                <div class="feature-card">
                    <i class="fas fa-comments text-blue-600 text-3xl mb-3"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Direct Landlord Chat</h3>
                    <p class="text-gray-600 text-sm">Real-time communication</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 gradient-blue text-white">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">A Simpler and More Reliable Way to Find Property</h2>
            <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                StaySphere is designed to make property discovery easier, faster, and more transparent by helping users locate available properties and connect directly with owners in one place.
            </p>
            
            <div class="grid md:grid-cols-2 gap-6 mt-12">
                <div class="text-left">
                    <p class="trust-item text-white"><span class="checkmark">✓</span> Direct owner contact</p>
                </div>
                <div class="text-left">
                    <p class="trust-item text-white"><span class="checkmark">✓</span> Real-time listing visibility</p>
                </div>
                <div class="text-left">
                    <p class="trust-item text-white"><span class="checkmark">✓</span> Location-based property discovery</p>
                </div>
                <div class="text-left">
                    <p class="trust-item text-white"><span class="checkmark">✓</span> Easy-to-use modern interface</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">Ready to Start Exploring?</h2>
            <p class="text-xl text-gray-600 mb-8">
                Sign in to discover available properties, save your favorites, view locations on the map, and connect directly with landlords on StaySphere.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/property_rental_system/register.php" class="btn-primary">
                    Get Started
                </a>
                <a href="/property_rental_system/login.php" class="btn-secondary">
                    Welcome Back!
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-4 gap-8 mb-12">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 gradient-blue rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold">S</span>
                        </div>
                        <span class="text-xl font-bold" style="color: #87CEEB;">StaySphere</span>
                    </div>
                    <p class="text-gray-400">Discover your perfect property and connect with landlords effortlessly.</p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4" style="color: #87CEEB;">Company</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">About Us</a></li>
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4" style="color: #87CEEB;">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4" style="color: #87CEEB;">Connect</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Twitter</a></li>
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">LinkedIn</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p style="color: white;">&copy; 2026 StaySphere. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Guest User Authentication Protection: Auto-redirect to login -->

    <script>
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('active');
        }
    </script>
    <script src="assets/js/premium-hero.js"></script>
    <script src="assets/js/guest-auth.js"></script>
</body>
</html>
