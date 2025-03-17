<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

// التأكد إن المستخدم مسجل دخوله وهو Admin
requireLogin();
requireAdmin($conn);

$accounts = getAllAccounts($conn);
$games = getAllGames($conn);

// إضافة حساب جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $title = $_POST['title'];
    $game_id = $_POST['game_id'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $image = $_FILES['image']['name'];
    $target_dir = "../assets/images/accounts/";
    $target_file = $target_dir . basename($image);
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        die("Failed to upload image.");
    }

    if (!addAccount($conn, $title, $game_id, $price, $status, $image)) {
        die("Failed to add account: " . $conn->error);
    }
    header("Location: /kernelstore/admin/accounts.php");
    exit();
}

// تعديل حساب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $game_id = $_POST['game_id'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $image = isset($_FILES['image']) && $_FILES['image']['name'] ? $_FILES['image']['name'] : null;
    if ($image) {
        $target_dir = "../assets/images/accounts/";
        $target_file = $target_dir . basename($image);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            die("Failed to upload image.");
        }
    }
    if (!updateAccount($conn, $id, $title, $game_id, $price, $status, $image)) {
        die("Failed to update account: " . $conn->error);
    }
    header("Location: /kernelstore/admin/accounts.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Accounts - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Admin Accounts</h1>
            <p class="lead text-center mb-6">Manage all accounts here!</p>
        </div>
    </section>
    
    <!-- Add Account Form -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-4">Add New Account</h2>
            <form method="POST" enctype="multipart/form-data" class="bg-gray-800 rounded-xl p-6 max-w-lg">
                <div class="mb-4">
                    <label for="title" class="block text-gray-400 mb-2">Account Title</label>
                    <input type="text" id="title" name="title" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="game_id" class="block text-gray-400 mb-2">Game</label>
                    <select id="game_id" name="game_id" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="price" class="block text-gray-400 mb-2">Price</label>
                    <input type="number" step="0.01" id="price" name="price" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="status" class="block text-gray-400 mb-2">Status</label>
                    <select id="status" name="status" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="image" class="block text-gray-400 mb-2">Account Image</label>
                    <input type="file" id="image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none" required>
                </div>
                <button type="submit" name="add_account" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Add Account</button>
            </form>
        </div>
    </section>
    
    <!-- Accounts List -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($accounts as $account): ?>
                    <div class="bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                        <div class="h-48 overflow-hidden relative">
                            <img src="../assets/images/accounts/<?php echo htmlspecialchars($account['image']); ?>" alt="<?php echo htmlspecialchars($account['title']); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($account['title']); ?></h3>
                            <p class="text-gray-400 mb-2"><?php echo htmlspecialchars($account['game_name']); ?></p>
                            <p class="text-white font-bold mb-4">$<?php echo number_format($account['price'], 2); ?></p>
                            <p class="text-gray-400 mb-4">Status: <?php echo htmlspecialchars($account['status']); ?></p>
                            <div class="flex space-x-2">
                                <button onclick="openEditModal(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['title']); ?>', '<?php echo $account['game_id']; ?>', '<?php echo $account['price']; ?>', '<?php echo $account['status']; ?>')" class="text-green-400 hover:text-green-300">Edit</button>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="delete_account_id" value="<?php echo $account['id']; ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Edit Account Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-gray-800 rounded-xl p-6 max-w-lg w-full">
            <h2 class="text-2xl font-bold mb-4">Edit Account</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <div class="mb-4">
                    <label for="edit_title" class="block text-gray-400 mb-2">Account Title</label>
                    <input type="text" id="edit_title" name="title" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit_game_id" class="block text-gray-400 mb-2">Game</label>
                    <select id="edit_game_id" name="game_id" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_price" class="block text-gray-400 mb-2">Price</label>
                    <input type="number" step="0.01" id="edit_price" name="price" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit_status" class="block text-gray-400 mb-2">Status</label>
                    <select id="edit_status" name="status" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_image" class="block text-gray-400 mb-2">Account Image (optional)</label>
                    <input type="file" id="edit_image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" name="update_account" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Update</button>
                    <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account_id'])) {
        $account_id = $_POST['delete_account_id'];
        $sql = "DELETE FROM accounts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        header("Location: /kernelstore/admin/accounts.php");
        exit();
    }
    ?>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        function openEditModal(id, title, game_id, price, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_game_id').value = game_id;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>