<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle form submissions
$message = '';
$error = '';

// Get all games for dropdown
$games = getAllGames($conn);

// Add new boosting service
if (isset($_POST['add_service'])) {
    $gameId = $_POST['game_id'];
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = sanitizeInput($_POST['duration']);
    $details = sanitizeInput($_POST['details']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = '../assets/images/boosting/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $fileName;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    } else {
        $error = "Please upload an image for the boosting service.";
    }
    
    if (empty($error)) {
        // Insert boosting service into database
        $sql = "INSERT INTO boosting_services (game_id, title, description, price, duration, details, image, active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdssi", $gameId, $title, $description, $price, $duration, $details, $imagePath, $active);
        
        if ($stmt->execute()) {
            $message = "Boosting service added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

// Edit boosting service
if (isset($_POST['edit_service'])) {
    $id = $_POST['id'];
    $gameId = $_POST['game_id'];
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = sanitizeInput($_POST['duration']);
    $details = sanitizeInput($_POST['details']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Handle image upload
    $imagePath = $_POST['current_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = '../assets/images/boosting/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $fileName;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    if (empty($error)) {
        // Update boosting service in database
        $sql = "UPDATE boosting_services SET game_id = ?, title = ?, description = ?, price = ?, duration = ?, details = ?, image = ?, active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdssii", $gameId, $title, $description, $price, $duration, $details, $imagePath, $active, $id);
        
        if ($stmt->execute()) {
            $message = "Boosting service updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

// Delete boosting service
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Delete boosting service
    $sql = "DELETE FROM boosting_services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Boosting service deleted successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Get boosting services with filtering
$gameFilter = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
$activeFilter = isset($_GET['active']) ? intval($_GET['active']) : -1;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$services = [];
$sql = "SELECT s.*, g.name as game_name 
        FROM boosting_services s 
        JOIN games g ON s.game_id = g.id 
        WHERE 1=1";

$countSql = "SELECT COUNT(*) as total FROM boosting_services s WHERE 1=1";

$params = [];
$types = "";

if ($gameFilter > 0) {
    $sql .= " AND s.game_id = ?";
    $countSql .= " AND s.game_id = ?";
    $params[] = $gameFilter;
    $types .= "i";
}

if ($activeFilter != -1) {
    $sql .= " AND s.active = ?";
    $countSql .= " AND s.active = ?";
    $params[] = $activeFilter;
    $types .= "i";
}

$sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $paramsCopy = $params;
    $paramsCopy[] = $limit;
    $paramsCopy[] = $offset;
    $stmt->bind_param($types . "ii", ...$paramsCopy);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Get total services count for pagination
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalServices = $countRow['total'];
$totalPages = ceil($totalServices / $limit);

// Get service for editing
$editService = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM boosting_services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $editService = $result->fetch_assoc();
    }
}

// Check if we're adding a new service
$addService = isset($_GET['action']) && $_GET['action'] == 'add';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editService ? 'Edit Boosting Service' : ($addService ? 'Add Boosting Service' : 'Manage Boosting Services'); ?> - KernelStore Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-gray-100 font-poppins min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-indigo-800 text-white fixed h-full z-10 hidden md:block">
        <div class="p-6">
            <a href="index.php" class="flex items-center">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mr-3">
                    <span class="text-indigo-800 font-bold text-xl">K</span>
                </div>
                <span class="text-white font-bold text-xl">KernelStore</span>
            </a>
        </div>
        
        <nav class="mt-6">
            <div class="px-6 py-2 text-gray-300 text-xs font-semibold uppercase">Main</div>
            <a href="index.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-tachometer-alt mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="games.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-gamepad mr-3"></i>
                <span>Games</span>
            </a>
            <a href="accounts.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-user-circle mr-3"></i>
                <span>Accounts</span>
            </a>
            <a href="boosting.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
                <i class="fas fa-rocket mr-3"></i>
                <span>Boosting</span>
            </a>
            <a href="orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-shopping-cart mr-3"></i>
                <span>Orders</span>
            </a>
            
            <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Users</div>
            <a href="users.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-users mr-3"></i>
                <span>Manage Users</span>
            </a>
            <a href="reviews.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-star mr-3"></i>
                <span>Reviews</span>
            </a>
            
            <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Settings</div>
            <a href="settings.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-cog mr-3"></i>
                <span>Site Settings</span>
            </a>
            <a href="../logout.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-sign-out-alt mr-3"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>
    
    <!-- Mobile Sidebar Toggle -->
    <div class="fixed bottom-4 right-4 md:hidden z-20">
        <button id="sidebarToggle" class="bg-indigo-600 text-white p-3 rounded-full shadow-lg">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <!-- Mobile Sidebar -->
    <div id="mobileSidebar" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-30 hidden">
        <div class="absolute right-0 top-0 h-full w-64 bg-indigo-800 text-white shadow-lg transform transition-transform duration-300 translate-x-full">
            <div class="p-6 flex justify-between items-center">
                <a href="index.php" class="flex items-center">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mr-3">
                        <span class="text-indigo-800 font-bold text-xl">K</span>
                    </div>
                    <span class="text-white font-bold text-xl">KernelStore</span>
                </a>
                <button id="closeSidebar" class="text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="mt-6">
                <div class="px-6 py-2 text-gray-300 text-xs font-semibold uppercase">Main</div>
                <a href="index.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Dashboard</span>
                </a>
                <a href="games.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-gamepad mr-3"></i>
                    <span>Games</span>
                </a>
                <a href="accounts.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-user-circle mr-3"></i>
                    <span>Accounts</span>
                </a>
                <a href="boosting.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
                    <i class="fas fa-rocket mr-3"></i>
                    <span>Boosting</span>
                </a>
                <a href="orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    <span>Orders</span>
                </a>
                
                <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Users</div>
                <a href="users.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-users mr-3"></i>
                    <span>Manage Users</span>
                </a>
                <a href="reviews.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-star mr-3"></i>
                    <span>Reviews</span>
                </a>
                
                <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Settings</div>
                <a href="settings.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Site Settings</span>
                </a>
                <a href="../logout.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="md:ml-64 flex-1 p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold"><?php echo $editService ? 'Edit Boosting Service' : ($addService ? 'Add Boosting Service' : 'Manage Boosting Services'); ?></h1>
            <div class="flex items-center">
                <span class="mr-2">Welcome, <?php echo $_SESSION['username']; ?></span>
                <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white">
                    <span class="font-medium"><?php echo substr($_SESSION['username'], 0, 1); ?></span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($editService || $addService): ?>
            <!-- Add/Edit Boosting Service Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <form action="boosting.php" method="POST" enctype="multipart/form-data">
                    <?php if ($editService): ?>
                        <input type="hidden" name="id" value="<?php echo $editService['id']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $editService['image']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="game_id" class="block text-gray-700 font-medium mb-2">Game</label>
                            <select id="game_id" name="game_id" class="form-select w-full rounded-md border-gray-300" required>
                                <option value="">Select Game</option>
                                <?php foreach ($games as $game): ?>
                                    <option value="<?php echo $game['id']; ?>" <?php echo ($editService && $editService['game_id'] == $game['id']) ? 'selected' : ''; ?>>
                                        <?php echo $game['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="flex items-center mt-8">
                                <input type="checkbox" name="active" class="form-checkbox rounded text-indigo-600" <?php echo ($editService && $editService['active']) ? 'checked' : ''; ?>>
                                <span class="ml-2 text-gray-700">Active</span>
                            </label>
                            <p class="text-gray-500 text-sm mt-1">Only active services are visible to users</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="title" class="block text-gray-700 font-medium mb-2">Service Title</label>
                            <input type="text" id="title" name="title" class="form-input w-full rounded-md border-gray-300" value="<?php echo $editService ? $editService['title'] : ''; ?>" required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
                            <textarea id="description" name="description" rows="4" class="form-textarea w-full rounded-md border-gray-300"><?php echo $editService ? $editService['description'] : ''; ?></textarea>
                        </div>
                        
                        <div>
                            <label for="price" class="block text-gray-700 font-medium mb-2">Price ($)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" class="form-input w-full rounded-md border-gray-300" value="<?php echo $editService ? $editService['price'] : ''; ?>" required>
                        </div>
                        
                        <div>
                            <label for="duration" class="block text-gray-700 font-medium mb-2">Estimated Duration</label>
                            <input type="text" id="duration" name="duration" class="form-input w-full rounded-md border-gray-300" value="<?php echo $editService ? $editService['duration'] : ''; ?>" placeholder="e.g., 2-3 days">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="details" class="block text-gray-700 font-medium mb-2">Service Details</label>
                            <textarea id="details" name="details" rows="6" class="form-textarea w-full rounded-md border-gray-300"><?php echo $editService ? $editService['details'] : ''; ?></textarea>
                            <p class="text-gray-500 text-sm mt-1">Enter detailed information about the boosting service, requirements, etc.</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-medium mb-2">Service Image</label>
                            
                            <?php if ($editService && !empty($editService['image'])): ?>
                                <div class="mb-4">
                                    <p class="text-gray-500 mb-2">Current Image:</p>
                                    <img src="../assets/images/boosting/<?php echo $editService['image']; ?>" alt="<?php echo $editService['title']; ?>" class="w-40 h-40 object-cover rounded-md">
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" id="image" name="image" class="form-input w-full" <?php echo $editService ? '' : 'required'; ?>>
                            <p class="text-gray-500 text-sm mt-1">Recommended size: 800x600 pixels</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <a href="boosting.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-md mr-2">
                            Cancel
                        </a>
                        <button type="submit" name="<?php echo $editService ? 'edit_service' : 'add_service'; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            <?php echo $editService ? 'Update Service' : 'Add Service'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Boosting Services List -->
            <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center space-y-4 md:space-y-0">
                <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
                    <h2 class="text-xl font-semibold">All Boosting Services</h2>
                    
                    <!-- Filter Form -->
                    <form action="boosting.php" method="GET" class="flex flex-wrap items-center space-x-2">
                        <select name="game_id" class="form-select rounded-md border-gray-300 text-sm">
                            <option value="0">All Games</option>
                            <?php foreach ($games as $game): ?>
                                <option value="<?php echo $game['id']; ?>" <?php echo ($gameFilter == $game['id']) ? 'selected' : ''; ?>>
                                    <?php echo $game['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="active" class="form-select rounded-md border-gray-300 text-sm">
                            <option value="-1">All Status</option>
                            <option value="1" <?php echo ($activeFilter === 1) ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ($activeFilter === 0) ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm py-2 px-3 rounded-md">
                            Filter
                        </button>
                        
                        <a href="boosting.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm py-2 px-3 rounded-md">
                            Reset
                        </a>
                    </form>
                </div>
                
                <a href="boosting.php?action=add" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center md:justify-start">
                    <i class="fas fa-plus mr-2"></i> Add New Service
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (count($services) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Game</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $service['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <img src="../assets/images/boosting/<?php echo $service['image']; ?>" alt="<?php echo $service['title']; ?>" class="w-12 h-12 object-cover rounded-md">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $service['game_name']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $service['title']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($service['price'], 2); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $service['duration']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($service['active']): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="boosting.php?edit=<?php echo $service['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="boosting.php?delete=<?php echo $service['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this service?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-500">
                                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $totalServices); ?> of <?php echo $totalServices; ?> services
                                </div>
                                <div class="flex space-x-1">
                                    <?php if ($page > 1): ?>
                                        <a href="boosting.php?page=<?php echo $page - 1; ?><?php echo $gameFilter ? '&game_id=' . $gameFilter : ''; ?><?php echo $activeFilter != -1 ? '&active=' . $activeFilter : ''; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="boosting.php?page=<?php echo $i; ?><?php echo $gameFilter ? '&game_id=' . $gameFilter : ''; ?><?php echo $activeFilter != -1 ? '&active=' . $activeFilter : ''; ?>" class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="boosting.php?page=<?php echo $page + 1; ?><?php echo $gameFilter ? '&game_id=' . $gameFilter : ''; ?><?php echo $activeFilter != -1 ? '&active=' . $activeFilter : ''; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        <p>No boosting services found. <a href="boosting.php?action=add" class="text-indigo-600 hover:underline">Add your first service</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <script>
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileSidebar = document.getElementById('mobileSidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebarContent = mobileSidebar.querySelector('.transform');
        
        sidebarToggle.addEventListener('click', () => {
            mobileSidebar.classList.remove('hidden');
            setTimeout(() => {
                sidebarContent.classList.remove('translate-x-full');
            }, 10);
        });
        
        closeSidebar.addEventListener('click', closeMobileSidebar);
        mobileSidebar.addEventListener('click', (e) => {
            if (e.target === mobileSidebar) {
                closeMobileSidebar();
            }
        });
        
        function closeMobileSidebar() {
            sidebarContent.classList.add('translate-x-full');
            setTimeout(() => {
                mobileSidebar.classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>