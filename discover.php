<?php
session_start();
require_once "config/db.php";
require_once "includes/functions.php";

// Check if user is logged in for authentication detection
$isUserLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

/* =========================
   GET FILTER VALUES
========================= */
$keyword         = trim($_GET['keyword'] ?? '');
$city            = trim($_GET['city'] ?? '');
$category        = $_GET['category'] ?? '';
$property_type   = $_GET['property_type'] ?? '';  // NEW: residential, commercial, land
$listing_type    = $_GET['listing_type'] ?? '';  // NEW: rent or sale
$listing_mode    = $_GET['listing_mode'] ?? '';
$min_price       = $_GET['min_price'] ?? '';
$max_price       = $_GET['max_price'] ?? '';
$featured        = $_GET['featured'] ?? '';
$furnished       = $_GET['furnished'] ?? '';
$bedrooms        = $_GET['bedrooms'] ?? '';
$room_type       = trim($_GET['room_type'] ?? '');
$vip_only        = $_GET['vip_only'] ?? '';
$availability    = $_GET['availability'] ?? '';
$most_viewed     = $_GET['most_viewed'] ?? '';
$verified        = $_GET['verified'] ?? '';
$land_purpose    = $_GET['land_purpose'] ?? '';       // NEW: for land properties
$commercial_type = $_GET['commercial_type'] ?? '';   // NEW: for commercial properties

/* =========================
   CHECK IF PAGE IS IN SEARCH MODE
========================= */
$isSearchMode =
    $keyword !== '' ||
    $city !== '' ||
    $category !== '' ||
    $property_type !== '' ||
    $listing_type !== '' ||
    $listing_mode !== '' ||
    $min_price !== '' ||
    $max_price !== '' ||
    $featured !== '' ||
    $furnished !== '' ||
    $bedrooms !== '' ||
    $room_type !== '' ||
    $vip_only !== '' ||
    $availability !== '' ||
    $most_viewed !== '' ||
    $verified !== '' ||
    $land_purpose !== '' ||
    $commercial_type !== '';

/* =========================
   FETCH CATEGORIES
========================= */
$categories = $pdo->query("
    SELECT * 
    FROM categories 
    ORDER BY category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   COMMON PROPERTY SELECT
========================= */
$propertySelect = "
SELECT 
    p.*,
    c.category_name,

    /* first image */
    (
        SELECT pi.image_path
        FROM property_images pi
        WHERE pi.property_id = p.property_id
        AND pi.is_main = 1
        LIMIT 1
    ) AS image_path,

    /* room stats */
    (
        SELECT MIN(rc.price)
        FROM room_categories rc
        WHERE rc.property_id = p.property_id
    ) AS min_room_price,

    (
        SELECT MAX(rc.price)
        FROM room_categories rc
        WHERE rc.property_id = p.property_id
    ) AS max_room_price,

    (
        SELECT COALESCE(SUM(rc.available_rooms), 0)
        FROM room_categories rc
        WHERE rc.property_id = p.property_id
    ) AS total_available_rooms,

    (
        SELECT COALESCE(SUM(rc.total_rooms), 0)
        FROM room_categories rc
        WHERE rc.property_id = p.property_id
    ) AS total_rooms_count,

    (
        SELECT COUNT(*)
        FROM room_categories rc
        WHERE rc.property_id = p.property_id
        AND rc.is_vip = 1
    ) AS vip_room_count,

    (
        SELECT COUNT(*)
        FROM favorites f
        WHERE f.property_id = p.property_id
        AND f.user_id = " . (isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 0) . "
    ) AS is_favorite,

    (
        SELECT COUNT(*)
        FROM property_views pv
        WHERE pv.property_id = p.property_id
    ) AS view_count

FROM properties p
JOIN categories c ON p.category_id = c.category_id
";

/* =========================
   BASE SEARCH QUERY
========================= */
$sql = $propertySelect . " WHERE 1=1 ";
$params = [];

/* =========================
   KEYWORD SEARCH
========================= */
if ($keyword !== '') {
    $sql .= " AND (
        p.title LIKE ?
        OR p.description LIKE ?
        OR p.address LIKE ?
        OR p.city LIKE ?
        OR EXISTS (
            SELECT 1
            FROM room_categories rc
            WHERE rc.property_id = p.property_id
            AND (
                rc.room_type LIKE ?
                OR rc.features LIKE ?
            )
        )
    )";

    $like = "%{$keyword}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

/* =========================
   CITY FILTER
========================= */
if ($city !== '') {
    $sql .= " AND p.city LIKE ?";
    $params[] = "%{$city}%";
}

/* =========================
   CATEGORY FILTER
========================= */
if ($category !== '') {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

/* =========================
   PROPERTY TYPE FILTER (NEW)
========================= */
if ($property_type !== '') {
    $sql .= " AND p.property_type = ?";
    $params[] = $property_type;
}

/* =========================
   LISTING TYPE FILTER (NEW) - rent or sale
========================= */
if ($listing_type !== '') {
    $sql .= " AND p.listing_type = ?";
    $params[] = $listing_type;
}

/* =========================
   LISTING MODE FILTER
========================= */
if ($listing_mode !== '') {
    $sql .= " AND p.listing_mode = ?";
    $params[] = $listing_mode;
}

/* =========================
   FEATURED FILTER
========================= */
if ($featured === '1') {
    $sql .= " AND p.featured = 1";
}

/* =========================
   FURNISHED FILTER
========================= */
if ($furnished === '1') {
    $sql .= " AND p.furnished = 1";
}

/* =========================
   BEDROOM FILTER
========================= */
if ($bedrooms !== '') {
    $sql .= " AND p.listing_mode = 'single' AND p.bedrooms >= ?";
    $params[] = $bedrooms;
}

/* =========================
   ROOM TYPE FILTER
========================= */
if ($room_type !== '') {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM room_categories rc
        WHERE rc.property_id = p.property_id
        AND rc.room_type LIKE ?
    )";
    $params[] = "%{$room_type}%";
}

/* =========================
   VIP ROOM FILTER
========================= */
if ($vip_only === '1') {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM room_categories rc
        WHERE rc.property_id = p.property_id
        AND rc.is_vip = 1
    )";
}

/* =========================
   AVAILABILITY FILTER
========================= */
if ($availability === 'available') {
    $sql .= " AND (
        (p.listing_mode = 'single' AND p.status = 'available')
        OR
        (p.listing_mode = 'multi_room' AND p.status = 'available')
    )";
}

if ($availability === 'few_left') {
    $sql .= " AND p.listing_mode = 'multi_room'
              AND EXISTS (
                  SELECT 1
                  FROM room_categories rc
                  WHERE rc.property_id = p.property_id
                  AND rc.available_rooms BETWEEN 1 AND 3
              )";
}

if ($availability === 'occupied') {
    $sql .= " AND (
        (p.listing_mode = 'single' AND p.status = 'occupied')
        OR
        (p.listing_mode = 'multi_room' AND p.status = 'occupied')
    )";
}

if ($availability === 'maintenance') {
    $sql .= " AND (
        (p.listing_mode = 'single' AND p.status = 'maintenance')
        OR
        (p.listing_mode = 'multi_room' AND p.status = 'maintenance')
    )";
}

/* =========================
   PRICE FILTER
========================= */
if ($min_price !== '') {
    $sql .= " AND (
        (p.listing_mode = 'single' AND p.price >= ?)
        OR
        (p.listing_mode = 'multi_room' AND EXISTS (
            SELECT 1
            FROM room_categories rc
            WHERE rc.property_id = p.property_id
            AND rc.price >= ?
        ))
    )";
    $params[] = $min_price;
    $params[] = $min_price;
}

if ($max_price !== '') {
    $sql .= " AND (
        (p.listing_mode = 'single' AND p.price <= ?)
        OR
        (p.listing_mode = 'multi_room' AND EXISTS (
            SELECT 1
            FROM room_categories rc
            WHERE rc.property_id = p.property_id
            AND rc.price <= ?
        ))
    )";
    $params[] = $max_price;
    $params[] = $max_price;
}

/* =========================
   VERIFIED FILTER
========================= */
if ($verified === '1') {
    $sql .= " AND p.is_verified = 1";
}

/* =========================
   LAND PURPOSE FILTER (NEW)
========================= */
if ($land_purpose !== '' && $property_type === 'land') {
    $sql .= " AND p.land_purpose = ?";
    $params[] = $land_purpose;
}

/* =========================
   COMMERCIAL TYPE FILTER (NEW)
========================= */
if ($commercial_type !== '' && $property_type === 'commercial') {
    $sql .= " AND p.commercial_type = ?";
    $params[] = $commercial_type;
}

/* =========================
   ORDER SEARCH RESULTS
========================= */
if ($most_viewed === '1') {
    $sql .= " ORDER BY (SELECT COUNT(*) FROM property_views pv WHERE pv.property_id = p.property_id) DESC, p.featured DESC, p.created_at DESC";
} else {
    $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   DISCOVERY SECTIONS
========================= */
$featuredProperties = $pdo->query(
    $propertySelect . "
    WHERE p.featured = 1
    ORDER BY p.created_at DESC
    LIMIT 6
"
)->fetchAll(PDO::FETCH_ASSOC);

$recentProperties = $pdo->query(
    $propertySelect . "
    ORDER BY p.created_at DESC
    LIMIT 6
"
)->fetchAll(PDO::FETCH_ASSOC);

$popularLocations = $pdo->query("
    SELECT city, COUNT(*) AS total_properties
    FROM properties
    WHERE city IS NOT NULL
    GROUP BY city
    ORDER BY total_properties DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-authenticated" content="<?= $isUserLoggedIn ? 'true' : 'false' ?>">
    <title>StaySphere - Discover Your Perfect Stay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-white" data-user-authenticated="<?= $isUserLoggedIn ? 'true' : 'false' ?>">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 20px;">

    <!-- SEARCH & FILTER SECTION -->
    <div class="card mb-3xl shadow-lg" style="margin-top: -20px;">
        <div class="card-body p-xl">
            <form action="discover.php" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-lg mb-lg">
                    <div class="md:col-span-2">
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Search Keywords</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Property title, location, or features..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                    <div>
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">City / Region</label>
                        <input type="text" name="city" class="form-control" placeholder="e.g. London" value="<?= htmlspecialchars($city) ?>">
                    </div>
                    <div>
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Property Type</label>
                        <select name="property_type" id="propertyTypeFilter" class="form-control" onchange="updatePropertyFilters()">
                            <option value="">All Types</option>
                            <option value="residential" <?= $property_type === 'residential' ? 'selected' : '' ?>>🏠 Residential</option>
                            <option value="commercial" <?= $property_type === 'commercial' ? 'selected' : '' ?>>🏪 Commercial</option>
                            <option value="land" <?= $property_type === 'land' ? 'selected' : '' ?>>🏞️ Land</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-lg mb-lg">
                    <div>
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Category</label>
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $category == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- RESIDENTIAL SPECIFIC FILTERS -->
                    <div id="residentialFilters" style="<?= $property_type && $property_type !== 'residential' ? 'display:none;' : '' ?>">
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Furnished</label>
                        <select name="furnished" class="form-control">
                            <option value="">Any</option>
                            <option value="1" <?= $furnished === '1' ? 'selected' : '' ?>>Furnished Only</option>
                        </select>
                    </div>

                    <!-- AVAILABILITY STATUS FILTER -->
                    <div>
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Availability Status</label>
                        <select name="availability" class="form-control">
                            <option value="">All Status</option>
                            <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="occupied" <?= $availability === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                            <option value="maintenance" <?= $availability === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>

                    <!-- COMMERCIAL SPECIFIC FILTERS -->
                    <div id="commercialFilters" style="<?= $property_type !== 'commercial' ? 'display:none;' : '' ?>">
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Commercial Type</label>
                        <select name="commercial_type" class="form-control">
                            <option value="">All Types</option>
                            <option value="shop" <?= $commercial_type === 'shop' ? 'selected' : '' ?>>Shop / Boutique</option>
                            <option value="office" <?= $commercial_type === 'office' ? 'selected' : '' ?>>Office</option>
                            <option value="warehouse" <?= $commercial_type === 'warehouse' ? 'selected' : '' ?>>Warehouse</option>
                            <option value="restaurant" <?= $commercial_type === 'restaurant' ? 'selected' : '' ?>>Restaurant</option>
                            <option value="salon" <?= $commercial_type === 'salon' ? 'selected' : '' ?>>Salon / Spa</option>
                            <option value="gym" <?= $commercial_type === 'gym' ? 'selected' : '' ?>>Gym</option>
                        </select>
                    </div>

                    <!-- LAND SPECIFIC FILTERS -->
                    <div id="landFilters" style="<?= $property_type !== 'land' ? 'display:none;' : '' ?>">
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Land Purpose</label>
                        <select name="land_purpose" class="form-control">
                            <option value="">All Purposes</option>
                            <option value="residential" <?= $land_purpose === 'residential' ? 'selected' : '' ?>>Residential</option>
                            <option value="commercial" <?= $land_purpose === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                            <option value="agricultural" <?= $land_purpose === 'agricultural' ? 'selected' : '' ?>>Agricultural</option>
                            <option value="industrial" <?= $land_purpose === 'industrial' ? 'selected' : '' ?>>Industrial</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-lg items-end">
                    <div style="flex: 1; min-width: 200px;">
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Listing Purpose</label>
                        <select name="listing_type" class="form-control">
                            <option value="">All Purposes</option>
                            <option value="rent" <?= $listing_type === 'rent' ? 'selected' : '' ?>>🏠 For Rent</option>
                            <option value="sale" <?= $listing_type === 'sale' ? 'selected' : '' ?>>💰 For Sale</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Listing Type</label>
                        <select name="listing_mode" class="form-control">
                            <option value="">Any Type</option>
                            <option value="single" <?= $listing_mode == 'single' ? 'selected' : '' ?>>Single Property</option>
                            <option value="multi_room" <?= $listing_mode == 'multi_room' ? 'selected' : '' ?>>Multi-Room / Apartment</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs">Price Range</label>
                        <div class="flex gap-sm">
                            <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>">
                            <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>">
                        </div>
                    </div>
                    <div class="flex gap-md">
                        <button type="submit" class="btn btn-primary px-2xl py-md">Search Properties</button>
                        <?php if ($isSearchMode): ?>
                            <a href="discover.php" class="btn btn-secondary px-xl py-md">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$isSearchMode): ?>

        <!-- FEATURED PROPERTIES -->
        <?php if (!empty($featuredProperties)): ?>
            <div class="flex justify-between items-end mb-md mt-8">
                <div>
                    <h2 class="section-title mb-0" style="font-size: 1.875rem;">Premium Listings</h2>
                    <p class="text-muted">Handpicked premium properties for you.</p>
                </div>
                <a href="discover.php?featured=1" class="text-primary font-bold mb-md">View All Premium →</a>
            </div>
            <div class="property-card-grid mb-3xl">
                <?php foreach ($featuredProperties as $property): ?>
                    <?= renderPropertyCard($property) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- RECENT PROPERTIES -->
        <?php if (!empty($recentProperties)): ?>
            <div class="flex justify-between items-end mb-md mt-8">
                <div>
                    <h2 class="section-title mb-0" style="font-size: 1.875rem;">Recently Added</h2>
                    <p class="text-muted">The latest listings on StaySphere.</p>
                </div>
                <a href="discover.php?most_viewed=1" class="text-primary font-bold mb-md">Explore More →</a>
            </div>
            <div class="property-card-grid mb-2xl">
                <?php foreach ($recentProperties as $property): ?>
                    <?= renderPropertyCard($property) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>

        <!-- SEARCH RESULTS -->
        <div class="section-title mt-8">
            <h2 style="font-size: 1.875rem;">Search Results</h2>
            <p class="text-muted"><?php echo count($properties) ?> <?php echo count($properties) === 1 ? 'property' : 'properties' ?> found matching your criteria.</p>
        </div>

        <?php if (!empty($properties)): ?>
            <div class="property-card-grid mb-2xl">
                <?php foreach ($properties as $property): ?>
                    <?= renderPropertyCard($property) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning py-xl text-center">
                <div class="alert-body">
                    <h4 class="mb-md">No properties found</h4>
                    <p>Try adjusting your filters or searching for a different location.</p>
                    <a href="discover.php" class="btn btn-primary mt-lg">Clear All Filters</a>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script>
// Update property type specific filters visibility
function updatePropertyFilters() {
    const propertyType = document.getElementById('propertyTypeFilter').value;
    document.getElementById('residentialFilters').style.display = (propertyType === 'residential' || propertyType === '') ? 'block' : 'none';
    document.getElementById('commercialFilters').style.display = (propertyType === 'commercial') ? 'block' : 'none';
    document.getElementById('landFilters').style.display = (propertyType === 'land') ? 'block' : 'none';
}
</script>

<script>
// Favorite toggle logic
document.addEventListener("click", function(e) {
    const button = e.target.closest(".property-card-favorite");
    if (!button) return;

    const propertyId = button.getAttribute("data-property-id");

    // Add animation class
    button.classList.add("heart-animate");

    fetch("toggle_favorite.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "property_id=" + encodeURIComponent(propertyId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "added") {
            button.classList.add("active");
            button.title = "Remove from favorites";
        } else if (data.status === "removed") {
            button.classList.remove("active");
            button.title = "Add to favorites";
        } else if (data.status === "error") {
            alert(data.message);
        }
    })
    .then(() => {
        // Remove animation class after animation
        setTimeout(() => button.classList.remove("heart-animate"), 300);
    })
    .catch(() => {
        alert("Failed to update favorite.");
        setTimeout(() => button.classList.remove("heart-animate"), 300);
    });
});
</script>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-16 px-4 sm:px-6 lg:px-8 mt-24">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-4 gap-8 mb-12">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 gradient-blue rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold">S</span>
                        </div>
                        <span class="text-xl font-bold" style="color: #87CEEB;">StaySphere</span>
                    </div>
                    <p class="text-gray-400">Discover your perfect property and connect with landlords effortlessly.</p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4" style="color: #87CEEB;">Company</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">About Us</a></li>
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4" style="color: #87CEEB;">Legal</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4" style="color: #87CEEB;">Connect</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">Twitter</a></li>
                        <li><a href="#" style="color: #87CEEB;" class="hover:text-white transition">LinkedIn</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p style="color: white;">&copy; 2026 StaySphere. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/guest-auth.js"></script>
</body>
</html>
