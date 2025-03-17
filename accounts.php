<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get all games for filtering
$games = getAllGames($conn);

// Get selected game
$gameId = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
$selectedGame = null;

if ($gameId > 0) {
    $selectedGame = getGameById($conn, $gameId);
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Get accounts
$accounts = [];
$totalAccounts = 0;

if ($gameId > 0) {
    // Get accounts for specific game
    $accounts = getAccountsByGameId($conn, $gameId, $limit, $offset);
    
    // Get total accounts count for pagination
    $sql = "SELECT COUNT(*) as total FROM accounts WHERE game_id = ? AND status = 'available'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalAccounts = $row['total'];
} else {
    // Get all accounts
    $sql = "SELECT a.*, g.name as game_name, 
            CASE WHEN a.discount > 0 THEN a.price * (1 + a.discount/100) ELSE a.price END as original_price 
            FROM accounts a 
            JOIN games g ON a.game_id = g.id 
            WHERE a.status = 'available' 
            ORDER BY a.featured DESC, a.id DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Add default rating if not present
            $row['rating'] = isset($row['rating']) ? $row['rating'] : 0;
            $row['reviews'] = isset($row['reviews']) ? $row['reviews'] : 0; // Also handle reviews
            $accounts[] = $row;
        }
    }
    
    // Get total accounts count for pagination
    $sql = "SELECT COUNT(*) as total FROM accounts WHERE status = 'available'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $totalAccounts = $row['total'];
}

// Calculate total pages
$totalPages = ceil($totalAccounts / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selectedGame ? $selectedGame['name'] . ' Accounts' : 'All Gaming Accounts'; ?> - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="bg-gray-800 py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-6"><?php echo $selectedGame ? $selectedGame['name'] . ' Accounts' : 'All Gaming Accounts'; ?></h1>
            
            <!-- Game Filter -->
            <div class="flex flex-wrap gap-3">
                <a href="accounts.php" class="game-filter-btn px-4 py-2 rounded-lg <?php echo $gameId === 0 ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?> transition-colors" data-game-id="0">
                    All Games
                </a>
                <?php foreach ($games as $game): ?>
                    <a href="accounts.php?game_id=<?php echo $game['id']; ?>" class="game-filter-btn px-4 py-2 rounded-lg <?php echo $gameId === $game['id'] ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?> transition-colors" data-game-id="<?php echo $game['id']; ?>">
                        <?php echo $game['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Accounts Grid -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <?php if (count($accounts) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($accounts as $account): ?>
                        <a href="account.php?id=<?php echo $account['id']; ?>" class="bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300 block">
                            <div class="h-48 overflow-hidden relative">
                                <img src="assets/images/accounts/<?php echo $account['image']; ?>" alt="<?php echo $account['title']; ?>" class="w-full h-full object-cover">
                                <div class="absolute top-4 left-4 bg-indigo-600 text-white text-xs px-2 py-1 rounded">
                                    <?php echo $account['game_name']; ?>
                                </div>
                                <?php if ($account['discount'] > 0): ?>
                                <div class="absolute top-4 right-4 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                    <?php echo $account['discount']; ?>% OFF
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold mb-2"><?php echo $account['title']; ?></h3>
                                <div class="flex items-center mb-4">
                                    <div class="flex mr-2">
                                        <?php 
                                        // Use rating if set, otherwise default to 0
                                        $rating = isset($account['rating']) ? $account['rating'] : 0;
                                        for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $rating): ?>
                                                <i class="fas fa-star text-yellow-400"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-gray-500"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-gray-400 text-sm">(<?php echo isset($account['reviews']) ? $account['reviews'] : 0; ?> reviews)</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <?php if ($account['discount'] > 0): ?>
                                        <span class="text-gray-400 line-through text-sm mr-2">$<?php echo number_format($account['original_price'], 2); ?></span>
                                        <?php endif; ?>
                                        <span class="text-white font-bold text-xl">$<?php echo number_format($account['price'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center mt-12">
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="accounts.php?<?php echo $gameId > 0 ? 'game_id=' . $gameId . '&' : ''; ?>page=<?php echo $page - 1; ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="accounts.php?<?php echo $gameId > 0 ? 'game_id=' . $gameId . '&' : ''; ?>page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'bg-indigo-600' : 'bg-gray-700 hover:bg-gray-600'; ?> text-white px-4 py-2 rounded-lg transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="accounts.php?<?php echo $gameId > 0 ? 'game_id=' . $gameId . '&' : ''; ?>page=<?php echo $page + 1; ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-16">
                    <i class="fas fa-search text-gray-600 text-5xl mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">No Accounts Found</h2>
                    <p class="text-gray-400 mb-8">We couldn't find any accounts matching your criteria.</p>
                    <a href="accounts.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        View All Accounts
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Ensure the active filter button is highlighted on page load
        document.addEventListener('DOMContentLoaded', function() {
            const gameId = <?php echo json_encode($gameId); ?>;
            const filterButtons = document.querySelectorAll('.game-filter-btn');

            filterButtons.forEach(button => {
                const buttonGameId = parseInt(button.getAttribute('data-game-id'));
                if (buttonGameId === gameId) {
                    button.classList.remove('bg-gray-700', 'text-gray-300', 'hover:bg-gray-600');
                    button.classList.add('bg-indigo-600', 'text-white');
                } else {
                    button.classList.remove('bg-indigo-600', 'text-white');
                    button.classList.add('bg-gray-700', 'text-gray-300', 'hover:bg-gray-600');
                }
            });
        });
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>