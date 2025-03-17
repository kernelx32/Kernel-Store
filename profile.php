<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// التأكد إن المستخدم مسجل دخوله
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$sql = "SELECT email, username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Your Profile</h1>
            <p class="lead text-center mb-6">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</p>
        </div>
    </section>
    
    <!-- Profile Details -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-lg mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                <h2 class="text-2xl font-bold mb-6 text-center">Profile Information</h2>
                <div class="mb-4">
                    <label class="block text-gray-400 mb-2">Username</label>
                    <p class="text-white"><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-400 mb-2">Email</label>
                    <p class="text-white"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="flex space-x-2">
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Logout</a>
                    <a href="profile.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Refresh</a>
                </div>
            </div>