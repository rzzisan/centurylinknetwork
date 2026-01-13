<?php
require_once 'config.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = 'অনুগ্রহ করে ইউজারনেম এবং পাসওয়ার্ড দিন।';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
        $stmt->execute([$username]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee && password_verify($password, $employee['password'])) {
            $_SESSION['employee_id'] = $employee['id'];
            $_SESSION['employee_name'] = $employee['full_name'];
            $_SESSION['role'] = $employee['role'];
            header("Location: index.php");
            exit();
        } else {
            $error_message = 'ভুল ইউজারনেম অথবা পাসওয়ার্ড।';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - ONU Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin-top: 10vh;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-sm login-container mx-auto">
            <div class="card-body p-5">
                <h1 class="card-title text-center mb-4">কর্মচারী লগইন</h1>
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">ইউজারনেম</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">পাসওয়ার্ড</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">লগইন করুন</button>
                </form>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>