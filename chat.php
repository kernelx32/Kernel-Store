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
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// التحقق من وجود الأوردر
$sql = "SELECT id FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("Invalid order or you do not have access to this order.");
}
$stmt->close();

// جلب حالة طلب الشات
$sql = "SELECT status, support_id FROM chat_requests WHERE user_id = ? AND order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $order_id);
$stmt->execute();
$result = $stmt->get_result();
$chat_request = $result->fetch_assoc();
$stmt->close();

$chat_status = 'waiting';
$support_id = $chat_request['support_id'] ?? 1; // معرف الدعم

if ($chat_request) {
    $chat_status = $chat_request['status'];
}

// التحقق من وجود بان للمستخدم
$sql = "SELECT id FROM chat_bans WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$is_banned = $result->num_rows > 0;
$stmt->close();

if ($is_banned) {
    die("You are banned from chatting.");
}

// التحقق من وجود جلسة شات للمستخدم
$sql = "SELECT status FROM chat_sessions WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$session_status = 'waiting';
if ($result->num_rows == 0) {
    $sql = "INSERT INTO chat_sessions (user_id, status) VALUES (?, 'waiting')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
} else {
    $session = $result->fetch_assoc();
    $session_status = $session['status'];
}
$stmt->close();

// تحديث حالة الجلسة لـ active لو مفيش جلسات نشطة
if ($chat_status == 'claimed' && $session_status == 'waiting') {
    $sql = "SELECT COUNT(*) as active_sessions FROM chat_sessions WHERE status = 'active' AND user_id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['active_sessions'] == 0) {
        $sql = "UPDATE chat_sessions SET status = 'active' WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $session_status = 'active';
    }
    $stmt->close();
}

// معالجة التقييم
$rating_success = '';
$rating_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($rating < 1 || $rating > 5) {
        $rating_error = "Rating must be between 1 and 5.";
    } else {
        $sql = "INSERT INTO support_ratings (user_id, support_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $user_id, $support_id, $rating, $comment);
        if ($stmt->execute()) {
            $rating_success = "Thank you for your feedback!";
        } else {
            $rating_error = "Failed to submit rating. Please try again.";
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
    <title>Chat with Support - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        #chat-box {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #4a5568;
            padding: 10px;
            background-color: #2d3748;
            border-radius: 8px;
        }
        .message-sent {
            text-align: right;
            margin-bottom: 10px;
        }
        .message-received {
            text-align: left;
            margin-bottom: 10px;
        }
        .message-sent p {
            background-color: #4a90e2;
            padding: 8px 12px;
            border-radius: 12px;
            display: inline-block;
            max-width: 70%;
        }
        .message-received p {
            background-color: #718096;
            padding: 8px 12px;
            border-radius: 12px;
            display: inline-block;
            max-width: 70%;
        }
        .message-timestamp {
            font-size: 0.75rem;
            color: #a0aec0;
            margin-top: 2px;
        }
    </style>
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Chat with Support</h1>
            <p class="lead text-center mb-6">Contact our support team for assistance</p>
        </div>
    </section>
    
    <!-- Chat Section -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                <?php if ($chat_status == 'pending'): ?>
                    <p class="text-yellow-400 text-center mb-4">Your chat request is pending. Please wait for support to respond.</p>
                <?php elseif ($chat_status == 'claimed' && $session_status == 'waiting'): ?>
                    <p class="text-yellow-400 text-center mb-4">You are in the waiting queue. Please wait until support is available.</p>
                <?php elseif ($chat_status == 'closed' || $session_status == 'closed'): ?>
                    <p class="text-red-400 text-center mb-4">This chat session has been closed by support.</p>
                    <!-- نموذج التقييم -->
                    <?php if (!empty($rating_success)): ?>
                        <p class="text-green-400 text-center mb-4"><?php echo $rating_success; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($rating_error)): ?>
                        <p class="text-red-400 text-center mb-4"><?php echo $rating_error; ?></p>
                    <?php endif; ?>
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="submit_rating" value="1">
                        <div class="mb-4">
                            <label for="rating" class="block text-gray-400 mb-2">Rate your support experience (1-5):</label>
                            <select id="rating" name="rating" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="comment" class="block text-gray-400 mb-2">Additional Comments (optional):</label>
                            <textarea id="comment" name="comment" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="3"></textarea>
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Submit Rating</button>
                    </form>
                <?php else: ?>
                    <!-- منطقة الشات -->
                    <div id="chat-box" class="mb-4"></div>

                    <!-- نموذج إرسال الرسائل -->
                    <