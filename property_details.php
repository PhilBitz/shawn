<?php
session_start();
require_once "config/db.php";
require_once "includes/functions.php";

// Currency conversion setup
$user_country = trim((string) ($_SESSION["country"] ?? "Cameroon"));
$user_currency = getCurrencyInfo($user_country);
$target_currency = $user_currency["currency"] ?? 'XAF';

// Determine display currency: show in property's currency if matches user's, else convert
$property_currency = $property['currency'] ?? 'XAF';
$display_currency = ($target_currency === $property_currency) ? $property_currency : $target_currency;
$display_currency_info = getCurrencyDetails($display_currency);

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$property_id = (int) $_GET["id"];

/* =========================
   FETCH PROPERTY
========================= */
$stmt = $pdo->prepare("
    SELECT p.*, c.category_name, u.full_name AS landlord_name, u.user_id AS landlord_id
    FROM properties p
    JOIN categories c ON p.category_id = c.category_id
    JOIN users u ON p.landlord_id = u.user_id
    WHERE p.property_id = ?
");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    die("Property not found.");
}

/* =========================
   TRACK VIEW
========================= */
if (isset($_SESSION["user_id"])) {
    $trackStmt = $pdo->prepare("
        INSERT INTO property_views (property_id, user_id) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE viewed_at = NOW()
    ");
    $trackStmt->execute([$property_id, $_SESSION["user_id"]]);
}

/* =========================
   CHECK IF FAVORITED
========================= */
$isFavorite = 0;
if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "client") {
    $favStmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND property_id = ?");
    $favStmt->execute([$_SESSION["user_id"], $property_id]);
    $isFavorite = $favStmt->fetchColumn();
}

/* =========================
   FETCH ROOM CATEGORIES
========================= */
$roomStmt = $pdo->prepare("SELECT * FROM room_categories WHERE property_id = ?");
$roomStmt->execute([$property_id]);
$rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PROPERTY RATING
========================= */
$ratingStmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews FROM property_reviews WHERE property_id=?");
$ratingStmt->execute([$property_id]);
$ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
$avgRating = $ratingData["avg_rating"] ? round($ratingData["avg_rating"],1) : 0;
$totalReviews = $ratingData["total_reviews"];

/* =========================
   FETCH IMAGES
========================= */
$imageStmt = $pdo->prepare("SELECT image_path, is_main FROM property_images WHERE property_id = ? ORDER BY is_main DESC, image_id ASC");
$imageStmt->execute([$property_id]);
$images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FETCH REVIEWS
========================= */
$reviewStmt = $pdo->prepare("SELECT r.*, u.full_name FROM property_reviews r JOIN users u ON r.client_id = u.user_id WHERE r.property_id = ? ORDER BY r.created_at DESC");
$reviewStmt->execute([$property_id]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

$userReview = null;
if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "client") {
    $userReviewStmt = $pdo->prepare("SELECT * FROM property_reviews WHERE property_id = ? AND client_id = ? LIMIT 1");
    $userReviewStmt->execute([$property_id, $_SESSION["user_id"]]);
    $userReview = $userReviewStmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   FETCH SIMILAR PROPERTIES
========================= */
$similarStmt = $pdo->prepare("
    SELECT p.*, c.category_name,
        (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.property_id AND pi.is_main = 1 LIMIT 1) AS image_path,
        (SELECT COUNT(*) FROM property_views pv WHERE pv.property_id = p.property_id) AS view_count,
        (SELECT COUNT(*) FROM favorites f WHERE f.property_id = p.property_id AND f.user_id = " . (isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 0) . ") AS is_favorite
    FROM properties p
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.property_id != ? AND p.category_id = ?
    ORDER BY p.featured DESC, p.created_at DESC
    LIMIT 3
");
$similarStmt->execute([$property_id, $property["category_id"]]);
$similarProperties = $similarStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is logged in for authentication detection
$isUserLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-authenticated" content="<?= $isUserLoggedIn ? 'true' : 'false' ?>">
    <title><?= htmlspecialchars($property["title"]) ?> - StaySphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-white" data-user-authenticated="<?= $isUserLoggedIn ? 'true' : 'false' ?>">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 20px;">

    <div class="flex gap-md items-center mb-lg mt-lg">
        <button onclick="history.back()" class="btn btn-secondary">← Back</button>
        <h1 class="page-title mb-0 flex-1" style="font-size: 1.875rem;"><?= htmlspecialchars($property["title"]) ?></h1>
        <button type="button"
                class="btn btn-outline favorite-btn<?php echo $isFavorite ? ' active' : '' ?>"
                data-property-id="<?= $property_id ?>"
                title="<?php echo $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
            <svg class="heart-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            Favorite
        </button>
    </div>
    <div class="flex gap-sm items-center mb-lg">
        <?php if ($property["featured"]): ?>
            <span class="badge bg-gold">⭐ Premium</span>
        <?php endif; ?>
        <?php if ($property["is_verified"]): ?>
            <span class="badge bg-success">✓ Verified</span>
        <?php endif; ?>
        <span class="badge bg-info"><?= htmlspecialchars($property["category_name"]) ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-xl">
        
        <!-- MAIN CONTENT COLUMN -->
        <div class="lg:col-span-2">
            
            <!-- GALLERY -->
            <?php if (!empty($images)): ?>
                <div class="card mb-lg overflow-hidden">
                    <div class="property-gallery">
                        <div class="property-gallery-main">
                            <img id="main-gallery-img" src="<?= htmlspecialchars($images[0]['image_path']) ?>" alt="Main property image">
                        </div>
                        <?php if (count($images) > 1): ?>
                            <div class="property-gallery-thumbs">
                                <?php foreach ($images as $index => $img): ?>
                                    <?php if ($index === 0) continue; ?>
                                    <button type="button" class="property-gallery-thumb<?= $index === 1 ? ' active' : '' ?>" onclick="setMainGalleryImage('<?= htmlspecialchars($img['image_path']) ?>', this)">
                                        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="Property thumbnail">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- DETAILS -->
            <div class="card mb-lg">
                <div class="card-header">
                    <h5>Description & Features</h5>
                </div>
                <div class="card-body">
                    <p class="text-secondary mb-xl" style="white-space: pre-line;"><?= htmlspecialchars($property["description"]) ?></p>
                    
                    <!-- PROPERTY TYPE SPECIFIC DETAILS -->
                    <?php 
                    $property_type = $property["property_type"] ?? "residential";
                    if ($property_type === "residential"): 
                    ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md pt-lg border-t">
                            <div>
                                <small class="text-muted block">Location</small>
                                <strong><?= htmlspecialchars($property["city"]) ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Listing Type</small>
                                <strong><?= $property["listing_mode"] === 'single' ? 'Entire Property' : 'Multi-Room' ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Bedrooms</small>
                                <strong><?= $property["bedrooms"] ?? '0' ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Bathrooms</small>
                                <strong><?= $property["bathrooms"] ?? '0' ?></strong>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md pt-md">
                            <div>
                                <small class="text-muted block">Furnished</small>
                                <strong><?= $property["furnished"] ? 'Yes ✓' : 'No' ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Average Rating</small>
                                <strong>⭐ <?= $avgRating ?> (<?= $totalReviews ?> reviews)</strong>
                            </div>
                        </div>
                    <?php elseif ($property_type === "commercial"): ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md pt-lg border-t">
                            <div>
                                <small class="text-muted block">Location</small>
                                <strong><?= htmlspecialchars($property["city"]) ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Commercial Type</small>
                                <strong><?= ucfirst(htmlspecialchars($property["commercial_type"] ?? 'N/A')) ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Shop Size</small>
                                <strong><?= !empty($property["shop_size"]) ? number_format($property["shop_size"], 1) . ' ' . htmlspecialchars($property["shop_unit"]) : 'N/A' ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Office Size</small>
                                <strong><?= !empty($property["office_size"]) ? number_format($property["office_size"], 1) . ' ' . htmlspecialchars($property["office_unit"]) : 'N/A' ?></strong>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md pt-md">
                            <div>
                                <small class="text-muted block">Parking Spaces</small>
                                <strong><?= $property["parking_spaces"] ?? '0' ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Average Rating</small>
                                <strong>⭐ <?= $avgRating ?> (<?= $totalReviews ?> reviews)</strong>
                            </div>
                        </div>
                    <?php elseif ($property_type === "land"): ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md pt-lg border-t">
                            <div>
                                <small class="text-muted block">Location</small>
                                <strong><?= htmlspecialchars($property["city"]) ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Land Purpose</small>
                                <strong><?= ucfirst(htmlspecialchars($property["land_purpose"] ?? 'N/A')) ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Land Size</small>
                                <strong><?= !empty($property["land_size"]) ? number_format($property["land_size"], 1) . ' ' . htmlspecialchars($property["land_unit"]) : 'N/A' ?></strong>
                            </div>
                            <div>
                                <small class="text-muted block">Road Access</small>
                                <strong><?= $property["land_road_access"] ? 'Yes ✓' : 'No' ?></strong>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-md pt-md">
                            <div>
                                <small class="text-muted block">Average Rating</small>
                                <strong>⭐ <?= $avgRating ?> (<?= $totalReviews ?> reviews)</strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ROOMS / PRICING -->
            <div class="card mb-lg">
                <div class="card-header">
                    <h5><?php 
                        if ($property_type === "residential" && $property["listing_mode"] === 'single') {
                            echo 'Pricing & Units';
                        } elseif ($property_type === "residential" && $property["listing_mode"] === 'multi_room') {
                            echo 'Available Room Types';
                        } elseif ($property_type === "commercial") {
                            echo 'Commercial Pricing';
                        } elseif ($property_type === "land") {
                            echo 'Land Pricing';
                        } else {
                            echo 'Pricing & Units';
                        }
                    ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($property_type === "residential"): ?>
                        <?php if ($property["listing_mode"] === "single"): ?>
                            <div class="flex justify-between items-center p-lg bg-alt rounded-md">
                                <div>
                                    <h3 class="mb-0 text-primary"><?php
                                        $converted_price = convertPrice($property["price"], $property_currency, $display_currency);
                                        echo formatPrice($converted_price, $display_currency_info);
                                    ?> <small class="text-muted">/ month</small></h3>
                                    <p class="text-muted mt-xs mb-0">Entire property rental</p>
                                </div>
                                <span class="badge <?= $property["status"] === 'available' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= ucfirst($property["status"]) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col gap-md">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="p-lg border rounded-md flex justify-between items-center hover:bg-alt transition-colors">
                                        <div>
                                            <h6 class="mb-xs"><?= htmlspecialchars($room["room_type"]) ?> <?php if($room["is_vip"]): ?><span class="badge bg-primary ml-sm">VIP</span><?php endif; ?></h6>
                                            <p class="text-tiny text-muted mb-0"><?= htmlspecialchars($room["features"]) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold text-lg text-primary"><?php
                                                $converted_room_price = convertPrice($room["price"], $property_currency, $display_currency);
                                                echo formatPrice($converted_room_price, $display_currency_info);
                                            ?> <small class="text-muted text-xs">/mo</small></div>
                                            <small class="<?= $room["available_rooms"] > 0 ? 'text-success' : 'text-danger' ?> font-bold">
                                                <?= $room["available_rooms"] ?> rooms available
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($property_type === "commercial"): ?>
                        <div class="flex justify-between items-center p-lg bg-alt rounded-md mb-lg">
                            <div>
                                <h3 class="mb-0 text-primary"><?php
                                    $converted_price = convertPrice($property["price"], $property_currency, $display_currency);
                                    echo formatPrice($converted_price, $display_currency_info);
                                ?> <small class="text-muted">/ month</small></h3>
                                <p class="text-muted mt-xs mb-0">Commercial Property Rental</p>
                            </div>
                            <span class="badge <?= $property["status"] === 'available' ? 'bg-success' : 'bg-danger' ?>">
                                <?= ucfirst($property["status"]) ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-md">
                            <?php if (!empty($property["commercial_type"])): ?>
                                <div class="p-md border rounded-md">
                                    <small class="text-muted block">Type</small>
                                    <strong><?= ucfirst(htmlspecialchars($property["commercial_type"])) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($property["shop_size"])): ?>
                                <div class="p-md border rounded-md">
                                    <small class="text-muted block">Shop Size</small>
                                    <strong><?= number_format($property["shop_size"], 1) ?> <?= htmlspecialchars($property["shop_unit"] ?? 'sqm') ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($property["office_size"])): ?>
                                <div class="p-md border rounded-md">
                                    <small class="text-muted block">Office Size</small>
                                    <strong><?= number_format($property["office_size"], 1) ?> <?= htmlspecialchars($property["office_unit"] ?? 'sqm') ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($property["parking_spaces"])): ?>
                                <div class="p-md border rounded-md">
                                    <small class="text-muted block">Parking</small>
                                    <strong><?= (int)$property["parking_spaces"] ?> spaces</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($property_type === "land"): ?>
                        <div class="flex justify-between items-center p-lg bg-alt rounded-md mb-lg">
                            <div>
                                <h3 class="mb-0 text-primary"><?php
                                    $converted_price = convertPrice($property["price"], $property_currency, $display_currency);
                                    echo formatPrice($converted_price, $display_currency_info);
                                ?></h3>
                                <p class="text-muted mt-xs mb-0">Land Property</p>
                            </div>
                            <span class="badge <?= $property["status"] === 'available' ? 'bg-success' : 'bg-danger' ?>">
                                <?= ucfirst($property["status"]) ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-md">
                            <?php if (!empty($property["land_size"])): ?>
                                <div class="p-md border rounded-md">
                                    <small class="text-muted block">Land Size</small>
                                    <strong><?= number_format($property["land_size"], 1) ?> <?= htmlspecialchars($property["land_unit"] ?? 'sqm') ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($property["land_purpose"])): ?>
                                <div class="p-md border rounded-md">
                                    <small class="text-muted block">Purpose</small>
                                    <strong><?= ucfirst(htmlspecialchars($property["land_purpose"])) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="p-md border rounded-md">
                                <small class="text-muted block">Road Access</small>
                                <strong><?= $property["land_road_access"] ? '✓ Available' : 'Not Available' ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- REVIEWS -->
            <div class="card mb-lg">
                <div class="card-header flex justify-between items-center">
                    <h5>User Reviews</h5>
                    <div class="flex items-center gap-sm">
                        <span class="text-tiny text-muted"><?= count($reviews) ?> total reviews</span>
                        <?php if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "client"): ?>
                            <button id="toggle-review-form" type="button" class="btn <?= $userReview ? 'btn-secondary' : 'btn-primary' ?> btn-sm">
                                <?= $userReview ? 'Edit Review' : 'Leave a Review' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "client"): ?>
                        <div id="review-form-wrapper" class="mb-lg hidden">
                            <form id="review-form" method="post" action="<?= $userReview ? 'update_review.php' : 'submit_review.php' ?>">
                                <input type="hidden" name="property_id" value="<?= $property_id ?>">
                                <input type="hidden" name="review_id" value="<?= htmlspecialchars($userReview['review_id'] ?? '') ?>">
                                <input type="hidden" name="redirect" value="1">

                                <div class="mb-md">
                                    <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs" for="review-rating">Rating</label>
                                    <select id="review-rating" name="rating" class="form-control">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <option value="<?= $i ?>" <?= isset($userReview['rating']) && $userReview['rating'] == $i ? 'selected' : '' ?>><?= $i ?> star<?= $i === 1 ? '' : 's' ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="mb-md">
                                    <label class="text-tiny uppercase tracking-wider font-bold text-muted mb-xs" for="review-text">Review</label>
                                    <textarea id="review-text" name="review" class="form-control" rows="4" placeholder="Share your experience..." required><?= htmlspecialchars($userReview['review'] ?? '') ?></textarea>
                                </div>

                                <div class="flex gap-md flex-wrap">
                                    <button type="submit" class="btn btn-primary"><?= $userReview ? 'Update Review' : 'Submit Review' ?></button>
                                    <button type="button" id="cancel-review-form" class="btn btn-secondary">Cancel</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($reviews)): ?>
                        <p class="text-muted text-center py-xl">No reviews yet for this property.</p>
                    <?php else: ?>
                        <div class="flex flex-col gap-lg">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="pb-lg border-b last:border-0">
                                    <div class="flex justify-between items-center mb-sm gap-sm">
                                        <div>
                                            <div class="font-bold"><?= htmlspecialchars($rev["full_name"]) ?></div>
                                            <?php if (!empty($rev["updated_at"]) && $rev["updated_at"] !== $rev["created_at"]): ?>
                                                <div class="text-tiny text-muted">Edited <?= date("M j, Y", strtotime($rev["updated_at"])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-gold"><?= str_repeat("⭐", $rev["rating"]) ?></div>
                                    </div>
                                    <p class="text-secondary text-tiny italic mb-xs">"<?= htmlspecialchars($rev["review"] ?? '') ?>"</p>
                                    <small class="text-muted"><?= date("M j, Y", strtotime($rev["created_at"])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="lg:col-span-1">
            
            <!-- CONTACT / ACTIONS -->
            <div class="card mb-lg">
                <div class="card-header">
                    <h5>Contact Landlord</h5>
                </div>
                <div class="card-body">
                    <div class="flex items-center gap-md mb-lg">
                        <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                            <?= substr($property["landlord_name"], 0, 1) ?>
                        </div>
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($property["landlord_name"]) ?></h6>
                            <small class="text-muted">Verified Owner</small>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-md">
                        <?php if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "client"): ?>
                            <a href="chat.php?property_id=<?= $property_id ?>" class="btn btn-primary w-full py-lg">
                                💬 Message Landlord
                            </a>
                        <?php elseif (!isset($_SESSION["user_id"])): ?>
                            <a href="login.php" class="btn btn-primary w-full py-lg">
                                🔒 Login to Message
                            </a>
                        <?php endif; ?>
                        
                        <a href="view_map.php?id=<?= $property_id ?>" class="btn btn-outline w-full py-lg">
                            📍 View on Map
                        </a>
                    </div>
                </div>
            </div>

            <!-- SIMILAR PROPERTIES -->
            <div class="card">
                <div class="card-header">
                    <h5>Similar Properties</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($similarProperties as $sp): ?>
                        <a href="property_details.php?id=<?= $sp["property_id"] ?>" class="flex items-center p-md border-b last:border-0 hover:bg-alt no-underline text-inherit">
                            <img src="<?= htmlspecialchars($sp["image_path"] ?: 'https://via.placeholder.com/80') ?>" class="w-16 h-16 object-cover rounded mr-md">
                            <div class="min-w-0">
                                <h6 class="mb-xs truncate"><?= htmlspecialchars($sp["title"]) ?></h6>
                                <div class="text-primary font-bold text-tiny"><?php
                                    $sp_currency = $sp["currency"] ?? 'XAF';
                                    $sp_display_currency = ($target_currency === $sp_currency) ? $sp_currency : $target_currency;
                                    $sp_display_currency_info = getCurrencyDetails($sp_display_currency);
                                    $converted_sp_price = convertPrice($sp["price"], $sp_currency, $sp_display_currency);
                                    echo formatPrice($converted_sp_price, $sp_display_currency_info);
                                ?> <small class="text-muted">/mo</small></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function setMainGalleryImage(src, thumbButton) {
    const mainImage = document.getElementById('main-gallery-img');
    if (!mainImage || !thumbButton) return;

    const thumbImage = thumbButton.querySelector('img');
    if (!thumbImage) return;

    const previousMainSrc = mainImage.src;
    mainImage.src = thumbImage.src;
    thumbImage.src = previousMainSrc;

    document.querySelectorAll('.property-gallery-thumb.active').forEach(btn => {
        btn.classList.remove('active');
    });

    thumbButton.classList.add('active');
}

// Favorite toggle logic
document.addEventListener("click", function(e) {
    const button = e.target.closest(".favorite-btn");
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

const reviewToggle = document.getElementById('toggle-review-form');
const reviewFormWrapper = document.getElementById('review-form-wrapper');
const cancelReviewBtn = document.getElementById('cancel-review-form');

if (reviewToggle && reviewFormWrapper) {
    reviewToggle.addEventListener('click', () => {
        reviewFormWrapper.classList.toggle('hidden');
        reviewToggle.textContent = reviewFormWrapper.classList.contains('hidden') ? '<?= $userReview ? 'Edit Review' : 'Leave a Review' ?>' : 'Hide Review';
    });
}

if (cancelReviewBtn && reviewFormWrapper) {
    cancelReviewBtn.addEventListener('click', () => {
        reviewFormWrapper.classList.add('hidden');
        if (reviewToggle) {
            reviewToggle.textContent = '<?= $userReview ? 'Edit Review' : 'Leave a Review' ?>';
        }
    });
}
</script>

<script src="assets/js/guest-auth.js"></script>
</body>
</html>
