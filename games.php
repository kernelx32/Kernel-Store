<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <span>K</span>
                    </div>
                    <span class="logo-text">KernelStore</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="games.php">
                            <i class="fas fa-gamepad"></i>
                            <span>Games</span>
                        </a>
                    </li>
                    <li>
                        <a href="accounts.php">
                            <i class="fas fa-user-circle"></i>
                            <span>Accounts</span>
                        </a>
                    </li>
                    <li>
                        <a href="boosting.php">
                            <i class="fas fa-rocket"></i>
                            <span>Boosting</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Site</span>
                </a>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><?php echo $editGame ? 'Edit Game' : 'Manage Games'; ?></h1>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                    <div class="user-avatar">
                        <img src="../assets/images/avatars/admin.jpg" alt="Admin">
                    </div>
                </div>
            </header>
            
            <?php if (!empty($message)): ?>
                <div class="success-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($editGame): ?>
                <!-- Edit Game Form -->
                <div class="form-card">
                    <form action="games.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $editGame['id']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $editGame['image']; ?>">
                        
                        <div class="form-group">
                            <label for="name">Game Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $editGame['name']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">Slug (URL-friendly name)</label>
                            <input type="text" id="slug" name="slug" class="form-control" value="<?php echo $editGame['slug']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4"><?php echo $editGame['description']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Current Image</label>
                            <?php if (!empty($editGame['image'])): ?>
                                <div class="image-preview">
                                    <img src="../<?php echo $editGame['image']; ?>" alt="<?php echo $editGame['name']; ?>" style="max-width: 200px;">
                                </div>
                            <?php else: ?>
                                <p>No image uploaded</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Upload New Image</label>
                            <input type="file" id="image" name="image" class="form-control">
                            <small>Leave empty to keep current image</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="featured" <?php echo $editGame['featured'] ? 'checked' : ''; ?>>
                                Featured Game
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <a href="games.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="edit_game" class="btn btn-primary">Update Game</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Add New Game Form -->
                <div class="form-card">
                    <h2>Add New Game</h2>
                    <form action="games.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Game Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">Slug (URL-friendly name)</label>
                            <input type="text" id="slug" name="slug" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Game Image</label>
                            <input type="file" id="image" name="image" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="featured">
                                Featured Game
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_game" class="btn btn-primary">Add Game</button>
                        </div>
                    </form>
                </div>
                
                <!-- Games List -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>All Games</h2>
                    </div>
                    <div class="card-content">
                        <?php if (count($games) > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Featured</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($games as $game): ?>
                                        <tr>
                                            <td><?php echo $game['id']; ?></td>
                                            <td>
                                                <?php if (!empty($game['image'])): ?>
                                                    <img src="../<?php echo $game['image']; ?>" alt="<?php echo $game['name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background-color: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-image" style="color: #999;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $game['name']; ?></td>
                                            <td><?php echo $game['slug']; ?></td>
                                            <td>
                                                <?php if ($game['featured']): ?>
                                                    <span class="status-badge status-completed">Yes</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-cancelled">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="games.php?edit=<?php echo $game['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                <a href="games.php?delete=<?php echo $game['id']; ?>" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this game?">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">No games found. Add your first game using the form above.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>