<?php
require_once "config/db.php";

$user_id = (int)($_GET["user"] ?? 0);
$success = "";
$error = "";

/* =========================
   CHECK IF APPEAL EXISTS
========================= */
$stmt = $pdo->prepare("SELECT * FROM appeals WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$existingAppeal = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingAppeal) {
    $error = "You already submitted an appeal. Please wait for the administrator to review it.";
}

/* =========================
   HANDLE FORM SUBMISSION
========================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$existingAppeal) {
    $message = trim($_POST["message"]);
    if (empty($message)) {
        $error = "Please explain your appeal.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO appeals (user_id, message) VALUES (?, ?)");
        $stmt->execute([$user_id, $message]);
        $success = "Appeal submitted successfully. Redirecting to login page...";
        header("refresh:3;url=login.php");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Appeal - StaySphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-white">

<?php include 'includes/navbar.php'; ?>

<div class="flex items-center justify-center" style="min-height: calc(100vh - 80px); padding: 20px;">
    <div class="container" style="max-width: 600px;">
    <div class="text-center mb-xl">
        <h1 class="font-bold text-primary" >StaySphere</h1>
        <p class="text-muted">Account Restoration Center</p>
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-alt">
            <h5 class="mb-0">Account Suspension Appeal</h5>
        </div>
        <div class="card-body">
            <?php if($error): ?>
                <div class="alert alert-danger mb-lg">
                    <div class="alert-body"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success mb-lg">
                    <div class="alert-body"><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <?php if(!$existingAppeal && !$success): ?>
                <form method="POST">
                    <div class="form-group">
                        <label class="required">Reason for Appeal</label>
                        <p class="text-tiny text-muted mb-sm">Please explain clearly why your account should be restored. Provide any relevant details or context.</p>
                        <textarea name="message" class="form-control" rows="6" placeholder="Explain your situation here..." required></textarea>
                    </div>

                    <div class="flex gap-md mt-xl">
                        <button type="submit" class="btn btn-primary flex-1 py-md">Submit Appeal</button>
                        <a href="login.php" class="btn btn-secondary py-md">Back to Login</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-lg">
                    <p class="text-muted mb-lg">Thank you for your patience. Our administrators will review your request shortly.</p>
                    <a href="login.php" class="btn btn-primary px-xl">Return to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <p class="text-center text-tiny text-muted mt-xl">
        &copy; <?= date('Y') ?> StaySphere Property Rental System. All rights reserved.
    </p>
    </div>
</div>

</body>
</html>
