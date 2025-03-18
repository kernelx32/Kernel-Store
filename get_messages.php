<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

$sender_id = isset($_GET['sender_id']) ? (int)$_GET['sender_id'] : 0;
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if ($sender_id <= 0 || $receiver_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid sender or receiver']);
    exit();
}

// التحقق إن الشات بين مستخدم ودعم فقط
$support_id = 1;
if (!($sender_id == $support_id || $receiver_id == $support_id)) {
    echo json_encode(['success' => false, 'error' => 'Chat is only allowed with support']);
    exit();
}

// تحديد اسم الملف بترتيب موحد (الأقل ID أولاً)
$ids = [$sender_id, $receiver_id];
sort($ids);
$chat_file = "chats/chat_user_{$ids[0]}_support_{$ids[1]}.txt";
if (!file_exists($chat_file)) {
    echo json_encode(['success' => true, 'messages' => []]);
    exit();
}

// قراءة الرسائل من الملف
$messages = [];
$lines = file($chat_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $index => $line) {
    if ($index <= $last_id) continue; // تخطي الرسائل القديمة

    preg_match('/\[(.*?)\] User (\d+): (.*)/', $line, $matches);
    if (count($matches) === 4) {
        $timestamp = $matches[1];
        $user_id = (int)$matches[2];
        $message_text = $matches[3];

        $messages[] = [
            'id' => $index,
            'sender_id' => $user_id,
            'receiver_id' => ($user_id == $sender_id) ? $receiver_id : $sender_id,
            'message' => htmlspecialchars($message_text),
            'sent_at' => $timestamp
        ];
    }
}

echo json_encode(['success' => true, 'messages' => $messages]);
?>