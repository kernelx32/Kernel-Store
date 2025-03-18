<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// التأكد إن المستخدم مسجل دخول وأدمن
if (!isLoggedIn() || !isAdmin($conn)) {
    header("Location: index.php");
    exit();
}

$support_id = $_SESSION['user_id'];

// جلب طلبات الشات
$sql = "SELECT cr.id, cr.user_id, cr.order_id, cr.status, cr.support_id, u.username, o.order_details 
        FROM chat_requests cr 
        JOIN users u ON cr.user_id = u.id 
        JOIN orders o ON cr.order_id = o.id 
        WHERE cr.status IN ('pending', 'claimed') 
        ORDER BY cr.created_at ASC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$chat_requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// جلب المستخدم المختار للدردشة
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$user_username = '';
$order_details = '';
$chat_request_id = 0;

if ($user_id && $order_id) {
    $sql = "SELECT cr.id, u.username, o.order_details 
            FROM chat_requests cr 
            JOIN users u ON cr.user_id = u.id 
            JOIN orders o ON cr.order_id = o.id 
            WHERE cr.user_id = ? AND cr.order_id = ? AND cr.status IN ('pending', 'claimed')";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $user_id, $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $chat = $result->fetch_assoc();
        $user_username = $chat['username'];
        $order_details = $chat['order_details'];
        $chat_request_id = $chat['id'];

        // تحديث حالة الطلب لـ claimed لو ما اتقبلش قبل كده
        $sql = "UPDATE chat_requests SET status = 'claimed', support_id = ? WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ii", $support_id, $chat_request_id);
        $stmt->execute();
        $stmt->close();

        // تحديث حالة الشات لـ active
        $sql = "UPDATE chat_sessions SET status = 'active' WHERE user_id = ? AND status = 'waiting'";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $user_id = null; // إذا الطلب مش موجود أو مش متاح
        $order_id = null;
    }
    $stmt->close();
}

// معالجة إغلاق الشات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_chat'])) {
    $sql = "UPDATE chat_requests SET status = 'closed' WHERE id = ? AND support_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $chat_request_id, $support_id);
    $stmt->execute();
    $stmt->close();

    // إغلاق جلسة الشات
    $sql = "UPDATE chat_sessions SET status = 'closed' WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: support_chat.php");
    exit();
}

// معالجة إعطاء بان للمستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_user'])) {
    $reason = trim($_POST['ban_reason']);
    $sql = "INSERT INTO chat_bans (user_id, banned_by, reason) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iis", $user_id, $support_id, $reason);
    $stmt->execute();
    $stmt->close();

    // إغلاق جلسة الشات
    $sql = "UPDATE chat_requests SET status = 'closed' WHERE id = ? AND support_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $chat_request_id, $support_id);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE chat_sessions SET status = 'closed' WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: support_chat.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat - KernelStore</title>
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
        .user-list {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Support Chat</h1>
            <p class="lead text-center mb-6">Manage user chats</p>
        </div>
    </section>
    
    <!-- Support Chat Section -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="flex space-x-4">
                <!-- قائمة طلبات الشات -->
                <div class="w-1/4 bg-gray-800 rounded-xl p-4 user-list">
                    <h2 class="text-xl font-bold mb-4">Chat Requests</h2>
                    <?php if (empty($chat_requests)): ?>
                        <p class="text-gray-400">No chat requests.</p>
                    <?php else: ?>
                        <?php foreach ($chat_requests as $request): ?>
                            <?php if ($request['status'] == 'pending' || ($request['status'] == 'claimed' && $request['support_id'] == $support_id)): ?>
                                <div class="mb-2">
                                    <a href="support_chat.php?user_id=<?php echo $request['user_id']; ?>&order_id=<?php echo $request['order_id']; ?>" class="block p-2 rounded-lg <?php echo $request['user_id'] == $user_id && $request['order_id'] == $order_id ? 'bg-indigo-600' : 'bg-gray-700'; ?> hover:bg-indigo-500 transition-colors">
                                        <div>
                                            <span class="font-bold"><?php echo htmlspecialchars($request['username']); ?></span>
                                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($request['order_details']); ?></p>
                                            <?php if ($request['status'] == 'claimed'): ?>
                                                <span class="text-sm text-green-400">Claimed</span>
                                            <?php else: ?>
                                                <span class="text-sm text-yellow-400">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- منطقة الشات -->
                <div class="w-3/4 bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                    <?php if (!$user_id || !$order_id): ?>
                        <p class="text-gray-400 text-center">Select a chat request to start chatting.</p>
                    <?php else: ?>
                        <h2 class="text-xl font-bold mb-4">Chat with <?php echo htmlspecialchars($user_username); ?></h2>
                        <p class="text-gray-400 mb-4"><?php echo htmlspecialchars($order_details); ?></p>
                        <div id="chat-box" class="mb-4"></div>

                        <!-- نموذج إرسال الرسائل -->
                        <form id="message-form" onsubmit="sendMessage(event)">
                            <div class="flex items-center space-x-2 mb-4">
                                <input type="hidden" id="receiver_id" name="receiver_id" value="<?php echo $user_id; ?>">
                                <input type="text" id="message" name="message" placeholder="Type your message..." class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Send</button>
                            </div>
                        </form>

                        <!-- أزرار التحكم -->
                        <div class="flex space-x-2">
                            <form method="POST">
                                <input type="hidden" name="close_chat" value="1">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Close Chat</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="ban_user" value="1">
                                <div class="flex items-center space-x-2">
                                    <input type="text" name="ban_reason" placeholder="Reason for ban..." class="bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Ban User</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        let lastMessageId = 0;

        // دالة لإرسال رسالة
        function sendMessage(event) {
            event.preventDefault();
            const receiverId = document.getElementById('receiver_id').value;
            const message = document.getElementById('message').value;

            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `sender_id=<?php echo $support_id; ?>&receiver_id=${receiverId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('message').value = '';
                    fetchMessages();
                } else {
                    alert('Failed to send message: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // دالة لجلب الرسائل
        function fetchMessages() {
            const senderId = <?php echo $support_id; ?>;
            const receiverId = document.getElementById('receiver_id')?.value;
            if (!receiverId) return;

            fetch(`get_messages.php?sender_id=${senderId}&receiver_id=${receiverId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                const chatBox = document.getElementById('chat-box');
                data.messages.forEach(msg => {
                    const messageClass = msg.sender_id == senderId ? 'message-sent' : 'message-received';
                    const messageElement = `
                        <div class="${messageClass}">
                            <p>${msg.message}</p>
                            <div class="message-timestamp">${new Date(msg.sent_at).toLocaleString()}</div>
                        </div>
                    `;
                    chatBox.insertAdjacentHTML('beforeend', messageElement);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => console.error('Error:', error));
        }

        // تحديث الرسائل كل 3 ثواني
        setInterval(fetchMessages, 3000);

        // جلب الرسائل عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', fetchMessages);
    </script>
</body>
</html>