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

// Update order status
if (isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $orderId);
    
    if ($stmt->execute()) {
        // If order is cancelled, make the account available again
        if ($status == 'cancelled') {
            $sql = "UPDATE accounts a 
                    JOIN orders o ON a.id = o.account_id 
                    SET a.status = 'available' 
                    WHERE o.id = ? AND a.status = 'reserved'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
        }
        
        // If order is completed, mark the account as sold
        if ($status == 'completed') {
            $sql = "UPDATE accounts a 
                    JOIN orders o ON a.id = o.account_id 
                    SET a.status = 'sold' 
                    WHERE o.id = ? AND a.status = 'reserved'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
        }
        
        $message = "Order status updated successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Delete order
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get order details to check if we need to update account status
    $sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if ($order) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete order
            $sql = "DELETE FROM orders WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // If order was pending or processing, make the account available again
            if ($order['status'] == 'pending' || $order['status'] == 'processing') {
                $sql = "UPDATE accounts SET status = 'available' WHERE id = ? AND status = 'reserved'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $order['account_id']);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            $message = "Order deleted successfully!";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Order not found.";
    }
}

// Get orders with filtering
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$orders = [];
$sql = "SELECT o.*, u.username, a.title as account_title, g.name as game_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        JOIN accounts a ON o.account_id = a.id 
        JOIN games g ON a.game_id = g.id 
        WHERE 1=1";

$countSql = "SELECT COUNT(*) as total FROM orders o WHERE 1=1";

$params = [];
$types = "";

if (!empty($statusFilter)) {
    $sql .= " AND o.status = ?";
    $countSql .= " AND o.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

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
        $orders[] = $row;
    }
}

// Get total orders count for pagination
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalOrders = $countRow['total'];
$totalPages = ceil($totalOrders / $limit);

// Get order details for viewing
$viewOrder = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $id = $_GET['view'];
    $sql = "SELECT o.*, u.username, u.email as user_email, a.title as account_title, a.email as account_email, a.password as account_password, g.name as game_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            JOIN accounts a ON o.account_id = a.id 
            JOIN games g ON a.game_id = g.id 
            WHERE o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $viewOrder = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $viewOrder ? 'Order #' . $viewOrder['id'] : 'Manage Orders'; ?> - KernelStore Admin</title>
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
            <a href="orders.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
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
                <a href="orders.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
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
            <h1 class="text-2xl font-bold"><?php echo $viewOrder ? 'Order #' . $viewOrder['id'] : 'Manage Orders'; ?></h1>
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
        
        <?php if ($viewOrder): ?>
            <!-- Order Details -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Order Details</h2>
                    <a href="orders.php" class="text-indigo-600 hover:text-indigo-900">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                    </a>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-gray-500 font-medium mb-2">Order Information</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-gray-500 text-sm">Order ID</p>
                                        <p class="font-medium">#<?php echo $viewOrder['id']; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-sm">Date</p>
                                        <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($viewOrder['created_at'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-sm">Status</p>
                                        <p>
                                            <?php if ($viewOrder['status'] == 'pending'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            <?php elseif ($viewOrder['status'] == 'processing'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Processing
                                                </span>
                                            <?php elseif ($viewOrder['status'] == 'completed'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Completed
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Cancelled
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-sm">Total</p>
                                        <p class="font-medium">$<?php echo number_format($viewOrder['price'], 2); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-gray-500 font-medium mb-2">Customer Information</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-gray-500 text-sm">Username</p>
                                        <p class="font-medium"><?php echo $viewOrder['username']; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-sm">Email</p>
                                        <p class="font-medium"><?php echo $viewOrder['user_email']; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-sm">User ID</p>
                                        <p class="font-medium">#<?php echo $viewOrder['user_id']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-gray-500 font-medium mb-2">Account Information</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-gray-500 text-sm">Game</p>
                                    <p class="font-medium"><?php echo $viewOrder['game_name']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Account Title</p>
                                    <p class="font-medium"><?php echo $viewOrder['account_title']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Account ID</p>
                                    <p class="font-medium">#<?php echo $viewOrder['account_id']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Account Email</p>
                                    <p class="font-medium"><?php echo $viewOrder['account_email']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Account Password</p>
                                    <p class="font-medium"><?php echo $viewOrder['account_password']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-gray-500 font-medium mb-2">Update Order Status</h3>
                        <form action="orders.php" method="POST" class="flex items-end space-x-4">
                            <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                            
                            <div class="flex-1">
                                <select name="status" class="form-select w-full rounded-md border-gray-300">
                                    <option value="pending" <?php echo ($viewOrder['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo ($viewOrder['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo ($viewOrder['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($viewOrder['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="update_status" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                                Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                <h2 class="text-xl font-semibold">All Orders</h2>
                
                <!-- Filter Form -->
                <form action="orders.php" method="GET" class="flex items-center space-x-2">
                    <select name="status" class="form-select rounded-md border-gray-300 text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($statusFilter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo ($statusFilter == 'processing') ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo ($statusFilter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($statusFilter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm py-2 px-3 rounded-md">
                        Filter
                    </button>
                    
                    <a href="orders.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm py-2 px-3 rounded-md">
                        Reset
                    </a>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (count($orders) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Game</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $order['username']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $order['game_name']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $order['account_title']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($order['price'], 2); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            <?php elseif ($order['status'] == 'processing'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Processing
                                                </span>
                                            <?php elseif ($order['status'] == 'completed'): ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Completed
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Cancelled
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="orders.php?view=<?php echo $order['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="orders.php?delete=<?php echo $order['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this order?');">
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
                                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $totalOrders); ?> of <?php echo $totalOrders; ?> orders
                                </div>
                                <div class="flex space-x-1">
                                    <?php if ($page > 1): ?>
                                        <a href="orders.php?page=<?php echo $page - 1; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="orders.php?page=<?php echo $i; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="orders.php?page=<?php echo $page + 1; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        <p>No orders found.</p>
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