<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// التأكد إن المستخدم مسجل دخول
requireLogin();

// جلب معرف الطلب من الـ URL
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

// جلب تفاصيل الطلب مع الصورة من جدول account_images
$order = null;
$sql = "SELECT o.*, a.title AS account_title, a.price, g.name AS game_name, ai.image AS account_image
        FROM orders o 
        JOIN accounts a ON o.account_id = a.id 
        JOIN games g ON a.game_id = g.id 
        LEFT JOIN account_images ai ON a.id = ai.account_id 
        WHERE o.id = ? AND o.user_id = ? 
        LIMIT 1"; // LIMIT 1 عشان نجيب أول صورة بس
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error); // لو فيه مشكلة في الـ Query، هيظهر السبب
}
$stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $order = $result->fetch_assoc();
}

if (!$order) {
    header('Location: index.php');
    exit;
}

// معالجة تأكيد الشراء
$error = '';
$success = '';

if (isset($_POST['confirm_purchase'])) {
    $paymentMethod = sanitizeInput($_POST['payment_method']);
    if (empty($paymentMethod)) {
        $error = "Please select a payment method.";
    } else {
        // تحديث حالة الطلب إلى completed
        $sql = "UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
        if ($stmt->execute()) {
            // تحديث حالة الحساب إلى sold
            $sql = "UPDATE accounts SET status = 'sold' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order['account_id']);
            $stmt->execute();

            $success = "Purchase confirmed successfully with " . htmlspecialchars($paymentMethod) . "! You'll receive the account details via email and your dashboard shortly.";
        } else {
            $error = "Failed to confirm purchase. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/kernelstore/assets/css/style.css">
    <style>
        .payment-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .step-indicator {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background-color: #4f46e5;
            border-radius: 50%;
            border: 2px solid #fff;
        }
        .step-indicator.active::before {
            background-color: #22c55e;
        }
    </style>
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl font-bold text-center text-indigo-400">Checkout</h1>
            <p class="text-center text-gray-400 mt-2">Securely complete your purchase</p>
        </div>
    </section>
    
    <!-- Checkout Steps -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between text-sm text-gray-400">
            <div class="step-indicator active">1. Order Summary</div>
            <div class="step-indicator">2. Payment</div>
            <div class="step-indicator">3. Confirmation</div>
        </div>
    </div>
    
    <!-- Checkout Details -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <?php if (!empty($success)): ?>
                <div class="max-w-3xl mx-auto bg-green-500/20 border border-green-500 text-green-300 px-6 py-4 rounded-lg shadow-lg text-center animate-pulse">
                    <h2 class="text-2xl font-bold mb-2">Success!</h2>
                    <p class="text-lg"><?php echo $success; ?></p>
                    <a href="/kernelstore/admin/index.php" class="mt-4 inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                        <i class="fas fa-user mr-2"></i> Go to Dashboard
                    </a>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="max-w-3xl mx-auto bg-red-500/20 border border-red-500 text-red-300 px-6 py-4 rounded-lg shadow-lg">
                    <p class="text-lg"><?php echo $error; ?></p>
                </div>
            <?php else: ?>
                <div class="max-w-3xl mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                    <h2 class="text-2xl font-bold mb-6 text-indigo-400 border-b border-gray-700 pb-2">Order Details</h2>
                    
                    <!-- Order Summary -->
                    <div class="mb-8">
                        <div class="flex items-center mb-4">
                            <img src="/kernelstore/assets/images/accounts/<?php echo htmlspecialchars($order['account_image']); ?>" alt="<?php echo htmlspecialchars($order['account_title']); ?>" class="w-20 h-20 rounded-lg mr-4 object-cover">
                            <div>
                                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($order['account_title']); ?></h3>
                                <p class="text-gray-400">Game: <?php echo htmlspecialchars($order['game_name']); ?></p>
                            </div>
                        </div>
                        <p class="text-gray-400 mb-2">Order ID: #<?php echo $orderId; ?></p>
                        <p class="text-gray-400 mb-2">Date: <?php echo date('F d, Y H:i:s', strtotime($order['created_at'])); ?></p>
                        <p class="text-gray-400 mb-2">Price: <span class="text-white font-bold">$<?php echo number_format($order['total_amount'], 2); ?></span></p>
                        <p class="text-gray-400 mb-2">Status: <span class="text-yellow-400 font-semibold"><?php echo htmlspecialchars($order['status']); ?></span></p>
                    </div>
                    
                    <!-- Payment Options -->
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold mb-4 text-indigo-400">Select Payment Method</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="payment-option bg-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-600 transition-all duration-300">
                                <input type="radio" name="payment_method" value="credit_card" class="mr-2" required>
                                <i class="fas fa-credit-card text-2xl text-indigo-400 mb-2"></i>
                                <p class="text-gray-300">Credit Card</p>
                            </label>
                            <label class="payment-option bg-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-600 transition-all duration-300">
                                <input type="radio" name="payment_method" value="paypal" class="mr-2">
                                <i class="fab fa-paypal text-2xl text-indigo-400 mb-2"></i>
                                <p class="text-gray-300">PayPal</p>
                            </label>
                            <label class="payment-option bg-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-600 transition-all duration-300">
                                <input type="radio" name="payment_method" value="crypto" class="mr-2">
                                <i class="fas fa-bitcoin text-2xl text-indigo-400 mb-2"></i>
                                <p class="text-gray-300">Crypto</p>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-4 text-indigo-400">Order Summary</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Subtotal</span>
                                <span class="text-white">$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Tax (5%)</span>
                                <span class="text-white">$<?php echo number_format($order['total_amount'] * 0.05, 2); ?></span>
                            </div>
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total</span>
                                <span class="text-indigo-400">$<?php echo number_format($order['total_amount'] * 1.05, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <button type="submit" name="confirm_purchase" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center">
                            <i class="fas fa-check-circle mr-2"></i> Confirm Purchase
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Additional Information -->
            <div class="max-w-3xl mx-auto mt-6 flex items-center justify-between text-gray-400">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt mr-2 text-indigo-400"></i>
                    <span>Secure Payment</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-truck mr-2 text-indigo-400"></i>
                    <span>Instant Delivery</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-undo mr-2 text-indigo-400"></i>
                    <span>24h Warranty</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="/kernelstore/assets/js/script.js"></script>
</body>
</html>