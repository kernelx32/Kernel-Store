<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if $conn is properly initialized
if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not initialized"));
}

if (isLoggedIn()) {
    if (isAdmin($conn)) {
        header("Location: /kernelstore/admin/index.php");
    } else {
        header("Location: /kernelstore/profile.php");
    }
    exit();
}

// Check remember token
checkRememberToken($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (login($conn, $email, $password)) {
        if ($remember) {
            $token = bin2hex(random_bytes(32)); // Generate a secure token
            $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);

            // Check if prepare failed
            if ($stmt === false) {
                die("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("si", $token, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60)); // 30 days
        }
        if (isAdmin($conn)) {
            header("Location: /kernelstore/admin/index.php");
        } else {
            header("Location: /kernelstore/profile.php");
        }
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <?php include 'includes/header.php'; ?>
    
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6 text-center">Login</h1>
            <p class="lead text-center mb-6">Sign in to your account</p>
        </div>
    </section>
    
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-lg mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                <?php if (isset($error)): ?>
                    <p class="text-red-400 mb-4 text-center"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-4">
                        <label for="email" class="block text-gray-400 mb-2">Email</label>
                        <input type="email" id="email" name="email" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-gray-400 mb-2">Password</label>
                        <input type="password" id="password" name="password" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4 flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="mr-2">
                        <label for="remember" class="text-gray-400">Remember Me</label>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Login</button>
                </form>
                <p class="mt-4 text-center"><a href="register.php" class="text-indigo-400 hover:text-indigo-300">Don't have an account? Register</a></p>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>