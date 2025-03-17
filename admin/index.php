<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

// التأكد إن المستخدم مسجل دخوله وهو Admin
requireLogin();
requireAdmin($conn);

if (isset($_GET['error']) && $_GET['error'] == 'access_denied') {
    echo "<p class='text-red-400 text-center'>Access denied. You are not an admin.</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $name = $_POST['name'];
    $image = $_FILES['image']['name'];
    $target_dir = "../assets/images/games/";
    $target_file = $target_dir . basename($image);
    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);

    $sql = "INSERT INTO games (name, image, account_count) VALUES (?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $image);
    $stmt->execute();
    header("Location: /kernelstore/admin/index.php");
    exit();
}

$featured_games = getFeaturedGames($conn);
$featured_accounts = getFeaturedAccounts($conn);
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
    <link rel="stylesheet" href="/kernelstore/assets/css/admin.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>
            <p class="lead text-center mb-6">Manage your games and accounts here!</p>
        </div>
    </section>
    
    <!-- Add Game Form -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-4">Add New Game</h2>
            <form method="POST" enctype="multipart/form-data" class="bg-gray-800 rounded-xl p-6 max-w-lg">
                <div class="mb-4">
                    <label for="name" class="block text-gray-400 mb-2">Game Name</label>
                    <input type="text" id="name" name="name" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="image" class="block text-gray-400 mb-2">Game Image</label>
                    <input type="file" id="image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none" required>
                </div>
                <button type="submit" name="add_game" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Add Game</button>
            </form>
        </div>
    </section>
    
    <!-- Featured Games -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Featured Games</h2>
                <a href="games.php" class="text-indigo-400 hover:text-indigo-300">View All</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($featured_games as $game): ?>
                    <div class="bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                        <div class="h-48 overflow-hidden relative">
                            <img src="../assets/images/games/<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($game['name']); ?></h3>
                            <p class="text-gray-400 mb-4">Accounts: <?php echo $game['account_count']; ?></p>
                            <div class="flex space-x-2">
                                <a href="../accounts.php?game_id=<?php echo $game['id']; ?>" class="text-indigo-400 hover:text-indigo-300">View Accounts</a>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="delete_game_id" value="<?php echo $game['id']; ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Accounts -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Featured Accounts</h2>
                <a href="accounts.php" class="text-indigo-400 hover:text-indigo-300">View All</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($featured_accounts as $account): ?>
                    <div class="bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                        <div class="h-48 overflow-hidden relative">
                            <img src="../assets/images/accounts/<?php echo htmlspecialchars($account['image']); ?>" alt="<?php echo htmlspecialchars($account['title']); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($account['title']); ?></h3>
                            <p class="text-gray-400 mb-2"><?php echo htmlspecialchars($account['game_name']); ?></p>
                            <p class="text-white font-bold mb-4">$<?php echo number_format($account['price'], 2); ?></p>
                            <div class="flex space-x-2">
                                <a href="../account-details.php?id=<?php echo $account['id']; ?>" class="text-indigo-400 hover:text-indigo-300">View Details</a>
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
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game_id'])) {
        $game_id = $_POST['delete_game_id'];
        $sql = "DELETE FROM games WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        header("Location: /kernelstore/admin/index.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account_id'])) {
        $account_id = $_POST['delete_account_id'];
        $sql = "DELETE FROM accounts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        header("Location: /kernelstore/admin/index.php");
        exit();
    }
    ?>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>