<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// التأكد إن المستخدم مسجل دخوله وهو Admin
requireLogin();
requireAdmin($conn);

$account_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$account = null;

if ($account_id > 0) {
    $sql = "SELECT a.*, g.name AS game_name 
            FROM accounts a 
            JOIN games g ON a.game_id = g.id 
            WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $account = $result->fetch_assoc();
    }
}

if (!$account) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// معالجة حذف صورة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['delete_image'];
    $sql = "DELETE FROM account_images WHERE id = ? AND account_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $image_id, $account_id);
    if ($stmt->execute()) {
        $success = "<p class='text-green-400 text-center'>Image deleted successfully!</p>";
    } else {
        $error = "<p class='text-red-400 text-center'>Failed to delete image. Try again.</p>";
    }
    $stmt->close();
}

// معالجة تحديث الحساب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $title = trim($_POST['title']);
    $game_id = (int)$_POST['game_id'];
    $price = (float)$_POST['price'];
    $status = trim($_POST['status']);
    $images = $_FILES['images'];

    if (empty($title) || empty($game_id) || empty($price) || empty($status)) {
        $error = "<p class='text-red-400 text-center'>Please fill all required fields.</p>";
    } else {
        // تحديث الحساب
        if (updateAccount($conn, $account_id, $title, $game_id, $price, $status)) {
            // إضافة الصور الجديدة
            if (!empty($images['name'][0])) {
                $target_dir = "../assets/images/accounts/";
                $uploaded_images = [];
                for ($i = 0; $i < count($images['name']); $i++) {
                    if (!empty($images['name'][$i])) {
                        $target_file = $target_dir . basename($images['name'][$i]);
                        if (move_uploaded_file($images['tmp_name'][$i], $target_file)) {
                            $uploaded_images[] = $images['name'][$i];
                        }
                    }
                }

                // إضافة الصور لجدول account_images
                foreach ($uploaded_images as $image) {
                    addAccountImage($conn, $account_id, $image);
                }
            }

            $success = "<p class='text-green-400 text-center'>Account updated successfully!</p>";
        } else {
            $error = "<p class='text-red-400 text-center'>Failed to update account. Try again.</p>";
        }
    }
}

$all_games = getAllGames($conn);
$images = getAccountImages($conn, $account_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/kernelstore/assets/css/admin.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Header -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Edit Account</h1>
            <p class="lead text-center mb-6">Update account details here!</p>
        </div>
    </section>
    
    <!-- Edit Account Form -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <?php if (!empty($success)) echo $success; ?>
            <?php if (!empty($error)) echo $error; ?>
            <form method="POST" enctype="multipart/form-data" class="bg-gray-800 rounded-xl p-6 max-w-lg">
                <div class="mb-4">
                    <label for="title" class="block text-gray-400 mb-2">Account Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($account['title']); ?>" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="game_id" class="block text-gray-400 mb-2">Game</label>
                    <select id="game_id" name="game_id" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <?php foreach ($all_games as $game): ?>
                            <option value="<?php echo $game['id']; ?>" <?php echo $game['id'] == $account['game_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($game['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="price" class="block text-gray-400 mb-2">Price</label>
                    <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($account['price']); ?>" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="status" class="block text-gray-400 mb-2">Status</label>
                    <select id="status" name="status" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="available" <?php echo $account['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo $account['status'] == 'sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-400 mb-2">Current Images</label>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($images as $image): ?>
                            <div class="relative">
                                <img src="../assets/images/accounts/<?php echo htmlspecialchars($image['image']); ?>" alt="Account Image" class="w-full h-32 object-cover rounded-lg">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="delete_image" value="<?php echo $image['id']; ?>">
                                    <button type="submit" class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded-full"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="images" class="block text-gray-400 mb-2">Add New Images (Select multiple images)</label>
                    <input type="file" id="images" name="images[]" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none" multiple>
                </div>
                <button type="submit" name="update_account" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Update Account</button>
                <a href="index.php" class="ml-2 text-gray-400 hover:text-gray-300">Cancel</a>
            </form>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>