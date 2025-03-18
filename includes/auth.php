<?php
// session_start(); // شيلناها من هنا لأن الجلسة بتفعّل في الصفحة الرئيسية
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /kernelstore/login.php");
        exit();
    }
}

function isAdmin($conn) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT is_admin FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return $user['is_admin'] == 1;
    }
    return false;
}

function requireAdmin($conn) {
    if (!isAdmin($conn)) {
        // إضافة رسالة للتحقق
        error_log("Access denied: User is not admin. User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not logged in'));
        header("Location: /kernelstore/index.php?error=access_denied");
        exit();
    }
}

function logout() {
    // مسح الكوكيز بتاع "Remember Me"
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600); // إزالة الكوكيز
    }
    // دمج الجلسة وتجديد المعرف
    session_destroy();
    session_regenerate_id(true); // إعادة تهيئة الجلسة
    header("Location: /kernelstore/index.php");
    exit();
}

function login($conn, $email, $password) {
    $sql = "SELECT id, username, password, is_admin FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            return true;
        }
    }
    return false;
}

function register($conn, $email, $password, $username) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (email, password, username) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $hashed_password, $username);
    return $stmt->execute();
}

function checkRememberToken($conn) {
    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $sql = "SELECT id, username, is_admin FROM users WHERE remember_token = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            return true;
        }
    }
    return false;
}
?>