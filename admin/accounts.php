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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_account'])) {
        // إضافة حساب جديد
        $title = trim($_POST['title'] ?? '');
        $game_id = (int)($_POST['game_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $platform = $_POST['platform'] ?? 'Unknown';
        $image = $_FILES['image']['name'];
        $target_dir = "../assets/images/accounts/";
        $target_file = $target_dir . basename($image);

        if (empty($title) || $game_id <= 0 || $price <= 0 || empty($image)) {
            $error = "All fields (Title, Game, Price, Image) are required!";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $sql = "INSERT INTO accounts (title, game_id, price, platform, image) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("sidss", $title, $game_id, $price, $platform, $image);
                    if ($stmt->execute()) {
                        $success = "Account added successfully!";
                        $title = $price = '';
                    } else {
                        $error = "Failed to add account: " . $conn->error;
                    }
                    $stmt->close();
                }
            } else {
                $error = "Failed to upload image.";
            }
        }
    } elseif (isset($_POST['update_account'])) {
        // تعديل حساب
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $game_id = (int)($_POST['game_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $platform = $_POST['platform'] ?? 'Unknown';
        $image = isset($_FILES['image']) && $_FILES['image']['name'] ? $_FILES['image']['name'] : null;

        if (empty($title) || $game_id <= 0 || $price <= 0 || $id <= 0) {
            $error = "All fields (Title, Game, Price, ID) are required!";
        } else {
            if ($image) {
                $target_dir = "../assets/images/accounts/";
                $target_file = $target_dir . basename($image);
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $error = "Failed to upload image.";
                } else {
                    $sql = "UPDATE accounts SET title = ?, game_id = ?, price = ?, platform = ?, image = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sidssi", $title, $game_id, $price, $platform, $image, $id);
                }
            } else {
                $sql = "UPDATE accounts SET title = ?, game_id = ?, price = ?, platform = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sidsi", $title, $game_id, $price, $platform, $id);
            }
            if ($stmt === false) {
                $error = "Prepare failed: " . $conn->error;
            } elseif ($stmt->execute()) {
                $success = "Account updated successfully!";
            } else {
                $error = "Failed to update account: " . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_account_id'])) {
        // حذف حساب
        $account_id = (int)($_POST['delete_account_id'] ?? 0);
        if ($account_id > 0) {
            // تحقق من وجود طلبات مرتبطة وحذفها أولاً
            $sql_check = "SELECT COUNT(*) as count FROM orders WHERE account_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $account_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            $row = $result->fetch_assoc();
            $stmt_check->close();

            if ($row['count'] > 0) {
                $sql_delete_orders = "DELETE FROM orders WHERE account_id = ?";
                $stmt_delete_orders = $conn->prepare($sql_delete_orders);
                $stmt_delete_orders->bind_param("i", $account_id);
                if ($stmt_delete_orders->execute()) {
                    $stmt_delete_orders->close();
                } else {
                    $error = "Failed to delete related orders: " . $conn->error;
                    $stmt_delete_orders->close();
                }
            }

            // حذف الحساب
            $sql = "DELETE FROM accounts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("i", $account_id);
                if ($stmt->execute()) {
                    $success = "Account deleted successfully!";
                } else {
                    $error = "Failed to delete account: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Invalid account ID!";
        }
    }
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
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="bg-gray-800 rounded-xl p-6 max-w-lg">
                <div class="mb-4">
                    <label for="title" class="block text-gray-400 mb-2">Account Title</label>
                    <input type="text" id="title" name="title" value="" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="game_id" class="block text-gray-400 mb-2">Game</label>
                    <select id="game_id" name="game_id" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="">Select Game</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="price" class="block text-gray-400 mb-2">Price</label>
                    <input type="number" step="0.01" id="price" name="price" value="" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="platform" class="block text-gray-400 mb-2">Platform</label>
                    <select id="platform" name="platform" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="Unknown">Select Platform</option>
                        <option value="PlayStation 5">PlayStation 5</option>
                        <option value="Xbox">Xbox</option>
                        <option value="PC">PC</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="image" class="block text-gray-400 mb-2">Account Image</label>
                    <input type="file" id="image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none" required>
                </div>
                <button type="submit" name="add_account" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Add Account
                </button>
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
                            <p class="text-white font-bold mb-2">$<?php echo number_format($account['price'], 2); ?></p>
                            <p class="text-gray-400 mb-2">Platform: <?php echo htmlspecialchars($account['platform'] ?? 'Unknown'); ?></p>
                            <div class="flex space-x-2">
                                <button onclick="openEditModal(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['title']); ?>', '<?php echo $account['game_id']; ?>', '<?php echo $account['price']; ?>', '<?php echo htmlspecialchars($account['platform'] ?? 'Unknown'); ?>')" class="text-green-400 hover:text-green-300">Edit</button>
                                <button onclick="confirmDelete(<?php echo $account['id']; ?>)" class="text-red-400 hover:text-red-300">Delete</button>
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
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
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
                    <label for="edit_platform" class="block text-gray-400 mb-2">Platform</label>
                    <select id="edit_platform" name="platform" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="Unknown">Select Platform</option>
                        <option value="PlayStation 5">PlayStation 5</option>
                        <option value="Xbox">Xbox</option>
                        <option value="PC">PC</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_image" class="block text-gray-400 mb-2">Account Image (optional)</label>
                    <input type="file" id="edit_image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" name="update_account" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Update
                    </button>
                    <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account_id'])) {
        $account_id = (int)($_POST['delete_account_id'] ?? 0);
        if ($account_id > 0) {
            // تحقق من وجود طلبات مرتبطة وحذفها أولاً
            $sql_check = "SELECT COUNT(*) as count FROM orders WHERE account_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $account_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            $row = $result->fetch_assoc();
            $stmt_check->close();

            if ($row['count'] > 0) {
                $sql_delete_orders = "DELETE FROM orders WHERE account_id = ?";
                $stmt_delete_orders = $conn->prepare($sql_delete_orders);
                $stmt_delete_orders->bind_param("i", $account_id);
                if (!$stmt_delete_orders->execute()) {
                    $error = "Failed to delete related orders: " . $conn->error;
                }
                $stmt_delete_orders->close();
            }

            // حذف الحساب
            $sql = "DELETE FROM accounts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("i", $account_id);
                if ($stmt->execute()) {
                    $success = "Account deleted successfully!";
                } else {
                    $error = "Failed to delete account: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Invalid account ID!";
        }
    }
    ?>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        function openEditModal(id, title, game_id, price, platform) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_game_id').value = game_id;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_platform').value = platform;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function confirmDelete(accountId) {
            if (confirm('Are you sure you want to delete this account? This will also delete related orders.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_account_id';
                input.value = accountId;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>