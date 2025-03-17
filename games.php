<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin


// Handle form submissions
$message = '';
$error = '';

// Add new game
if (isset($_POST['add_game'])) {
    $name = sanitizeInput($_POST['name']);
    $slug = sanitizeInput($_POST['slug']);
    $description = sanitizeInput($_POST['description']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = '../assets/images/';
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'assets/images/' . $fileName;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    if (empty($error)) {
        // Insert game into database
        $sql = "INSERT INTO games (name, slug, description, image, featured, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $slug, $description, $imagePath, $featured);
        
        if ($stmt->execute()) {
            $message = "Game added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

// Edit game
if (isset($_POST['edit_game'])) {
    $id = $_POST['id'];
    $name = sanitizeInput($_POST['name']);
    $slug = sanitizeInput($_POST['slug']);
    $description = sanitizeInput($_POST['description']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle image upload
    $imagePath = $_POST['current_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = '../assets/images/';
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'assets/images/' . $fileName;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    if (empty($error)) {
        // Update game in database
        $sql = "UPDATE games SET name = ?, slug = ?, description = ?, image = ?, featured = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $name, $slug, $description, $imagePath, $featured, $id);
        
        if ($stmt->execute()) {
            $message = "Game updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

// Delete game
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if game has accounts
    $checkSql = "SELECT COUNT(*) as count FROM accounts WHERE game_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $row = $checkResult->fetch_assoc();
    
    if ($row['count'] > 0) {
        $error = "Cannot delete game because it has associated accounts. Delete the accounts first.";
    } else {
        // Delete game
        $sql = "DELETE FROM games WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Game deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

// Get all games
$games = getAllGames($conn);

// Get game for editing
$editGame = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM games WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $editGame = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games - KernelStore Admin</title>
    <!-- Use the same CSS as the main site -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Use the main site's header -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Add a header section similar to the main site -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6 text-center"><?php echo $editGame ? 'Edit Game' : 'Manage Games'; ?></h1>
            <p class="lead text-center mb-6">Admin panel for managing games</p>
        </div>
    </section>
    
    <!-- Main Content -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <?php if (!empty($message)): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6 text-center">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6 text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($editGame): ?>
                <!-- Edit Game Form -->
                <div class="max-w-2xl mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                    <form action="games.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $editGame['id']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $editGame['image']; ?>">
                        
                        <div class="mb-4">
                            <label for="name" class="block text-gray-400 mb-2">Game Name</label>
                            <input type="text" id="name" name="name" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo $editGame['name']; ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="slug" class="block text-gray-400 mb-2">Slug (URL-friendly name)</label>
                            <input type="text" id="slug" name="slug" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?php echo $editGame['slug']; ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-gray-400 mb-2">Description</label>
                            <textarea id="description" name="description" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="4"><?php echo $editGame['description']; ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-400 mb-2">Current Image</label>
                            <?php if (!empty($editGame['image'])): ?>
                                <div class="image-preview mb-4">
                                    <img src="../<?php echo $editGame['image']; ?>" alt="<?php echo $editGame['name']; ?>" class="max-w-full h-auto rounded-lg" style="max-width: 200px;">
                                </div>
                            <?php else: ?>
                                <p class="text-gray-400">No image uploaded</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label for="image" class="block text-gray-400 mb-2">Upload New Image</label>
                            <input type="file" id="image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2">
                            <small class="text-gray-400">Leave empty to keep current image</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center text-gray-400">
                                <input type="checkbox" name="featured" class="mr-2" <?php echo $editGame['featured'] ? 'checked' : ''; ?>>
                                Featured Game
                            </label>
                        </div>
                        
                        <div class="flex justify-between">
                            <a href="games.php" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Cancel</a>
                            <button type="submit" name="edit_game" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Update Game</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Add New Game Form -->
                <div class="max-w-2xl mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300 mb-12">
                    <h2 class="text-2xl font-bold mb-6">Add New Game</h2>
                    <form action="games.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="name" class="block text-gray-400 mb-2">Game Name</label>
                            <input type="text" id="name" name="name" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="slug" class="block text-gray-400 mb-2">Slug (URL-friendly name)</label>
                            <input type="text" id="slug" name="slug" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-gray-400 mb-2">Description</label>
                            <textarea id="description" name="description" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="4"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="image" class="block text-gray-400 mb-2">Game Image</label>
                            <input type="file" id="image" name="image" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2">
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center text-gray-400">
                                <input type="checkbox" name="featured" class="mr-2">
                                Featured Game
                            </label>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="add_game" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Add Game</button>
                        </div>
                    </form>
                </div>
                
                <!-- Games List -->
                <div class="max-w-4xl mx-auto bg-gray-800 rounded-xl p-8 shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                    <h2 class="text-2xl font-bold mb-6">All Games</h2>
                    <?php if (count($games) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-gray-700">
                                        <th class="py-3 px-4">ID</th>
                                        <th class="py-3 px-4">Image</th>
                                        <th class="py-3 px-4">Name</th>
                                        <th class="py-3 px-4">Slug</th>
                                        <th class="py-3 px-4">Featured</th>
                                        <th class="py-3 px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($games as $game): ?>
                                        <tr class="border-b border-gray-700">
                                            <td class="py-3 px-4"><?php echo $game['id']; ?></td>
                                            <td class="py-3 px-4">
                                                <?php if (!empty($game['image'])): ?>
                                                    <img src="../<?php echo $game['image']; ?>" alt="<?php echo $game['name']; ?>" class="w-12 h-12 object-cover rounded-lg">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 bg-gray-600 rounded-lg flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4"><?php echo $game['name']; ?></td>
                                            <td class="py-3 px-4"><?php echo $game['slug']; ?></td>
                                            <td class="py-3 px-4">
                                                <?php if ($game['featured']): ?>
                                                    <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">Yes</span>
                                                <?php else: ?>
                                                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 flex space-x-2">
                                                <a href="games.php?edit=<?php echo $game['id']; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg text-sm transition-colors">Edit</a>
                                                <a href="games.php?delete=<?php echo $game['id']; ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg text-sm transition-colors" onclick="return confirm('Are you sure you want to delete this game?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-400">No games found. Add your first game using the form above.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Use the main site's footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>