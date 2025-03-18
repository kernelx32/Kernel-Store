<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get account ID
$accountId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($accountId <= 0) {
    header('Location: accounts.php');
    exit;
}

// Get account details
$account = getAccountById($conn, $accountId);

if (!$account) {
    header('Location: accounts.php');
    exit;
}

// Add default values if keys are not present for the account
$account['rating'] = isset($account['rating']) ? floatval($account['rating']) : 0;
$account['reviews'] = isset($account['reviews']) ? intval($account['reviews']) : 0;
$account['features'] = isset($account['features']) && !empty($account['features']) ? $account['features'] : '';
$account['description'] = isset($account['description']) && !empty($account['description']) ? $account['description'] : 'No description available.';
$account['details'] = isset($account['details']) && !empty($account['details']) ? $account['details'] : 'No details available.'; // الوصف الجديد

// Get similar accounts
$similarAccounts = getSimilarAccounts($conn, $account['game_id'], $accountId);

// Add default values for similar accounts to avoid warnings
foreach ($similarAccounts as &$similar) {
    $similar['rating'] = isset($similar['rating']) ? floatval($similar['rating']) : 0;
    $similar['reviews'] = isset($similar['reviews']) ? intval($similar['reviews']) : 0;
}
unset($similar); // Unset the reference to avoid issues

function getSimilarAccounts($conn, $gameId, $accountId) {
    $accounts = [];
    $sql = "SELECT a.*, g.name as game_name 
            FROM accounts a 
            JOIN games g ON a.game_id = g.id 
            WHERE a.game_id = ? AND a.status = 'available' AND a.id != ? 
            LIMIT 4";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $gameId, $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $accounts[] = $row;
        }
    }
    return $accounts;
}

// Handle purchase
$error = '';
$success = '';

if (isset($_POST['purchase'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode('account.php?id=' . $accountId));
        exit;
    }
    
    // Create order
    $userId = $_SESSION['user_id'];
    $price = $account['price'];
    
    $orderId = createOrder($conn, $userId, $accountId, $price);
    
    if ($orderId) {
        header('Location: checkout.php?order_id=' . $orderId);
        exit;
    } else {
        $error = "Unable to create order. This account may no longer be available.";
    }
}

// جلب الصور المتعددة للحساب
$images = getAccountImages($conn, $accountId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($account['title']); ?> - KernelStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/kernelstore/assets/css/style.css">
    <style>
        .carousel-container {
            position: relative;
            width: 100%;
            overflow: hidden;
        }
        .carousel {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        .carousel img {
            width: 100%;
            flex-shrink: 0;
            height: 100%;
        }
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 10;
            font-size: 18px;
        }
        .carousel-btn.prev {
            left: 10px;
        }
        .carousel-btn.next {
            right: 10px;
        }
        .info-group {
            background-color: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .details-text-plain {
            color: #d1d5db;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .info-group h4 {
            color: #a5b4fc;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }
        .info-items-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
    </style>
</head>
<body class="bg-gray-900 text-white font-poppins min-h-screen flex flex-col">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Breadcrumbs -->
    <div class="bg-gray-800 py-4">
        <div class="container mx-auto px-4">
            <div class="flex items-center text-sm text-gray-400">
                <a href="index.php" class="hover:text-white transition-colors">Home</a>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <a href="accounts.php" class="hover:text-white transition-colors">Accounts</a>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <a href="accounts.php?game_id=<?php echo $account['game_id']; ?>" class="hover:text-white transition-colors"><?php echo $account['game_name']; ?></a>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span class="text-white"><?php echo $account['title']; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Account Details -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <?php if (!empty($error)): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Account Images (Carousel) -->
                <div>
                    <div class="bg-gray-800 rounded-xl overflow-hidden">
                        <div class="carousel-container">
                            <div class="carousel" id="carousel">
                                <?php if (!empty($images)): ?>
                                    <?php foreach ($images as $image): ?>
                                        <img src="/kernelstore/assets/images/accounts/<?php echo htmlspecialchars($image['image']); ?>" alt="<?php echo htmlspecialchars($account['title']); ?>">
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="w-full h-64 flex items-center justify-center bg-gray-700">
                                        <span class="text-gray-400">No Images Available</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-btn prev" onclick="moveSlide(-1)">❮</button>
                                <button class="carousel-btn next" onclick="moveSlide(1)">❯</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Account Info -->
                <div>
                    <div class="bg-indigo-600 text-white text-sm px-3 py-1 rounded-lg inline-block mb-4">
                        <?php echo htmlspecialchars($account['game_name']); ?>
                    </div>
                    
                    <!-- Group Box for all info -->
                    <div class="info-group">
                        <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($account['title']); ?></h1>
                        
                        <div class="flex items-center mb-4">
                            <div class="flex mr-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $account['rating']): ?>
                                        <i class="fas fa-star text-yellow-400"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-gray-500"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="text-gray-400">(<?php echo $account['reviews']; ?> reviews)</span>
                        </div>
                        
                        <div class="mb-4">
                            <div class="flex items-end mb-2">
                                <?php if (isset($account['discount']) && $account['discount'] > 0): ?>
                                    <span class="text-gray-400 line-through text-xl mr-3">$<?php echo number_format($account['original_price'], 2); ?></span>
                                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded">
                                        <?php echo $account['discount']; ?>% OFF
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-3xl font-bold text-white">
                                $<?php echo number_format($account['price'], 2); ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <form method="POST" action="account.php?id=<?php echo $accountId; ?>">
                                <button type="submit" name="purchase" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center">
                                    <i class="fas fa-shopping-cart mr-2"></i> Buy Now
                                </button>
                            </form>
                        </div>
                        
                        <!-- Additional Information in a row -->
                        <div class="info-items-row">
                            <div class="info-item">
                                <i class="fas fa-shield-alt mr-2 text-indigo-400"></i>
                                <span>Secure Payment</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-truck mr-2 text-indigo-400"></i>
                                <span>Instant Delivery</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-undo mr-2 text-indigo-400"></i>
                                <span>24h Warranty</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Features -->
            <div class="mt-12">
                <div class="bg-gray-800 rounded-xl overflow-hidden">
                    <div class="border-b border-gray-700">
                        <div class="flex">
                            <button class="px-6 py-4 font-medium focus:outline-none border-b-2 border-indigo-600 text-white" id="tab-features">
                                Features
                            </button>
                            <button class="px-6 py-4 font-medium focus:outline-none border-b-2 border-transparent text-gray-400 hover:text-white" id="tab-delivery">
                                Delivery
                            </button>
                            <button class="px-6 py-4 font-medium focus:outline-none border-b-2 border-transparent text-gray-400 hover:text-white" id="tab-warranty">
                                Warranty
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div id="content-features" class="tab-content">
                            <ul class="list-disc list-inside text-gray-300 space-y-2">
                                <?php 
                                $features = explode("\n", $account['features']);
                                foreach ($features as $feature):
                                    if (trim($feature)):
                                ?>
                                    <li><?php echo trim($feature); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                            <?php if ($account['details'] !== 'No details available.'): ?>
                                <div class="details-text-plain mt-4">
                                    <?php echo nl2br(htmlspecialchars($account['details'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div id="content-delivery" class="tab-content hidden">
                            <p class="text-gray-300 mb-4">After your purchase is complete, you will receive the account details instantly via:</p>
                            <ul class="list-disc list-inside text-gray-300 space-y-2">
                                <li>Email notification</li>
                                <li>Account details in your KernelStore dashboard</li>
                            </ul>
                            <p class="text-gray-300 mt-4">The account details include:</p>
                            <ul class="list-disc list-inside text-gray-300 space-y-2">
                                <li>Account email</li>
                                <li>Account password</li>
                                <li>Any additional information needed to access the account</li>
                            </ul>
                        </div>
                        
                        <div id="content-warranty" class="tab-content hidden">
                            <p class="text-gray-300 mb-4">All accounts come with a 24-hour warranty from the time of purchase. This warranty covers:</p>
                            <ul class="list-disc list-inside text-gray-300 space-y-2">
                                <li>Account access issues</li>
                                <li>Missing items or features that were advertised</li>
                                <li>Account recovery by the original owner</li>
                            </ul>
                            <p class="text-gray-300 mt-4">If you experience any issues within the warranty period, please contact our support team immediately.</p>
                            <p class="text-gray-300 mt-4"><strong>Note:</strong> The warranty does not cover issues caused by the buyer, such as changing the account password and then forgetting it, or violating the game's terms of service.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Similar Accounts -->
    <?php if (count($similarAccounts) > 0): ?>
    <section class="py-12 bg-gray-800">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-8">Similar Accounts</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($similarAccounts as $similarAccount): ?>
                    <div class="bg-gray-700 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300">
                        <?php $similarImages = getAccountImages($conn, $similarAccount['id']); ?>
                        <div class="h-48 overflow-hidden relative">
                            <?php if (!empty($similarImages)): ?>
                                <img src="/kernelstore/assets/images/accounts/<?php echo htmlspecialchars($similarImages[0]['image']); ?>" alt="<?php echo htmlspecialchars($similarAccount['title']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gray-700">
                                    <span class="text-gray-400">No Image</span>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-4 left-4 bg-indigo-600 text-white text-xs px-2 py-1 rounded">
                                <?php echo htmlspecialchars($similarAccount['game_name']); ?>
                            </div>
                            <?php if (isset($similarAccount['discount']) && $similarAccount['discount'] > 0): ?>
                            <div class="absolute top-4 right-4 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                <?php echo $similarAccount['discount']; ?>% OFF
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($similarAccount['title']); ?></h3>
                            <div class="flex items-center mb-4">
                                <div class="flex mr-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $similarAccount['rating']): ?>
                                            <i class="fas fa-star text-yellow-400"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-gray-500"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-gray-400 text-sm">(<?php echo $similarAccount['reviews']; ?> reviews)</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <?php if (isset($similarAccount['discount']) && $similarAccount['discount'] > 0): ?>
                                    <span class="text-gray-400 line-through text-sm mr-2">$<?php echo number_format($similarAccount['original_price'], 2); ?></span>
                                    <?php endif; ?>
                                    <span class="text-white font-bold text-xl">$<?php echo number_format($similarAccount['price'], 2); ?></span>
                                </div>
                                <a href="account.php?id=<?php echo $similarAccount['id']; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = ['features', 'delivery', 'warranty'];
            
            tabs.forEach(tab => {
                document.getElementById(`tab-${tab}`).addEventListener('click', function() {
                    // Hide all content
                    tabs.forEach(t => {
                        document.getElementById(`content-${t}`).classList.add('hidden');
                        document.getElementById(`tab-${t}`).classList.remove('border-indigo-600', 'text-white');
                        document.getElementById(`tab-${t}`).classList.add('border-transparent', 'text-gray-400');
                    });
                    
                    // Show selected content
                    document.getElementById(`content-${tab}`).classList.remove('hidden');
                    document.getElementById(`tab-${tab}`).classList.add('border-indigo-600', 'text-white');
                    document.getElementById(`tab-${tab}`).classList.remove('border-transparent', 'text-gray-400');
                });
            });

            // Carousel functionality
            let currentSlide = 0;
            const carousel = document.getElementById('carousel');
            let slides = carousel ? carousel.getElementsByTagName('img') : [];

            function moveSlide(direction) {
                if (slides.length === 0) return;
                currentSlide += direction;
                if (currentSlide < 0) {
                    currentSlide = slides.length - 1;
                } else if (currentSlide >= slides.length) {
                    currentSlide = 0;
                }
                carousel.style.transform = `translateX(-${currentSlide * 100}%)`;
            }

            document.querySelectorAll('.carousel-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const direction = this.classList.contains('prev') ? -1 : 1;
                    moveSlide(direction);
                });
            });
        });
    </script>
</body>
</html>