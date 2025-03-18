<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Pagination settings
$limit = 12; // عدد الحسابات في كل صفحة
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // تأكد إن الصفحة موجة
$offset = ($page - 1) * $limit;

// تعريف المتغيرات الافتراضية
$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_price = isset($_GET['sort_price']) ? $_GET['sort_price'] : 'desc'; // الافتراضي تنازلي
$rank = isset($_GET['rank']) ? trim($_GET['rank']) : '';
$min_price = isset($_GET['min_price']) ? max(1, floatval($_GET['min_price'])) : 1; // الافتراضي 1
$max_price = isset($_GET['max_price']) ? min(1000, floatval($_GET['max_price'])) : 1000; // الافتراضي 1000

// بناء الـ Query لعدد الحسابات
$total_sql = "SELECT COUNT(*) as total FROM accounts a WHERE a.status = 'available'";
$params = [];
$types = '';

if ($game_id) {
    $total_sql .= " AND a.game_id = ?";
    $params[] = $game_id;
    $types .= 'i';
}

if (!empty($search)) {
    $total_sql .= " AND a.title LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($rank)) {
    $total_sql .= " AND a.rank = ?";
    $params[] = $rank;
    $types .= 's';
}

if ($min_price > 0) {
    $total_sql .= " AND a.price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if ($max_price > 0) {
    $total_sql .= " AND a.price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

$stmt = $conn->prepare($total_sql);
if ($stmt === false) {
    die("Prepare failed for total count: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_accounts = $total_result->fetch_assoc()['total'] ?? 0; // تعريف افتراضي لو فشل
$stmt->close();

$total_pages = ceil($total_accounts / $limit);

// بناء الـ Query لجلب الحسابات
$sql = "SELECT a.*, g.name as game_name 
        FROM accounts a 
        JOIN games g ON a.game_id = g.id 
        WHERE a.status = 'available'";
$params = [];
$types = '';

if ($game_id) {
    $sql .= " AND a.game_id = ?";
    $params[] = $game_id;
    $types .= 'i';
}

if (!empty($search)) {
    $sql .= " AND a.title LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($rank)) {
    $sql .= " AND a.rank = ?";
    $params[] = $rank;
    $types .= 's';
}

if ($min_price > 0) {
    $sql .= " AND a.price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if ($max_price > 0) {
    $sql .= " AND a.price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

// الترتيب بناءً على السعر
if ($sort_price === 'asc') {
    $sql .= " ORDER BY a.price ASC";
} else {
    $sql .= " ORDER BY a.price DESC";
}

$sql .= " LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed for accounts: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}
$stmt->close();

// جلب كل الألعاب للفلتر
$all_games = getAllGames($conn);

// جلب قيم الـ Rank المتاحة
$ranks = [];
$rank_sql = "SELECT DISTINCT rank FROM accounts WHERE rank IS NOT NULL AND status = 'available'";
$rank_result = $conn->query($rank_sql);
while ($row = $rank_result->fetch_assoc()) {
    if ($row['rank']) {
        $ranks[] = $row['rank'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts - KernelStore</title>
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
        /* Custom Range Slider Style */
        .range-slider {
            width: 100%;
            height: 20px;
            background: #2d3748; /* لون رمادي غامق يتناسب مع الموقع */
            border: 2px solid #4a5568; /* حدود متناسقة مع الموقع */
            border-radius: 10px;
            position: relative;
            margin: 20px 0;
            overflow: hidden; /* لمنع الخروج من الفريم */
            box-sizing: border-box; /* لضمان احتواء الـ thumbs داخل الفريم */
        }
        .range-slider .slider-track {
            height: 100%;
            background: linear-gradient(90deg, #4b5e8a, #a3bffa); /* تدرج ألوان زرقاء متناسقة */
            border-radius: 10px;
            position: absolute;
            top: 0;
            transition: all 0.2s ease;
        }
        .range-slider .slider-thumb {
            width: 20px;
            height: 20px;
            background: #edf2f7;
            border: 2px solid #4a5568;
            border-radius: 50%;
            position: absolute;
            top: -2px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
            max-width: calc(100% - 20px); /* يمنع الـ thumb من الخروج */
        }
        .range-values {
            display: flex;
            justify-content: space-between;
            color: #a0aec0;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        .account-card {
            cursor: pointer;
            transition: transform 0.2s ease;
            background-color: #1a202c; /* لون الموقع الرئيسي */
        }
        .account-card .card-content {
            padding: 1rem;
        }
        .account-card .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: #1a202c; /* نفس لون الكارد عشان يبقى متناسق */
        }
        .account-card:hover {
            transform: translateY(-5px);
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
                <span class="text-white">Accounts</span>
            </div>
        </div>
    </div>
    
    <!-- Accounts Section -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Filters -->
                <div class="lg:w-1/4">
                    <div class="bg-gray-800 rounded-xl p-6">
                        <h3 class="text-xl font-bold mb-4">Filter Accounts</h3>
                        <form method="GET" action="accounts.php">
                            <!-- فلتر البحث عن كلمة -->
                            <div class="mb-4">
                                <label for="search" class="block text-gray-400 mb-2">Search by Title</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter keyword...">
                            </div>
                            <!-- فلتر اللعبة -->
                            <div class="mb-4">
                                <label for="game_id" class="block text-gray-400 mb-2">Game</label>
                                <select id="game_id" name="game_id" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All Games</option>
                                    <?php foreach ($all_games as $game): ?>
                                        <option value="<?php echo $game['id']; ?>" <?php echo $game_id == $game['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($game['name']); ?> (<?php echo $game['accounts']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- فلتر الـ Rank -->
                            <div class="mb-4">
                                <label for="rank" class="block text-gray-400 mb-2">Rank</label>
                                <select id="rank" name="rank" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All Ranks</option>
                                    <?php foreach ($ranks as $r): ?>
                                        <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $rank == $r ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- فلتر الترتيب حسب السعر -->
                            <div class="mb-4">
                                <label for="sort_price" class="block text-gray-400 mb-2">Sort by Price</label>
                                <select id="sort_price" name="sort_price" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="desc" <?php echo $sort_price == 'desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="asc" <?php echo $sort_price == 'asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                </select>
                            </div>
                            <!-- فلتر نطاق السعر بشريط سحب مخصص -->
                            <div class="mb-4">
                                <label class="block text-gray-400 mb-2">Price Range ($1 - $1000)</label>
                                <div class="range-slider">
                                    <div class="slider-track" style="width: <?php echo (($max_price - $min_price) / 999) * 100; ?>%; left: <?php echo (($min_price - 1) / 999) * 100; ?>%;"></div>
                                    <div class="slider-thumb" style="left: <?php echo (($min_price - 1) / 999) * 100; ?>%;"></div>
                                    <div class="slider-thumb" style="left: <?php echo (($max_price - 1) / 999) * 100; ?>%;"></div>
                                </div>
                                <div class="range-values">
                                    <span>Min: $<?php echo $min_price; ?></span>
                                    <span>Max: $<?php echo $max_price; ?></span>
                                </div>
                                <input type="hidden" name="min_price" id="min_price" value="<?php echo $min_price; ?>">
                                <input type="hidden" name="max_price" id="max_price" value="<?php echo $max_price; ?>">
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">Apply Filters</button>
                        </form>
                    </div>
                </div>
                
                <!-- Accounts List -->
                <div class="lg:w-3/4">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-bold">Available Accounts (<?php echo $total_accounts; ?>)</h2>
                    </div>
                    
                    <?php if (empty($accounts)): ?>
                        <div class="text-center text-gray-400">
                            <p>No accounts available at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                            <?php foreach ($accounts as $account): ?>
                                <div class="account-card bg-gray-800 rounded-xl overflow-hidden hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300" onclick="window.location.href='account.php?id=<?php echo $account['id']; ?>'">
                                    <?php $images = getAccountImages($conn, $account['id']); ?>
                                    <div class="h-48 overflow-hidden relative">
                                        <?php if (!empty($images) && !empty($images[0]['image'])): ?>
                                            <img src="/kernelstore/assets/images/accounts/<?php echo htmlspecialchars($images[0]['image']); ?>" alt="<?php echo htmlspecialchars($account['title']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center bg-gray-700">
                                                <span class="text-gray-400">No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-content">
                                        <div class="text-xs text-green-400 mb-1"><?php echo htmlspecialchars($account['platform'] ?? 'Unknown'); ?></div> <!-- افتراضي لو المنصة مش موجودة -->
                                        <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($account['title']); ?></h3>
                                        <div class="flex items-center mb-2">
                                            <div class="flex mr-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= ($account['rating'] ?? 0)): ?>
                                                        <i class="fas fa-star text-yellow-400"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-gray-500"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-gray-400"><?php echo ($account['reviews'] ?? 0) . ' reviews'; ?></span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="text-white font-bold">$<?php echo number_format($account['price'], 2); ?></div>
                                        <a href="account.php?id=<?php echo $account['id']; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                            Buy Now
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="mt-8 flex justify-center items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="accounts.php?page=<?php echo $page - 1; ?>&game_id=<?php echo $game_id; ?>&search=<?php echo urlencode($search); ?>&sort_price=<?php echo $sort_price; ?>&rank=<?php echo urlencode($rank); ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="accounts.php?page=<?php echo $i; ?>&game_id=<?php echo $game_id; ?>&search=<?php echo urlencode($search); ?>&sort_price=<?php echo $sort_price; ?>&rank=<?php echo urlencode($rank); ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>" class="<?php echo $i == $page ? 'bg-indigo-600' : 'bg-gray-700 hover:bg-gray-600'; ?> text-white px-4 py-2 rounded-lg transition-colors">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="accounts.php?page=<?php echo $page + 1; ?>&game_id=<?php echo $game_id; ?>&search=<?php echo urlencode($search); ?>&sort_price=<?php echo $sort_price; ?>&rank=<?php echo urlencode($rank); ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Custom Range Slider Logic
        document.addEventListener('DOMContentLoaded', function() {
            const minInput = document.getElementById('min_price');
            const maxInput = document.getElementById('max_price');
            const rangeSlider = document.querySelector('.range-slider');
            const track = document.querySelector('.slider-track');
            const thumbs = document.querySelectorAll('.slider-thumb');

            function updateSlider() {
                const min = parseFloat(minInput.value);
                const max = parseFloat(maxInput.value);
                const minPercent = ((min - 1) / 999) * 100;
                const maxPercent = ((max - 1) / 999) * 100;

                track.style.width = `${((max - min) / 999) * 100}%`;
                track.style.left = `${minPercent}%`;
                thumbs[0].style.left = `${minPercent}%`;
                thumbs[1].style.left = `${maxPercent}%`;
            }

            thumbs.forEach((thumb, index) => {
                thumb.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    const startX = e.clientX;
                    const startValue = index === 0 ? parseFloat(minInput.value) : parseFloat(maxInput.value);
                    const minBound = index === 0 ? 1 : parseFloat(minInput.value);
                    const maxBound = index === 0 ? parseFloat(maxInput.value) : 1000;

                    function moveThumb(moveEvent) {
                        const moveX = moveEvent.clientX - startX;
                        const newValue = startValue + (moveX / rangeSlider.offsetWidth) * 999;
                        let clampedValue = Math.max(minBound, Math.min(maxBound, newValue));
                        clampedValue = Math.round(clampedValue); // Round to nearest integer

                        if (index === 0) {
                            minInput.value = clampedValue;
                            if (parseFloat(maxInput.value) < clampedValue) maxInput.value = clampedValue;
                        } else {
                            maxInput.value = clampedValue;
                            if (parseFloat(minInput.value) > clampedValue) minInput.value = clampedValue;
                        }
                        updateSlider();
                        updateURL();
                    }

                    function stopThumb() {
                        document.removeEventListener('mousemove', moveThumb);
                        document.removeEventListener('mouseup', stopThumb);
                    }

                    document.addEventListener('mousemove', moveThumb);
                    document.addEventListener('mouseup', stopThumb);
                });
            });

            function updateURL() {
                const url = new URL(window.location);
                url.searchParams.set('min_price', minInput.value);
                url.searchParams.set('max_price', maxInput.value);
                window.history.pushState({}, '', url);
            }

            updateSlider(); // Initial setup
        });
    </script>
</body>
</html>