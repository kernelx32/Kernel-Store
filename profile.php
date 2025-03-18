<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// التأكد إن المستخدم مسجل دخوله
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$sql = "SELECT email, username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// معالجة نموذج التعديل
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_email = trim($_POST['email']);
    $new_username = trim($_POST['username']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من الإيميل
    if (empty($new_email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // التحقق إن الإيميل مش مستخدم من يوزر تاني
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "This email is already used by another user.";
        }
        $stmt->close();
    }

    // التحقق من اليوزر نيم
    if (empty($new_username)) {
        $errors[] = "Username is required.";
    } else {
        // التحقق إن اليوزر نيم مش مستخدم من يوزر تاني
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "This username is already used by another user.";
        }
        $stmt->close();
    }

    // التحقق من كلمة المرور
    if (!empty($new_password)) {
        if (strlen($new_password) <= 8) {
            $errors[] = "Password must be more than 8 characters long.";
        } elseif (!preg_match("/[A-Za-z0-9]*[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?][A-Za-z0-9]*/", $new_password)) {
            $errors[] = "Password must contain at least one special character (e.g., !, @, #, $).";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    // لو مفيش أخطاء، نقدر نحدث البيانات
    if (empty($errors)) {
        $update_fields = [];
        $params = [];
        $types = '';

        // تحديث الإيميل
        if ($new_email !== $user['email']) {
            $update_fields[] = "email = ?";
            $params[] = $new_email;
            $types .= 's';
        }

        // تحديث اليوزر نيم
        if ($new_username !== $user['username']) {
            $update_fields[] = "username = ?";
            $params[] = $new_username;
            $types .= 's';
        }

        // تحديث كلمة المرور لو موجودة
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields[] = "password = ?";
            $params[] = $hashed_password;
            $types .= 's';
        }

        if (!empty($update_fields)) {
            $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $params[] = $user_id;
            $types .= 'i';

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // تحديث البيانات في الجلسة
                $_SESSION['username'] = $new_username;
                // جلب البيانات المحدثة لعرضها
                $sql = "SELECT email, username FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $errors[] = "Failed to update profile. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Header -->

    
    <!-- Profile Details -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-lg mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                <?php if (!empty($errors)): ?>
                    <div class="mb-4">
                        <?php foreach ($errors as $error): ?>
                            <p class="text-red-400 text-center"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <p class="text-green-400 mb-4 text-center"><?php echo $success; ?></p>
                <?php endif; ?>

                <h2 class="text-2xl font-bold mb-6 text-center">Profile Information</h2>
                
                <!-- نموذج التعديل -->
                <form action="profile.php" method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-400 mb-2">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-400 mb-2">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-gray-400 mb-2">New Password (leave blank to keep current)</label>
                        <input type="password" id="password" name="password" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-gray-400 mb-2">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Update Profile</button>
                        <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Logout</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>