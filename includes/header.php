<?php
// session_start(); // شيلناها من هنا لأن الجلسة بتفعّل في admin/index.php
require_once dirname(__DIR__) . '/config/database.php'; // مسار نسبي صحيح
require_once __DIR__ . '/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/kernelstore/assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <nav class="bg-gray-800 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <span class="text-xl font-bold">K</span>
                <a href="/kernelstore/index.php" class="text-indigo-400 hover:text-indigo-300 text-xl font-bold">KernelStore</a>
            </div>
            <div class="flex space-x-4">
                <a href="/kernelstore/accounts.php" class="hover:text-indigo-300">Accounts</a>
                <a href="/kernelstore/boosting.php" class="hover:text-indigo-300">Boosting</a>
                <a href="/kernelstore/games.php" class="hover:text-indigo-300">Games</a>
                <a href="/kernelstore/support.php" class="hover:text-indigo-300">Support</a>
            </div>
            <div class="flex items-center space-x-4">
                <input type="text" placeholder="Search..." class="bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
                <?php if (isLoggedIn()): ?>
                    <span class="text-white">Welcome, <?php echo isset($_SESSION['user_id']) ? (isset($user['username']) ? $user['username'] : 'User') : 'Guest'; ?></span>
                    <a href="/kernelstore/logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Logout</a>
                <?php else: ?>
                    <a href="/kernelstore/login.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Login</a>
                    <a href="/kernelstore/register.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>