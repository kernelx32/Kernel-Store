<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boosting Services - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Boosting Services</h1>
            <p class="lead text-center mb-6">Get professional boosting services for your favorite games!</p>
            <a href="#boosting-options" class="btn bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">Explore Options</a>
        </div>
    </section>
    
    <!-- Boosting Services Grid -->
    <section class="py-16" id="boosting-options">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-8 text-center">Our Boosting Services</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                    <div class="h-48 overflow-hidden relative bg-gray-700">
                        <!-- يمكن تضيفي صورة لو عندك، مثلاً: <img src="assets/images/boosting/rank.jpg" alt="Rank Boosting"> -->
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Rank Boosting</h3>
                        <p class="text-gray-400 mb-4">Improve your rank with our expert boosters.</p>
                        <div class="flex space-x-2">
                            <a href="#" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Learn More</a>
                            <a href="contact.php" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Contact Us</a>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                    <div class="h-48 overflow-hidden relative bg-gray-700">
                        <!-- يمكن تضيفي صورة لو عندك، مثلاً: <img src="assets/images/boosting/level.jpg" alt="Level Boosting"> -->
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Level Boosting</h3>
                        <p class="text-gray-400 mb-4">Level up quickly with our services.</p>
                        <div class="flex space-x-2">
                            <a href="#" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Learn More</a>
                            <a href="contact.php" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Contact Us</a>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                    <div class="h-48 overflow-hidden relative bg-gray-700">
                        <!-- يمكن تضيفي صورة لو عندك، مثلاً: <img src="assets/images/boosting/custom.jpg" alt="Custom Boosting"> -->
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Custom Boosting</h3>
                        <p class="text-gray-400 mb-4">Tailored boosting to your needs.</p>
                        <div class="flex space-x-2">
                            <a href="#" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Learn More</a>
                            <a href="contact.php" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>