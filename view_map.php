<?php
session_start();
require_once "config/db.php";

// Guest user restriction - require login to view map
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Store the current page as redirect destination
    $currentPage = "view_map.php?id=" . (isset($_GET['id']) ? urlencode($_GET['id']) : '');
    header("Location: login.php?redirect=" . urlencode($currentPage));
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$property_id = (int) $_GET["id"];

$stmt = $pdo->prepare("
    SELECT p.*, c.category_name, u.full_name AS landlord_name 
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

$hasCoordinates = $property["latitude"] !== null && $property["longitude"] !== null;
$latitude = $hasCoordinates ? (float) $property["latitude"] : null;
$longitude = $hasCoordinates ? (float) $property["longitude"] : null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Location - StaySphere</title>    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-white">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 20px;">

    <h1 class="page-title mb-sm" style="font-size: 1.875rem;">Property Location</h1>
    <p class="text-muted mb-lg"><?php echo htmlspecialchars($property["title"]) ?></p>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-xl">
        
        <!-- INFO SIDEBAR -->
        <div class="lg:col-span-1">
            <div class="card mb-lg">
                <div class="card-header">
                    <h5>Address Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-lg">
                        <small class="text-muted block">Street Address</small>
                        <strong><?php echo htmlspecialchars($property["address"]) ?></strong>
                    </div>
                    <div class="mb-lg">
                        <small class="text-muted block">City / Region</small>
                        <strong><?php echo htmlspecialchars($property["city"]) ?></strong>
                    </div>
                    <div class="mb-lg">
                        <small class="text-muted block">Property Category</small>
                        <span class="badge bg-info"><?php echo htmlspecialchars($property["category_name"]) ?></span>
                    </div>
                    <hr class="my-lg">
                    <div class="mb-lg">
                        <small class="text-muted block">Listed by</small>
                        <strong><?php echo htmlspecialchars($property["landlord_name"]) ?></strong>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <div class="alert-body">
                    <p class="text-tiny mb-0">Use the interactive map to explore the surrounding area and nearby amenities.</p>
                </div>
            </div>
        </div>

        <!-- MAP AREA -->
        <div class="lg:col-span-3">
            <div class="card shadow-lg overflow-hidden">
                <div class="card-body p-0">
                    <div style="height: 600px; width: 100%;">
                        <iframe
                            src="<?php echo $hasCoordinates
                                ? 'https://www.google.com/maps?output=embed&q=' . urlencode($latitude . ',' . $longitude)
                                : 'https://www.google.com/maps?output=embed&q=' . urlencode($property['address'] . ', ' . $property['city']); ?>"
                            style="border:0; width:100%; height:100%;"
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
                <div class="card-footer bg-alt flex justify-between items-center">
                    <button id="getDirectionsBtn" type="button" class="btn btn-primary">
                        📍 Get Directions
                    </button>
                    <?php if ($hasCoordinates): ?>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($latitude . ',' . $longitude) ?>" target="_blank" class="btn btn-outline">
                            Open in Google Maps
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <p class="text-muted mt-md text-tiny text-center">
                Directions will use your current GPS location to calculate the best route.
            </p>
        </div>
    </div>

</div>

<script>
document.getElementById('getDirectionsBtn').addEventListener('click', function() {
    const destination = <?php echo json_encode($hasCoordinates ? $latitude . ',' . $longitude : $property["address"] . ", " . $property["city"] ) ?>;
    if (!navigator.geolocation) {
        window.open('https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destination), '_blank');
        return;
    }

    navigator.geolocation.getCurrentPosition(function(position) {
        const origin = position.coords.latitude + ',' + position.coords.longitude;
        const url = 'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origin) + '&destination=' + encodeURIComponent(destination) + '&travelmode=driving';
        window.open(url, '_blank');
    }, function(error) {
        window.open('https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destination), '_blank');
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    });
});
</script>

</body>
</html>
