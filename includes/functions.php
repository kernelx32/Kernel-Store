<?php
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function getAllGames($conn) {
    $games = [];
    $sql = "SELECT g.*, (SELECT COUNT(*) FROM accounts a WHERE a.game_id = g.id AND a.status = 'available') as accounts 
            FROM games g 
            GROUP BY g.id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
    }
    return $games;
}

function getGameById($conn, $id) {
    $sql = "SELECT * FROM games WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getAccountsByGameId($conn, $game_id, $limit, $offset) {
    $sql = "SELECT a.*, g.name as game_name FROM accounts a JOIN games g ON a.game_id = g.id WHERE a.game_id = ? LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $game_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    return $accounts;
}

function getFeaturedGames($conn) {
    $sql = "SELECT g.*, 
            (SELECT COUNT(*) FROM accounts a WHERE a.game_id = g.id AND a.status = 'available') as account_count 
            FROM games g 
            ORDER BY account_count DESC LIMIT 4";
    $result = $conn->query($sql);
    if ($result === false) {
        error_log("Query failed: " . $conn->error);
        return []; // رجوع مصفوفة فارغة لو الاستعلام فشل
    }
    $games = [];
    while ($row = $result->fetch_assoc()) {
        $games[] = $row;
    }
    return $games;
}

function getFeaturedAccounts($conn) {
    $sql = "SELECT a.*, g.name as game_name FROM accounts a JOIN games g ON a.game_id = g.id WHERE a.status = 'available' ORDER BY a.featured DESC, a.id DESC LIMIT 4";
    $result = $conn->query($sql);
    if ($result === false) {
        error_log("Query failed: " . $conn->error);
        return []; // رجوع مصفوفة فارغة لو الاستعلام فشل
    }
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    return $accounts;
}

// دالة لجلب كل الحسابات
function getAllAccounts($conn) {
    $sql = "SELECT a.*, g.name as game_name FROM accounts a JOIN games g ON a.game_id = g.id";
    $result = $conn->query($sql);
    if ($result === false) {
        error_log("Query failed: " . $conn->error);
        return [];
    }
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    return $accounts;
}

// دالة لجلب الصور المتعددة للحساب
function getAccountImages($conn, $account_id) {
    $sql = "SELECT * FROM account_images WHERE account_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    $stmt->close();
    return $images;
}

// دالة لإضافة صور لحساب
function addAccountImage($conn, $account_id, $image) {
    $sql = "INSERT INTO account_images (account_id, image) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $account_id, $image);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// دالة لتحديث حساب
function updateAccount($conn, $id, $title, $game_id, $price, $status) {
    $sql = "UPDATE accounts SET title = ?, game_id = ?, price = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidsi", $title, $game_id, $price, $status, $id);
    return $stmt->execute();
}

// دالة لإضافة حساب
function addAccount($conn, $title, $game_id, $price, $status) {
    $sql = "INSERT INTO accounts (title, game_id, price, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sids", $title, $game_id, $price, $status);
    return $stmt->execute();
}

// دالة لجلب حساب معين بناءً على ID
function getAccountById($conn, $id) {
    $sql = "SELECT a.*, g.name as game_name FROM accounts a JOIN games g ON a.game_id = g.id WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// دالة لتحديث لعبة
function updateGame($conn, $id, $name, $image = null) {
    if ($image) {
        $sql = "UPDATE games SET name = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $image, $id);
    } else {
        $sql = "UPDATE games SET name = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $name, $id);
    }
    return $stmt->execute();
}

// دالة لإنشاء طلب جديد
function createOrder($conn, $user_id, $account_id, $total_amount) {
    // التحقق من القيم المدخلة
    if (empty($user_id) || empty($account_id) || !is_numeric($total_amount)) {
        error_log("Invalid input: user_id=$user_id, account_id=$account_id, total_amount=$total_amount");
        return false;
    }

    $status = 'pending';
    $sql = "INSERT INTO orders (user_id, account_id, total_amount, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iids", $user_id, $account_id, $total_amount, $status);
    if ($stmt->execute()) {
        $orderId = $conn->insert_id; // إرجاع ID الطلب الجديد
        $stmt->close();
        return $orderId;
    } else {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
}
?>