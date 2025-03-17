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

// Add new user
if (isset($_POST['add_user'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $status = sanitizeInput($_POST['status']);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username or email already exists
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $existingUser = $stmt->get_result();
        
        if ($existingUser->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO users (username, email, password, is_admin, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssis", $username, $email, $hashedPassword, $is_admin, $status);
            
            if ($stmt->execute()) {
                $message = "User added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}

// Edit user
if (isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $status = sanitizeInput($_POST['status']);
    $password = $_POST['password'];
    
    // Check if username or email already exists for other users
    $sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();
    $existingUser = $stmt->get_result();
    
    if ($existingUser->num_rows > 0) {
        $error = "Username or email already exists";
    } else {
        // Update user
        if (!empty($password)) {
            // Update with new password
            if (strlen($password) < 6) {
                $error = "Password must be at least 6 characters";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, email = ?, password = ?, is_admin = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssisi", $username, $email, $hashedPassword, $is_admin, $status, $id);
            }
        } else {
            // Update without changing password
            $sql = "UPDATE users SET username = ?, email = ?, is_admin = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisi", $username, $email, $is_admin, $status, $id);
        }
        
        if (empty($error)) {
            if ($stmt->execute()) {
                $message = "User updated successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Don't allow deleting self
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        // Check if user has orders
        $checkSql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $row = $checkResult->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error = "Cannot delete user because they have orders. Consider deactivating the account instead.";
        } else {
            // Delete user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "User deleted successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}

// Get users with filtering
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$users = [];
$sql = "SELECT * FROM users WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";

$params = [];
$types = "";

if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($searchQuery)) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $countSql .= " AND (username LIKE ? OR email LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

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
        $users[] = $row;
    }
}

// Get total users count for pagination
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalUsers = $countRow['total'];
$totalPages = ceil($totalUsers / $limit);

// Get user for editing
$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $editUser = $result->fetch_assoc();
    }
}

// Check if we're adding a new user
$addUser = isset($_GET['action']) && $_GET['action'] == 'add';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editUser ? 'Edit User' : ($addUser ? 'Add User' : 'Manage Users'); ?> - KernelStore Admin</title>
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
            <a href="orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-shopping-cart mr-3"></i>
                <span>Orders</span>
            </a>
            
            <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Users</div>
            <a href="users.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
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
                <a href="orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    <span>Orders</span>
                </a>
                
                <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Users</div>
                <a href="users.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
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
            <h1 class="text-2xl font-bold"><?php echo $editUser ? 'Edit User' : ($addUser ? 'Add User' : 'Manage Users'); ?></h1>
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
        
        <?php if ($editUser || $addUser): ?>
            <!-- Add/Edit User Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <form action="users.php" method="POST">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                            <input type="text" id="username" name="username" class="form-input w-full rounded-md border-gray-300" value="<?php echo $editUser ? $editUser['username'] : ''; ?>" required>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                            <input type="email" id="email" name="email" class="form-input w-full rounded-md border-gray-300" value="<?php echo $editUser ? $editUser['email'] : ''; ?>" required>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-gray-700 font-medium mb-2">
                                <?php echo $editUser ? 'New Password (leave blank to keep current)' : 'Password'; ?>
                            </label>
                            <input type="password" id="password" name="password" class="form-input w-full rounded-md border-gray-300" <?php echo $editUser ? '' : 'required'; ?>>
                            <?php if (!$editUser): ?>
                                <p class="text-gray-500 text-sm mt-1">Password must be at least 6 characters</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$editUser): ?>
                            <div>
                                <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input w-full rounded-md border-gray-300" required>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <label for="status" class="block text-gray-700 font-medium mb-2">Status</label>
                            <select id="status" name="status" class="form-select w-full rounded-md border-gray-300" required>
                                <option value="active" <?php echo ($editUser && $editUser['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editUser && $editUser['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="banned" <?php echo ($editUser && $editUser['status'] == 'banned') ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="flex items-center mt-8">
                                <input type="checkbox" name="is_admin" class="form-checkbox rounded text-indigo-600" <?php echo ($editUser && $editUser['is_admin']) ? 'checked' : ''; ?>>
                                <span class="ml-2 text-gray-700">Administrator</span>
                            </label>
                            <p class="text-gray-500 text-sm mt-1">Administrators have full access to the admin panel</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <a href="users.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-md mr-2">
                            Cancel
                        </a>
                        <button type="submit" name="<?php echo $editUser ? 'edit_user' : 'add_user'; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            <?php echo $editUser ? 'Update User' : 'Add User'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Users List -->
            <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center space-y-4 md:space-y-0">
                <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
                    <h2 class="text-xl font-semibold">All Users</h2>
                    
                    <!-- Filter Form -->
                    <form action="users.php" method="GET" class="flex flex-wrap items-center space-x-2">
                        <div class="flex">
                            <input type="text" name="search" placeholder="Search username or email" class="form-input rounded-l-md border-gray-300 text-sm" value="<?php echo $searchQuery; ?>">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm py-2 px-3 rounded-r-md">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <select name="status" class="form-select rounded-md border-gray-300 text-sm">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo ($statusFilter == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($statusFilter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="banned" <?php echo ($statusFilter == 'banned') ? 'selected' : ''; ?>>Banned</option>
                        </select>
                        
                        <a href="users.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm py-2 px-3 rounded-md">
                            Reset
                        </a>
                    </form>
                </div>
                
                <a href="users.php?action=add" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md flex items-center justify-center md:justify-start">
                    <i class="fas fa-plus mr-2"></i> Add New User
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (count($users) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white mr-3">
                                                    <span class="font-medium"><?php echo substr($user['username'], 0, 1); ?></span>
                                                </div>
                                                <span class="font-medium text-gray-900"><?php echo $user['username']; ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['email']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($user['is_admin']): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                    Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    User
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($user['status'] == 'active'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            <?php elseif ($user['status'] == 'inactive'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Inactive
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Banned
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="users.php?edit=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="users.php?delete=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this user?');">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            <?php endif; ?>
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
                                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $totalUsers); ?> of <?php echo $totalUsers; ?> users
                                </div>
                                <div class="flex space-x-1">
                                    <?php if ($page > 1): ?>
                                        <a href="users.php?page=<?php echo $page - 1; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="users.php?page=<?php echo $i; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="users.php?page=<?php echo $page + 1; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        <p>No users found. <a href="users.php?action=add" class="text-indigo-600 hover:underline">Add your first user</a>.</p>
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