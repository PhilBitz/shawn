<?php
session_start();
require_once "config/db.php";

$errors = [];
$suspended_user_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {

            /* =========================
               CHECK ACCOUNT STATUS
            ========================= */

            if (isset($user["status"]) && $user["status"] === "suspended") {

                $errors[] = "Your account has been suspended.";
                $suspended_user_id = $user["user_id"];

            } else {

                /* =========================
                   SECURE SESSION
                ========================= */

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["country"] = $user["country"];

                /* =========================
                   ROLE REDIRECTION
                ========================= */

                if ($user["role"] === "admin") {
                    header("Location: admin/dashboard.php");
                } elseif ($user["role"] === "landlord") {
                    header("Location: landlord/dashboard.php");
                } elseif ($user["role"] === "client") {
                    header("Location: client/dashboard.php");
                } else {
                    header("Location: discover.php");
                }

                exit();
            }

        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StaySphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-white">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 20px;">

    <div class="flex items-center justify-center" style="min-height: 100vh;">
        <div class="w-full" style="max-width: 480px;">
            
            <div class="text-center mb-xl">
                <h1 class="page-title" style="font-size: 1.875rem;">Welcome Back</h1>
                <p class="text-muted">Sign in to your StaySphere account</p>
            </div>

            <div class="card shadow-lg">
                <div class="card-body p-xl">
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-lg">
                            <div class="alert-body">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET["registered"])): ?>
                        <div class="alert alert-success mb-lg">
                            <div class="alert-body">
                                <strong>Success!</strong> Your account has been created. Please log in below.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($suspended_user_id): ?>
                        <div class="alert alert-warning mb-lg">
                            <div class="alert-body">
                                <p>Your account is currently suspended.</p>
                                <a href="appeal.php?user=<?= $suspended_user_id ?>" class="btn btn-dark btn-sm mt-sm">Submit Appeal</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="required">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" required autofocus>
                        </div>

                        <div class="form-group">
                            <div class="flex justify-between items-center">
                                <label class="required">Password</label>
                                <a href="#" class="text-tiny text-primary">Forgot password?</a>
                            </div>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-full py-lg mt-md">
                            Sign In
                        </button>
                    </form>

                </div>
                <div class="card-footer bg-alt justify-center">
                    <p class="mb-0 text-muted">
                        Don't have an account? <a href="register.php" class="font-bold text-primary">Create Account</a>
                    </p>
                </div>
            </div>
            
            <div class="text-center mt-xl">
                <a href="index.php" class="text-secondary">← Back to Homepage</a>
            </div>

        </div>
    </div>

</div>

</body>
</html>
