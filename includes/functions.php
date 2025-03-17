<?php
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function getAllGames($conn) {
    $sql = "SELECT * FROM games";
    $result = $conn->query($sql);
    $games = [];
    while ($row = $result->fetch_assoc()) {
        $games[] = $row;
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
    $sql = "SELECT * FROM games ORDER BY account_count DESC LIMIT 4";
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

// دالة لتحديث حساب
function updateAccount($conn, $id, $title, $game_id, $price, $status, $image = null) {
    if ($image) {
        $sql = "UPDATE accounts SET title = ?, game_id = ?, price = ?, status = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidssi", $title, $game_id, $price, $status, $image, $id);
    } else {
        $sql = "UPDATE accounts SET title = ?, game_id = ?, price = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidss", $title, $game_id, $price, $status, $id);
    }
    return $stmt->execute();
}

// دالة لإضافة حساب
function addAccount($conn, $title, $game_id, $price, $status, $image) {
    $sql = "INSERT INTO accounts (title, game_id, price, status, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidss", $title, $game_id, $price, $status, $image);
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
?>