<?php
session_start(); // هنا بتفعّل الجلسة مرة واحدة
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // ما فيش session_start() هنا دلوقتي

if (isLoggedIn()) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (function_exists('register')) {
        if (register($conn, $email, $password, $username)) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Email might be taken or database error.";
        }
    } else {
        $error = "Function register() is not defined. Check includes/auth.php.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Register</h1>
            <p class="lead text-center mb-6">Create your account to get started!</p>
        </div>
    </section>
    
    <!-- Registration Form -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-lg mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                <h2 class="text-2xl font-bold mb-6 text-center">Register Account</h2>
                <?php if (isset($error)): ?>
                    <p class="text-red-400 mb-4 text-center"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="register.php" method="POST">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-400 mb-2">Username</label>
                        <input type="text" id="username" name="username" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-400 mb-2">Email</label>
                        <input type="email" id="email" name="email" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-gray-400 mb-2">Password</label>
                        <input type="password" id="password" name="password" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Register</button>
                </form>
                <p class="mt-4 text-center"><a href="login.php" class="text-indigo-400 hover:text-indigo-300">Already have an account? Login</a></p>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>