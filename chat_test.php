<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$test_results = [];

// تعريف معرفات المستخدمين للاختبار
$user_id = 3; // مستخدم عادي (غيّره حسب قاعدة بياناتك)
$support_id = 1; // معرف الدعم (الأدمن)
$order_id = 1; // معرف أوردر (غيّره حسب قاعدة بياناتك)

// التحقق من وجود الدعم في جدول users
$sql = "SELECT id FROM users WHERE id = ? AND is_admin = 1";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $support_id);
$stmt->execute();
$result = $stmt->get_result();
$support_exists = $result->num_rows > 0;
$stmt->close();
$test_results[] = [
    'test' => 'Support user exists and is admin',
    'result' => $support_exists ? 'Pass' : 'Fail',
    'details' => $support_exists ? "Support ID $support_id exists as admin" : "Support ID $support_id does not exist or is not admin"
];

// التحقق من وجود المستخدم في جدول users
$sql = "SELECT id FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_exists = $result->num_rows > 0;
$stmt->close();
$test_results[] = [
    'test' => 'User exists',
    'result' => $user_exists ? 'Pass' : 'Fail',
    'details' => $user_exists ? "User ID $user_id exists" : "User ID $user_id does not exist"
];

// إنشاء جلسة شات للمستخدم قبل الاختبارات
$sql = "INSERT INTO chat_sessions (user_id, status) VALUES (?, 'waiting') ON DUPLICATE KEY UPDATE status = 'waiting'";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Initialize chat session (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// إنشاء طلب شات للاختبار
$sql = "INSERT INTO chat_requests (user_id, order_id, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE status = 'pending'";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Initialize chat request (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->bind_param("ii", $user_id, $order_id);
    $stmt->execute();
    $stmt->close();
}

// اختبار 1: تخزين الرسائل في ملف .txt
$_SESSION['user_id'] = $user_id;
$message = "Test message from user to support";
$ids = [$user_id, $support_id];
sort($ids);
$chat_file = "chats/chat_user_{$ids[0]}_support_{$ids[1]}.txt";
if (!file_exists('chats')) {
    mkdir('chats', 0777, true);
}

$data = [
    'sender_id' => $user_id,
    'receiver_id' => $support_id,
    'message' => $message
];
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$response = file_get_contents('http://localhost/kernelstore/send_message.php', false, $context);
$response_data = json_decode($response, true);

$test_results[] = [
    'test' => 'Message stored in .txt file',
    'result' => file_exists($chat_file) && strpos(file_get_contents($chat_file), $message) !== false ? 'Pass' : 'Fail',
    'details' => file_exists($chat_file) && strpos(file_get_contents($chat_file), $message) !== false ? "Message found in $chat_file" : "Message not found in $chat_file"
];

// اختبار 2: إغلاق الشات
$_SESSION['user_id'] = $support_id;
$data = ['close_chat' => 1];
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = file_get_contents('http://localhost/kernelstore/support_chat.php?user_id=' . $user_id . '&order_id=' . $order_id, false, $context);
$sql = "SELECT status FROM chat_sessions WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Closing chat by support (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();
    $chat_status = $session ? $session['status'] : 'Not found';
    $stmt->close();
    $test_results[] = [
        'test' => 'Closing chat by support',
        'result' => $chat_status == 'closed' ? 'Pass' : 'Fail',
        'details' => "Chat status: $chat_status"
    ];
}

// اختبار 3: إعطاء بان للمستخدم
$data = ['ban_user' => 1, 'ban_reason' => 'Test ban'];
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = file_get_contents('http://localhost/kernelstore/support_chat.php?user_id=' . $user_id . '&order_id=' . $order_id, false, $context);
$sql = "SELECT id FROM chat_bans WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Banning user (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $test_results[] = [
        'test' => 'Banning user',
        'result' => $result->num_rows > 0 ? 'Pass' : 'Fail',
        'details' => $result->num_rows > 0 ? "User $user_id is banned" : "User $user_id is not banned"
    ];
    $stmt->close();
}

// اختبار 4: نظام التقييم
$_SESSION['user_id'] = $user_id;
$data = ['submit_rating' => 1, 'rating' => 4, 'comment' => 'Good support'];
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = file_get_contents('http://localhost/kernelstore/chat.php?order_id=' . $order_id, false, $context);
$sql = "SELECT id FROM support_ratings WHERE user_id = ? AND rating = 4";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Rating submission (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $test_results[] = [
        'test' => 'Rating submission',
        'result' => $result->num_rows > 0 ? 'Pass' : 'Fail',
        'details' => $result->num_rows > 0 ? "Rating submitted successfully" : "Rating not submitted"
    ];
    $stmt->close();
}

// اختبار 5: استقبال الرسائل
$_SESSION['user_id'] = $user_id;
$response = file_get_contents("http://localhost/kernelstore/get_messages.php?sender_id=$user_id&receiver_id=$support_id&last_id=0");
$response_data = json_decode($response, true);
$test_results[] = [
    'test' => 'Receiving messages as user',
    'result' => !empty($response_data['messages']) ? 'Pass' : 'Fail',
    'details' => !empty($response_data['messages']) ? "Messages received: " . count($response_data['messages']) : "No messages received"
];

$_SESSION['user_id'] = $support_id;
$response = file_get_contents("http://localhost/kernelstore/get_messages.php?sender_id=$support_id&receiver_id=$user_id&last_id=0");
$response_data = json_decode($response, true);
$test_results[] = [
    'test' => 'Receiving messages as support',
    'result' => !empty($response_data['messages']) ? 'Pass' : 'Fail',
    'details' => !empty($response_data['messages']) ? "Messages received: " . count($response_data['messages']) : "No messages received"
];

// اختبار 6: نظام الانتظار
$_SESSION['user_id'] = $user_id;
$sql = "INSERT INTO chat_sessions (user_id, status) VALUES (?, 'waiting') ON DUPLICATE KEY UPDATE status = 'waiting'";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Waiting queue system (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $user_id_2 = $user_id + 1; // مستخدم جديد للاختبار
    $stmt->bind_param("i", $user_id_2);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE chat_sessions SET status = 'active' WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $test_results[] = [
            'test' => 'Waiting queue system (update prepare failed)',
            'result' => 'Fail',
            'details' => "Prepare failed: " . $conn->error
        ];
    } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $sql = "SELECT status FROM chat_sessions WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $test_results[] = [
                'test' => 'Waiting queue system (select prepare failed)',
                'result' => 'Fail',
                'details' => "Prepare failed: " . $conn->error
            ];
        } else {
            $stmt->bind_param("i", $user_id_2);
            $stmt->execute();
            $result = $stmt->get_result();
            $waiting_status = $result->fetch_assoc()['status'] ?? 'Not found';
            $stmt->close();
            $test_results[] = [
                'test' => 'Waiting queue system',
                'result' => $waiting_status == 'waiting' ? 'Pass' : 'Fail',
                'details' => "Waiting user status: $waiting_status"
            ];
        }
    }
}

// اختبار 7: طلبات الشات
$_SESSION['user_id'] = $user_id;
$data = ['request_chat' => 1, 'order_id' => $order_id];
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = file_get_contents('http://localhost/kernelstore/accounts.php', false, $context);
$sql = "SELECT id FROM chat_requests WHERE user_id = ? AND order_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Chat request submission (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->bind_param("ii", $user_id, $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $test_results[] = [
        'test' => 'Chat request submission',
        'result' => $result->num_rows > 0 ? 'Pass' : 'Fail',
        'details' => $result->num_rows > 0 ? "Chat request submitted" : "Chat request not submitted"
    ];
    $stmt->close();
}

// اختبار 8: قبول طلب الشات من الدعم
$_SESSION['user_id'] = $support_id;
$result = file_get_contents("http://localhost/kernelstore/support_chat.php?user_id=$user_id&order_id=$order_id");
$sql = "SELECT status, support_id FROM chat_requests WHERE user_id = ? AND order_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Support claims chat request (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->bind_param("ii", $user_id, $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $request_status = $request ? $request['status'] : 'Not found';
    $request_support_id = $request ? $request['support_id'] : 'Not set';
    $stmt->close();
    $test_results[] = [
        'test' => 'Support claims chat request',
        'result' => $request_status == 'claimed' && $request_support_id == $support_id ? 'Pass' : 'Fail',
        'details' => "Status: $request_status, Support ID: $request_support_id"
    ];
}

// اختبار 9: إخفاء طلب الشات من الدعم الآخرين
$_SESSION['user_id'] = $support_id + 1; // دعم آخر
$sql = "SELECT cr.user_id, cr.order_id, cr.status, cr.support_id 
        FROM chat_requests cr 
        JOIN users u ON cr.user_id = u.id 
        JOIN orders o ON cr.order_id = o.id 
        WHERE cr.status IN ('pending', 'claimed')";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $test_results[] = [
        'test' => 'Chat request hidden from other support (prepare failed)',
        'result' => 'Fail',
        'details' => "Prepare failed: " . $conn->error
    ];
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    $chat_requests = $result->fetch_all(MYSQLI_ASSOC);
    $found = false;
    foreach ($chat_requests as $request) {
        if ($request['user_id'] == $user_id && $request['order_id'] == $order_id) {
            $found = $request['status'] == 'claimed' && $request['support_id'] != $_SESSION['user_id'];
        }
    }
    $test_results[] = [
        'test' => 'Chat request hidden from other support',
        'result' => $found ? 'Pass' : 'Fail',
        'details' => $found ? "Chat request hidden from other support" : "Chat request visible to other support"
    ];
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Test - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6 text-center">Chat System Test Results</h1>
            <div class="max-w-4xl mx-auto bg-gray-800 rounded-xl p-8 shadow-lg">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="p-2 text-left">Test</th>
                            <th class="p-2 text-left">Result</th>
                            <th class="p-2 text-left">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($test_results as $test): ?>
                            <tr class="border-b border-gray-700">
                                <td class="p-2"><?php echo $test['test']; ?></td>
                                <td class="p-2 <?php echo $test['result'] == 'Pass' ? 'text-green-400' : 'text-red-400'; ?>">
                                    <?php echo $test['result']; ?>
                                </td>
                                <td class="p-2"><?php echo $test['details']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="mt-4 text-center">
                    <strong>Total Tests: <?php echo count($test_results); ?></strong><br>
                    Passed: <?php echo count(array_filter($test_results, fn($t) => $test['result'] == 'Pass')); ?><br>
                    Failed: <?php echo count(array_filter($test_results, fn($t) => $test['result'] == 'Fail')); ?>
                </p>
            </div>
        </div>
    </section>
</body>
</html>