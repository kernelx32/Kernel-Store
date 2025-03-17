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

// Update settings
if (isset($_POST['update_settings'])) {
    $siteName = sanitizeInput($_POST['site_name']);
    $siteDescription = sanitizeInput($_POST['site_description']);
    $contactEmail = sanitizeInput($_POST['contact_email']);
    $currency = sanitizeInput($_POST['currency']);
    $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // Update settings in database
    $settings = [
        'site_name' => $siteName,
        'site_description' => $siteDescription,
        'contact_email' => $contactEmail,
        'currency' => $currency,
        'maintenance_mode' => $maintenanceMode
    ];
    
    $success = true;
    
    foreach ($settings as $key => $value) {
        $sql = "INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $key, $value, $value);
        
        if (!$stmt->execute()) {
            $success = false;
            $error = "Error updating settings: " . $stmt->error;
            break;
        }
    }
    
    if ($success) {
        $message = "Settings updated successfully!";
    }
}

// Get current settings
$settings = [];
$sql = "SELECT setting_key, setting_value FROM settings";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Set default values if not set
$settings['site_name'] = $settings['site_name'] ?? 'KernelStore';
$settings['site_description'] = $settings['site_description'] ?? 'Premium Gaming Accounts Marketplace';
$settings['contact_email'] = $settings['contact_email'] ?? 'support@kernelstore.com';
$settings['currency'] = $settings['currency'] ?? 'USD';
$settings['maintenance_mode'] = $settings['maintenance_mode'] ?? '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - KernelStore Admin</title>
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
            <a href="users.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-users mr-3"></i>
                <span>Manage Users</span>
            </a>
            <a href="reviews.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                <i class="fas fa-star mr-3"></i>
                <span>Reviews</span>
            </a>
            
            <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Settings</div>
            <a href="settings.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
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
                <a href="users.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-users mr-3"></i>
                    <span>Manage Users</span>
                </a>
                <a href="reviews.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-indigo-700 hover:text-white transition-colors">
                    <i class="fas fa-star mr-3"></i>
                    <span>Reviews</span>
                </a>
                
                <div class="px-6 py-2 mt-6 text-gray-300 text-xs font-semibold uppercase">Settings</div>
                <a href="settings.php" class="flex items-center px-6 py-3 text-white bg-indigo-700">
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
            <h1 class="text-2xl font-bold">Site Settings</h1>
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
        
        <!-- Settings Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="settings.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="site_name" class="block text-gray-700 font-medium mb-2">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-input w-full rounded-md border-gray-300" value="<?php echo $settings['site_name']; ?>" required>
                    </div>
                    
                    <div>
                        <label for="contact_email" class="block text-gray-700 font-medium mb-2">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-input w-full rounded-md border-gray-300" value="<?php echo $settings['contact_email']; ?>" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="site_description" class="block text-gray-700 font-medium mb-2">Site Description</label>
                        <textarea id="site_description" name="site_description" rows="3" class="form-textarea w-full rounded-md border-gray-300"><?php echo $settings['site_description']; ?></textarea>
                    </div>
                    
                    <div>
                        <label for="currency" class="block text-gray-700 font-medium mb-2">Currency</label>
                        <select id="currency" name="currency" class="form-select w-full rounded-md border-gray-300">
                            <option value="USD" <?php echo ($settings['currency'] == 'USD') ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="EUR" <?php echo ($settings['currency'] == 'EUR') ? 'selected' : ''; ?>>EUR (€)</option>
                            <option value="GBP" <?php echo ($settings['currency'] == 'GBP') ? 'selected' : ''; ?>>GBP (£)</option>
                            <option value="CAD" <?php echo ($settings['currency'] == 'CAD') ? 'selected' : ''; ?>>CAD ($)</option>
                            <option value="AUD" <?php echo ($settings['currency'] == 'AUD') ? 'selected' : ''; ?>>AUD ($)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="flex items-center mt-8">
                            <input type="checkbox" name="maintenance_mode" class="form-checkbox rounded text-indigo-600" <?php echo ($settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-700">Maintenance Mode</span>
                        </label>
                        <p class="text-gray-500 text-sm mt-1">When enabled, only administrators can access the site</p>
                    </div>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="submit" name="update_settings" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Additional Settings Sections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Payment Settings</h2>
                <p class="text-gray-500 mb-4">Configure payment gateways and options.</p>
                <a href="#" class="text-indigo-600 hover:text-indigo-900 font-medium">
                    <i class="fas fa-credit-card mr-1"></i> Configure Payment Methods
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Email Templates</h2>
                <p class="text-gray-500 mb-4">Customize email notifications sent to users.</p>
                <a href="#" class="text-indigo-600 hover:text-indigo-900 font-medium">
                    <i class="fas fa-envelope mr-1"></i> Manage Email Templates
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">System Information</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">PHP Version:</span>
                        <span class="font-medium"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">MySQL Version:</span>
                        <span class="font-medium"><?php echo $conn->server_info; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Server Software:</span>
                        <span class="font-medium"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">KernelStore Version:</span>
                        <span class="font-medium">1.0.0</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Backup & Restore</h2>
                <p class="text-gray-500 mb-4">Manage database backups and restore points.</p>
                <div class="space-y-3">
                    <a href="#" class="block text-indigo-600 hover:text-indigo-900 font-medium">
                        <i class="fas fa-download mr-1"></i> Create Backup
                    </a>
                    <a href="#" class="block text-indigo-600 hover:text-indigo-900 font-medium">
                        <i class="fas fa-upload mr-1"></i> Restore from Backup
                    </a>
                </div>
            </div>
        </div>
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