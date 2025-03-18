<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : 0;
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($sender_id <= 0 || $receiver_id <= 0 || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Invalid sender, receiver, or message']);
        exit();
    }

    // التحقق إن الـ sender_id موجود في جدول users
    $sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => "Sender ID $sender_id does not exist in users table"]);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // التحقق إن الـ receiver_id موجود في جدول users
    $sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => "Receiver ID $receiver_id does not exist in users table"]);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // التحقق إن الشات بين مستخدم ودعم فقط
    $support_id = 1; // معرف الدعم (غيّره حسب الأدمن بتاعك)
    if (!($sender_id == $support_id || $receiver_id == $support_id)) {
        echo json_encode(['success' => false, 'error' => 'Chat is only allowed with support']);
        exit();
    }

    // التحقق من وجود بان للمستخدم
    if ($sender_id != $support_id) { // المستخدم العادي بس هو اللي ممكن يتبان
        $sql = "SELECT id FROM chat_bans WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'You are banned from chatting']);
            $stmt->close();
            exit();
        }
        $stmt->close();
    }

    // التحقق من حالة الشات
    if ($sender_id != $support_id) { // المستخدم العادي هو اللي بيتحقق من حالة الشات
        $sql = "SELECT status FROM chat_sessions WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $session = $result->fetch_assoc();
            if ($session['status'] == 'closed') {
                echo json_encode(['success' => false, 'error' => 'Chat session is closed']);
                $stmt->close();
                exit();
            }
        }
        $stmt->close();
    }

    // تحديد اسم الملف بترتيب موحد (الأقل ID أولاً)
    $ids = [$sender_id, $receiver_id];
    sort($ids); // ترتيب الأقل أولاً
    $chat_file = "chats/chat_user_{$ids[0]}_support_{$ids[1]}.txt";
    if (!file_exists('chats')) {
        mkdir('chats', 0777, true);
    }

    // تنسيق الرسالة
    $timestamp = date('Y-m-d H:i:s');
    $message_line = "[$timestamp] User $sender_id: $message\n";

    // إضافة الرسالة للملف
    if (file_put_contents($chat_file, $message_line, FILE_APPEND | LOCK_EX) !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to write message to file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>