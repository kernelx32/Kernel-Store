<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

// التأكد إن المستخدم مسجل دخوله وهو Admin
requireLogin();
requireAdmin($conn);

// تعديل لعبة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_game'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $image = isset($_FILES['image']) && $_FILES['image']['name'] ? $_FILES['image']['name'] : null;
    if ($image) {
        $target_dir = "../assets/images/games/";
        $target_file = $target_dir . basename($image);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            die("Failed to upload image.");
        }
    }
    updateGame($conn, $id, $name, $image);
    header("Location: /kernelstore/admin/games.php");
    exit();
}

$featured_games = getFeaturedGames($conn); // بنجيب الألعاب الأحدث
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Games - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6">Admin Games</h1>
            <p class="lead text-center mb-6">Manage all games here!</p>
        </div>
    </section>

    <!-- Banner with Add Game Button -->
    <section class="bg-gradient-to-r from-indigo-900 to-purple-900 py-8">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-2xl font-bold mb-4">Ready to Add a New Game?</h2>
            <a href="/kernelstore/admin/add-game.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">Add Game</a>
        </div>
    </section>
    
    <!-- Games List -->
    <section class="py-16">
        <div class="container mx-auto px-4">
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
                                <a href="/kernelstore/admin/accounts.php?game_id=<?php echo $game['id']; ?>" class="text-indigo-400 hover:text-indigo-300">View Accounts</a>
                                <button onclick="openEditModal(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars($game['name']); ?>')" class="text-green-400 hover:text-green-300">Edit</button>
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
    
    <!-- Edit Game Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-gray-800 rounded-xl p-6 max-w-lg w-full">
            <h2 class="text-2xl font-bold mb-4">Edit Game</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <div class="mb-4">
                    <label for="edit_name" class="block text-gray-400 mb-2">Game Name</label>
                    <input type="text" id="edit_name" name="name" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="edit_image" class="block text-gray-400 mb-2">Game Image (optional)</label>
                    <input type="file" id="edit_image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" name="update_game" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Update</button>
                    <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game_id'])) {
        $game_id = $_POST['delete_game_id'];
        $sql = "DELETE FROM games WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        header("Location: /kernelstore/admin/games.php");
        exit();
    }
    ?>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        function openEditModal(id, name) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>