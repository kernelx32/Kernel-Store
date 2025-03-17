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
    <title>Contact Us - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Contact Us</h1>
            <p class="lead text-center mb-6">Have questions? Get in touch with us!</p>
        </div>
    </section>
    
    <!-- Contact Form -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-lg mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                <h2 class="text-2xl font-bold mb-6 text-center">Send Us a Message</h2>
                <form action="contact.php" method="POST">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-400 mb-2">Name</label>
                        <input type="text" id="name" name="name" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-400 mb-2">Email</label>
                        <input type="email" id="email" name="email" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block text-gray-400 mb-2">Message</label>
                        <textarea id="message" name="message" rows="5" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required></textarea>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Send Message</button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>