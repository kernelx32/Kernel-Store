<?php
// session_start(); // شيلناها من هنا لأن الجلسة بتفعّل في admin/index.php
require_once dirname(__DIR__) . '/config/database.php'; // مسار نسبي صحيح
require_once __DIR__ . '/auth.php';
?>

<nav class="bg-gray-800 p-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center space-x-2">
            <span class="text-xl font-bold">K</span>
            <a href="/kernelstore/index.php" class="text-indigo-400 hover:text-indigo-300 text-xl font-bold">KernelStore</a>
        </div>
        <div class="flex space-x-4">
            <a href="/kernelstore/accounts.php" class="hover:text-indigo-300">Accounts</a>
            <a href="/kernelstore/boosting.php" class="hover:text-indigo-300">Boosting</a>
            <a href="/kernelstore/games.php" class="hover:text-indigo-300">Games</a>
            <a href="/kernelstore/support.php" class="hover:text-indigo-300">Support</a>
        </div>
        <div class="flex items-center space-x-4">
            <input type="text" placeholder="Search..." class="bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none">
            <?php if (isLoggedIn()): ?>
                <span class="text-white">Welcome, <?php echo isset($_SESSION['user_id']) ? (isset($user['username']) ? $user['username'] : 'User') : 'Guest'; ?></span>
                <a href="/kernelstore/profile.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Profile</a>
                <a href="/kernelstore/chat.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Chat</a>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                    <a href="/kernelstore/admin/dashboard.php" class="dashboard-btn bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-colors" onclick="preventDoubleClick(this)">Dashboard</a>
                <?php endif; ?>
                <a href="/kernelstore/logout.php" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg font-medium transition-colors">Logout</a>
            <?php else: ?>
                <a href="/kernelstore/login.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Login</a>
                <a href="/kernelstore/register.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>