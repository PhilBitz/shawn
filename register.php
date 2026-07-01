<?php
require_once "config/db.php";
require_once "includes/functions.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $country = trim($_POST["country"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = $_POST["role"];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($country) || empty($password) || empty($role)) {
        $errors[] = "All required fields must be filled.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    // Allow only landlord or client registration
    if (!in_array($role, ['landlord', 'client'])) {
        $errors[] = "Invalid role selected.";
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $errors[] = "Email already registered.";
    }

    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);

    if ($stmt->rowCount() > 0) {
        $errors[] = "Phone number already registered.";
    }

    // If no errors → Insert user
    if (empty($errors)) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO users (full_name, email, phone, country, password, role, account_status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        try {
            $insert->execute([
                $full_name,
                $email,
                $phone,
                $country,
                $hashed_password,
                $role
            ]);

            header("Location: login.php?registered=success");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // Check if it's a duplicate phone number
                $check_phone = $pdo->prepare("SELECT user_id FROM users WHERE phone = ?");
                $check_phone->execute([$phone]);
                if ($check_phone->rowCount() > 0) {
                    $errors[] = "Phone number already registered.";
                } else {
                    $errors[] = "Email already registered."; // Fallback for other unique constraint violations
                }
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - StaySphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-white">
<?php include 'includes/navbar.php'; ?>
<div class="container" style="max-width: 1280px; margin: 0 auto; padding: 0 20px;">

    <div class="flex items-center justify-center" style="min-height: 100vh; padding: 40px 0;">
        <div class="w-full" style="max-width: 600px;">
            
            <div class="text-center mb-xl">
                <h1 class="page-title" style="font-size: 1.875rem;">Join StaySphere</h1>
                <p class="text-muted">Create an account to start your property journey</p>
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

                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">Full Name</label>
                                <input type="text" name="full_name" class="form-control" placeholder="name" value="<?= isset($full_name) ? htmlspecialchars($full_name) : '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="required">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label class="required">Country</label>
                                <select name="country" class="form-control" required>
                                    <option value="">-- Select Country --</option>
                                    <?php
                                    foreach ($countries_currencies as $country_name => $code) {
                                        $currency_details = getCurrencyInfo($country_name);
                                        $selected = (isset($country) && $country == $country_name) ? 'selected' : '';
                                        echo "<option value=\"$country_name\" $selected>$country_name ({$currency_details['symbol']} {$currency_details['currency']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">Account Type</label>
                                <select name="role" class="form-control" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="client" <?= isset($role) && $role == 'client' ? 'selected' : '' ?>>Client (Looking for property)</option>
                                    <option value="landlord" <?= isset($role) && $role == 'landlord' ? 'selected' : '' ?>>Landlord (Listing property)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                <small class="text-muted">Min. 6 characters</small>
                            </div>
                            <div class="form-group">
                                <label class="required">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="mt-md">
                            <label class="flex items-center gap-sm cursor-pointer">
                                <input type="checkbox" required>
                                <span class="text-tiny text-muted">I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a></span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-full py-lg mt-xl">
                            Create Account
                        </button>
                    </form>

                </div>
                <div class="card-footer bg-alt justify-center">
                    <p class="mb-0 text-muted">
                        Already have an account? <a href="login.php" class="font-bold text-primary">Sign In</a>
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
