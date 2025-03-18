<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();
requireAdmin($conn);

// جلب الحسابات مع التأكد من إن الـ Query بتجيب كل الأعمدة
$sql_accounts = "SELECT a.*, g.name AS game_name FROM accounts a LEFT JOIN games g ON a.game_id = g.id";
$result_accounts = $conn->query($sql_accounts);
$accounts = $result_accounts->fetch_all(MYSQLI_ASSOC);

// جلب الألعاب مع التأكد من إن الـ Query بتجيب كل الأعمدة
$sql_games = "SELECT * FROM games";
$result_games = $conn->query($sql_games);
$games = $result_games->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_account'])) {
        $title = trim($_POST['title'] ?? '');
        $game_id = (int)($_POST['game_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $platform = $_POST['platform'] ?? 'Unknown';
        $description = trim($_POST['description'] ?? '');
        $image = $_FILES['account_image']['name'];
        $target_dir = "../assets/images/accounts/";
        $target_file = $target_dir . basename($image);

        if (empty($title) || $game_id <= 0 || $price <= 0 || empty($image)) {
            $error = "All fields (Title, Game, Price, Image) are required!";
        } else {
            if (move_uploaded_file($_FILES['account_image']['tmp_name'], $target_file)) {
                $sql = "INSERT INTO accounts (title, game_id, price, platform, description, image) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("sidsss", $title, $game_id, $price, $platform, $description, $image);
                    if ($stmt->execute()) {
                        $success = "Account added successfully!";
                        updateGameAccountCount($conn, $game_id);
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
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
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $game_id = (int)($_POST['game_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $platform = $_POST['platform'] ?? 'Unknown';
        $description = trim($_POST['description'] ?? '');
        $image = isset($_FILES['account_image']) && $_FILES['account_image']['name'] ? $_FILES['account_image']['name'] : null;

        if (empty($title) || $game_id <= 0 || $price <= 0 || $id <= 0) {
            $error = "All fields (Title, Game, Price, ID) are required!";
        } else {
            if ($image) {
                $target_dir = "../assets/images/accounts/";
                $target_file = $target_dir . basename($image);
                if (!move_uploaded_file($_FILES['account_image']['tmp_name'], $target_file)) {
                    $error = "Failed to upload image.";
                } else {
                    $sql = "UPDATE accounts SET title = ?, game_id = ?, price = ?, platform = ?, description = ?, image = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        $error = "Prepare failed: " . $conn->error;
                    } else {
                        $stmt->bind_param("sidsssi", $title, $game_id, $price, $platform, $description, $image, $id);
                        if ($stmt->execute()) {
                            $success = "Account updated successfully!";
                            updateGameAccountCount($conn, $game_id);
                        } else {
                            $error = "Failed to update account: " . $conn->error;
                        }
                        $stmt->close();
                    }
                }
            } else {
                $sql = "UPDATE accounts SET title = ?, game_id = ?, price = ?, platform = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("sidssi", $title, $game_id, $price, $platform, $description, $id);
                    if ($stmt->execute()) {
                        $success = "Account updated successfully!";
                        updateGameAccountCount($conn, $game_id);
                    } else {
                        $error = "Failed to update account: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    } elseif (isset($_POST['delete_account_id'])) {
        $account_id = (int)($_POST['delete_account_id'] ?? 0);
        if ($account_id > 0) {
            $sql_check = "SELECT game_id FROM accounts WHERE id = ?";
            $stmt_check = $conn->prepare($sql_check);
            if ($stmt_check === false) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt_check->bind_param("i", $account_id);
                $stmt_check->execute();
                $result = $stmt_check->get_result();
                $game_id = $result->fetch_assoc()['game_id'] ?? 0;
                $stmt_check->close();

                $sql_delete_orders = "DELETE FROM orders WHERE account_id = ?";
                $stmt_delete_orders = $conn->prepare($sql_delete_orders);
                if ($stmt_delete_orders === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt_delete_orders->bind_param("i", $account_id);
                    if (!$stmt_delete_orders->execute()) {
                        $error = "Failed to delete related orders: " . $conn->error;
                    }
                    $stmt_delete_orders->close();
                }

                $sql = "DELETE FROM accounts WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("i", $account_id);
                    if ($stmt->execute()) {
                        $success = "Account deleted successfully!";
                        updateGameAccountCount($conn, $game_id);
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        $error = "Failed to delete account: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } else {
            $error = "Invalid account ID!";
        }
    } elseif (isset($_POST['delete_game_id'])) {
        $game_id = (int)($_POST['delete_game_id'] ?? 0);
        if ($game_id > 0) {
            $sql_check = "SELECT COUNT(*) as count FROM accounts WHERE game_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            if ($stmt_check === false) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt_check->bind_param("i", $game_id);
                $stmt_check->execute();
                $result = $stmt_check->get_result();
                $row = $result->fetch_assoc();
                $stmt_check->close();

                if ($row['count'] > 0) {
                    $error = "Cannot delete game with associated accounts!";
                } else {
                    $sql = "DELETE FROM games WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        $error = "Prepare failed: " . $conn->error;
                    } else {
                        $stmt->bind_param("i", $game_id);
                        if ($stmt->execute()) {
                            $success = "Game deleted successfully!";
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit();
                        } else {
                            $error = "Failed to delete game: " . $conn->error;
                        }
                        $stmt->close();
                    }
                }
            }
        } else {
            $error = "Invalid game ID!";
        }
    } elseif (isset($_POST['add_game'])) {
        $name = trim($_POST['game_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = $_FILES['game_image']['name'];
        $target_dir = "../assets/images/games/";
        $target_file = $target_dir . basename($image);

        if (empty($name)) {
            $error = "Game name is required!";
        } else {
            if (!empty($image) && move_uploaded_file($_FILES['game_image']['tmp_name'], $target_file)) {
                $sql = "INSERT INTO games (name, account_count, image, description) VALUES (?, 0, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("sss", $name, $image, $description);
                    if ($stmt->execute()) {
                        $success = "Game added successfully!";
                        $name = $description = '';
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        $error = "Failed to add game: " . $conn->error;
                    }
                    $stmt->close();
                }
            } else {
                $sql = "INSERT INTO games (name, account_count, description) VALUES (?, 0, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("ss", $name, $description);
                    if ($stmt->execute()) {
                        $success = "Game added successfully!";
                        $name = $description = '';
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        $error = "Failed to add game: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    } elseif (isset($_POST['update_game'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['game_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = isset($_FILES['game_image']) && $_FILES['game_image']['name'] ? $_FILES['game_image']['name'] : null;

        if (empty($name) || $id <= 0) {
            $error = "Game name and ID are required!";
        } else {
            if ($image) {
                $target_dir = "../assets/images/games/";
                $target_file = $target_dir . basename($image);
                if (!move_uploaded_file($_FILES['game_image']['tmp_name'], $target_file)) {
                    $error = "Failed to upload game image.";
                } else {
                    $sql = "UPDATE games SET name = ?, description = ?, image = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        $error = "Prepare failed: " . $conn->error;
                    } else {
                        $stmt->bind_param("sssi", $name, $description, $image, $id);
                        if ($stmt->execute()) {
                            $success = "Game updated successfully!";
                        } else {
                            $error = "Failed to update game: " . $conn->error;
                        }
                        $stmt->close();
                    }
                }
            } else {
                $sql = "UPDATE games SET name = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("ssi", $name, $description, $id);
                    if ($stmt->execute()) {
                        $success = "Game updated successfully!";
                    } else {
                        $error = "Failed to update game: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

function updateGameAccountCount($conn, $game_id) {
    $sql = "UPDATE games SET account_count = (SELECT COUNT(*) FROM accounts WHERE game_id = ?) WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Log error if needed
    } else {
        $stmt->bind_param("ii", $game_id, $game_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }
        .grid-item {
            background-color: #2d3748;
            border-radius: 0.75rem;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .grid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(99, 102, 241, 0.2);
        }
        .grid-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .grid-item-content {
            padding: 1rem;
        }
        .btn-edit, .btn-delete, .btn-details {
            background-color: #4b5563;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            transition: background-color 0.3s;
            cursor: pointer;
            margin: 0 2px;
        }
        .btn-edit:hover {
            background-color: #10b981; /* Green for Edit */
        }
        .btn-delete:hover {
            background-color: #ef4444; /* Red for Delete */
        }
        .btn-details:hover {
            background-color: #6366f1; /* Indigo for Details */
        }
        .description-full {
            color: #a0aec0; /* text-gray-400 (شفاف نسبي) */
            -webkit-line-clamp: 2;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>
            <p class="lead text-center mb-6">Manage accounts and games here!</p>
        </div>
    </section>

    <?php if ($error): ?>
        <div class="container mx-auto px-4 mb-4">
            <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg"><?php echo $error; ?></div>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="container mx-auto px-4 mb-4">
            <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg"><?php echo $success; ?></div>
        </div>
    <?php endif; ?>

    <section class="py-8">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Add Account Form -->
            <div class="bg-gray-800 rounded-xl p-6">
                <h2 class="text-2xl font-bold mb-4">Add New Account</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="title" class="block text-gray-400 mb-2">Account Title</label>
                            <input type="text" id="title" name="title" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="game_id" class="block text-gray-400 mb-2">Game</label>
                            <select id="game_id" name="game_id" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">Select Game</option>
                                <?php foreach ($games as $game): ?>
                                    <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="price" class="block text-gray-400 mb-2">Price</label>
                            <input type="number" step="0.01" id="price" name="price" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="platform" class="block text-gray-400 mb-2">Platform</label>
                            <select id="platform" name="platform" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="Unknown">Select Platform</option>
                                <option value="PlayStation 5">PlayStation 5</option>
                                <option value="Xbox">Xbox</option>
                                <option value="PC">PC</option>
                            </select>
                        </div>
                        <div>
                            <label for="description" class="block text-gray-400 mb-2">Description</label>
                            <textarea id="description" name="description" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="3"></textarea>
                        </div>
                        <div>
                            <label for="account_image" class="block text-gray-400 mb-2">Account Image</label>
                            <input type="file" id="account_image" name="account_image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none" required>
                        </div>
                        <button type="submit" name="add_account" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Add Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- Add Game Form -->
            <div class="bg-gray-800 rounded-xl p-6">
                <h2 class="text-2xl font-bold mb-4">Add New Game</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="game_name" class="block text-gray-400 mb-2">Game Name</label>
                            <input type="text" id="game_name" name="game_name" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="description" class="block text-gray-400 mb-2">Description</label>
                            <textarea id="description" name="description" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="3"></textarea>
                        </div>
                        <div>
                            <label for="game_image" class="block text-gray-400 mb-2">Game Image</label>
                            <input type="file" id="game_image" name="game_image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
                        </div>
                        <button type="submit" name="add_game" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Add Game
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Accounts Grid -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-6">Accounts</h2>
            <div class="grid-container">
                <?php foreach ($accounts as $account): ?>
                    <div class="grid-item">
                        <img src="../assets/images/accounts/<?php echo htmlspecialchars($account['image'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($account['title'] ?? 'No title'); ?>" onerror="this.onerror=null; this.src='../assets/images/default.jpg';">
                        <div class="grid-item-content">
                            <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($account['title'] ?? 'No title'); ?></h3>
                            <p class="text-gray-400 text-sm mb-1">Game: <?php echo htmlspecialchars($account['game_name'] ?? 'No game'); ?></p>
                            <p class="text-gray-400 text-sm mb-1">Platform: <?php echo htmlspecialchars($account['platform'] ?? 'Unknown'); ?></p>
                            <p class="text-white font-bold mb-2">$<?php echo number_format($account['price'] ?? 0, 2); ?></p>
                            <p class="description-full"><?php echo htmlspecialchars($account['description'] ?? 'No description'); ?></p>
                            <div class="flex justify-between mt-2">
                                <div class="space-x-2">
                                    <button onclick="openEditModal('account', <?php echo $account['id']; ?>, '<?php echo addslashes(htmlspecialchars($account['title'] ?? '')); ?>', '<?php echo $account['game_id'] ?? ''; ?>', '<?php echo $account['price'] ?? 0; ?>', '<?php echo addslashes(htmlspecialchars($account['platform'] ?? 'Unknown')); ?>', '<?php echo addslashes(htmlspecialchars($account['description'] ?? '')); ?>'); console.log('Edit clicked for ID: <?php echo $account['id']; ?>');" class="btn-edit">Edit</button>
                                    <button onclick="confirmDelete('account', <?php echo $account['id']; ?>); console.log('Delete clicked for ID: <?php echo $account['id']; ?>');" class="btn-delete">Delete</button>
                                </div>
                                <button onclick="openDetailsModal('<?php echo addslashes(htmlspecialchars($account['description'] ?? 'No description')); ?>'); console.log('Details clicked for ID: <?php echo $account['id']; ?>');" class="btn-details">Details</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Games Grid -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-6">Games</h2>
            <div class="grid-container">
                <?php foreach ($games as $game): ?>
                    <div class="grid-item">
                        <img src="../assets/images/games/<?php echo htmlspecialchars($game['image'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($game['name'] ?? 'No name'); ?>" onerror="this.onerror=null; this.src='../assets/images/default.jpg';">
                        <div class="grid-item-content">
                            <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($game['name'] ?? 'No name'); ?></h3>
                            <p class="text-gray-400 text-sm mb-1">Accounts: <?php echo $game['account_count'] ?? 0; ?></p>
                            <p class="description-full"><?php echo htmlspecialchars($game['description'] ?? 'No description'); ?></p>
                            <div class="flex justify-between mt-2">
                                <div class="space-x-2">
                                    <button onclick="openEditModal('game', <?php echo $game['id']; ?>, '<?php echo addslashes(htmlspecialchars($game['name'] ?? '')); ?>', '', '', '', '<?php echo addslashes(htmlspecialchars($game['description'] ?? '')); ?>'); console.log('Edit clicked for ID: <?php echo $game['id']; ?>');" class="btn-edit">Edit</button>
                                    <button onclick="confirmDelete('game', <?php echo $game['id']; ?>); console.log('Delete clicked for ID: <?php echo $game['id']; ?>');" class="btn-delete">Delete</button>
                                </div>
                                <button onclick="openDetailsModal('<?php echo addslashes(htmlspecialchars($game['description'] ?? 'No description')); ?>'); console.log('Details clicked for ID: <?php echo $game['id']; ?>');" class="btn-details">Details</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-gray-800 rounded-xl p-6 max-w-lg w-full">
            <h2 id="modalTitle" class="text-2xl font-bold mb-4"></h2>
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" id="edit_type" name="type">
                <div class="mb-4" id="edit_title_div">
                    <label for="edit_title" class="block text-gray-400 mb-2">Title</label>
                    <input type="text" id="edit_title" name="title" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4" id="edit_game_id_div" style="display: none;">
                    <label for="edit_game_id" class="block text-gray-400 mb-2">Game</label>
                    <select id="edit_game_id" name="game_id" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4" id="edit_price_div" style="display: none;">
                    <label for="edit_price" class="block text-gray-400 mb-2">Price</label>
                    <input type="number" step="0.01" id="edit_price" name="price" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4" id="edit_platform_div" style="display: none;">
                    <label for="edit_platform" class="block text-gray-400 mb-2">Platform</label>
                    <select id="edit_platform" name="platform" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="Unknown">Select Platform</option>
                        <option value="PlayStation 5">PlayStation 5</option>
                        <option value="Xbox">Xbox</option>
                        <option value="PC">PC</option>
                    </select>
                </div>
                <div class="mb-4" id="edit_description_div">
                    <label for="edit_description" class="block text-gray-400 mb-2">Description</label>
                    <textarea id="edit_description" name="description" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="3"></textarea>
                </div>
                <div class="mb-4" id="edit_image_div" style="display: none;">
                    <label for="edit_image" class="block text-gray-400 mb-2">Account Image</label>
                    <input type="file" id="edit_image" name="account_image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
                </div>
                <div class="mb-4" id="edit_game_image_div" style="display: none;">
                    <label for="edit_game_image" class="block text-gray-400 mb-2">Game Image</label>
                    <input type="file" id="edit_game_image" name="game_image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" id="submit_button" name="update_account" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Update
                    </button>
                    <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-gray-800 rounded-xl p-6 max-w-lg w-full">
            <h2 class="text-2xl font-bold mb-4">Details</h2>
            <p id="detailsContent" class="text-gray-300 mb-4 description-full"></p>
            <button onclick="closeDetailsModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                Close
            </button>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        function openEditModal(type, id, title, game_id = '', price = '', platform = '', description = '') {
            console.log('openEditModal called:', { type, id, title, game_id, price, platform, description });
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_type').value = type;
            document.getElementById('modalTitle').textContent = type === 'account' ? 'Edit Account' : 'Edit Game';

            const titleDiv = document.getElementById('edit_title_div');
            const gameIdDiv = document.getElementById('edit_game_id_div');
            const priceDiv = document.getElementById('edit_price_div');
            const platformDiv = document.getElementById('edit_platform_div');
            const descriptionDiv = document.getElementById('edit_description_div');
            const imageDiv = document.getElementById('edit_image_div');
            const gameImageDiv = document.getElementById('edit_game_image_div');
            const submitButton = document.getElementById('submit_button');

            titleDiv.style.display = 'block';
            gameIdDiv.style.display = 'none';
            priceDiv.style.display = 'none';
            platformDiv.style.display = 'none';
            descriptionDiv.style.display = 'block';
            imageDiv.style.display = 'none';
            gameImageDiv.style.display = 'none';
            submitButton.name = type === 'account' ? 'update_account' : 'update_game';

            if (type === 'account') {
                gameIdDiv.style.display = 'block';
                priceDiv.style.display = 'block';
                platformDiv.style.display = 'block';
                imageDiv.style.display = 'block';
                document.getElementById('edit_title').value = title.replace(/'/g, "'");
                document.getElementById('edit_game_id').value = game_id || '';
                document.getElementById('edit_price').value = price || 0;
                document.getElementById('edit_platform').value = platform.replace(/'/g, "'") || 'Unknown';
                document.getElementById('edit_description').value = description.replace(/'/g, "'") || '';
            } else if (type === 'game') {
                gameImageDiv.style.display = 'block';
                document.getElementById('edit_title').value = title.replace(/'/g, "'");
                document.getElementById('edit_description').value = description.replace(/'/g, "'") || '';
            }

            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            console.log('closeEditModal called');
            document.getElementById('editModal').classList.add('hidden');
        }

        function openDetailsModal(description) {
            console.log('openDetailsModal called with:', description);
            document.getElementById('detailsContent').textContent = description.replace(/'/g, "'");
            const modal = document.getElementById('detailsModal');
            modal.classList.remove('hidden');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeDetailsModal();
                }
            });
        }

        function closeDetailsModal() {
            console.log('closeDetailsModal called');
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function confirmDelete(type, id) {
            console.log('confirmDelete called with:', { type, id });
            if (confirm(`Are you sure you want to delete this ${type === 'account' ? 'account' : 'game'}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.action = window.location.href;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = type === 'account' ? 'delete_account_id' : 'delete_game_id';
                input.value = id;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        }
    </script>
</body>
</html>