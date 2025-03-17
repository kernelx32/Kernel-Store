<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

$featured_games = [
    [
        'name' => 'Marvel Rivals',
        'description' => 'Marvel Rivals is a team-based hero shooter featuring iconic Marvel characters...',
        'image' => 'marvelrivals.jpg',
        'accounts' => 0
    ],
    [
        'name' => 'Call of Duty',
        'description' => 'Call of Duty is a first-person shooter game series published by Activision...',
        'image' => 'callofduty.jpg',
        'accounts' => 0
    ],
    [
        'name' => 'Fragpunk',
        'description' => 'Fragpunk is a futuristic first-person shooter with cyberpunk elements...',
        'image' => 'fragpunk.jpg',
        'accounts' => 0
    ],
    [
        'name' => 'Overwatch',
        'description' => 'Overwatch is a team-based multiplayer first-person shooter developed by Blizzard...',
        'image' => 'overwatch.jpg',
        'accounts' => 0
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-indigo-900 to-purple-900 py-16">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Level Up Your Gaming Experience</h1>
            <p class="text-xl text-gray-300 mb-8">Premium gaming accounts for Marvel Rivals, Call of Duty, Fragpunk, and Overwatch at unbeatable prices.</p>
            <div class="space-x-4">
                <a href="accounts.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">Browse Accounts</a>
                <a href="games.php" class="bg-gray-600 hover:bg-gray-500 text-white px-6 py-3 rounded-lg font-medium transition-colors">Explore Games</a>
            </div>
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
                            <img src="assets/images/<?php echo htmlspecialchars($game['image']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($game['name']); ?></h3>
                            <p class="text-gray-400 mb-4"><?php echo htmlspecialchars($game['description']); ?></p>
                            <div class="flex space-x-2">
                                <a href="accounts.php?game_id=<?php echo htmlspecialchars($game['name']); ?>" class="text-indigo-400 hover:text-indigo-300">View Accounts</a>
                                <span class="text-gray-400"><?php echo $game['accounts']; ?> Accounts</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>