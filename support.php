<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// التأكد إن المستخدم مسجل دخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// معالجة طلب الدعم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    
    if (empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } else {
        $sql = "INSERT INTO chat_requests (user_id, account_id, subject, message, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("iiss", $user_id, $account_id, $subject, $message);
        if ($stmt->execute()) {
            $success = "Your support request has been submitted!";
        } else {
            $error = "Failed to submit request. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Support</h1>
            <p class="lead text-center mb-6">Need help? Submit a support request</p>
        </div>
    </section>
    
    <!-- Support Form -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-lg mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                <?php if (isset($success)): ?>
                    <p class="text-green-400 text-center mb-4"><?php echo $success; ?></p>
                <?php elseif (isset($error)): ?>
                    <p class="text-red-400 text-center mb-4"><?php echo $error; ?></p>
                <?php endif; ?>
                <form method="POST">
                    <?php if (isset($_GET['account_id'])): ?>
                        <input type="hidden" name="account_id" value="<?php echo (int)$_GET['account_id']; ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label for="subject" class="block text-gray-400 mb-2">Subject</label>
                        <input type="text" id="subject" name="subject" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block text-gray-400 mb-2">Message</label>
                        <textarea id="message" name="message" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors w-full">Submit Request</button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>