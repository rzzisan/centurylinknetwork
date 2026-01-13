<?php
require_once 'config.php';
redirect_if_not_logged_in();
// redirect_if_not_admin(); // This line is removed

require_once 'header.php';
require_once 'sidebar.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role_id = trim($_POST['role_id']); // Role is now role_id

    if (empty($fullname) || empty($username) || empty($password) || empty($role_id)) {
        $message = '<p class="error">Please fill in all fields.</p>';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO employees (full_name, username, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullname, $username, $hashed_password, $role_id]);
            $message = '<p class="success">Employee created successfully.</p>';
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $message = '<p class="error">This username is already taken. Please choose another one.</p>';
            } else {
                $message = '<p class="error">An error occurred: ' . $e->getMessage() . '</p>';
            }
        }
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add New Employee</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Employee Details</h6>
        </div>
        <div class="card-body">
            <form action="add_employee.php" method="post">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select class="form-control" id="role_id" name="role_id" required>
                        <?php
                        $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($roles as $role) {
                            echo '<option value="' . $role['id'] . '">' . htmlspecialchars($role['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create Employee</button>
            </form>
            <?php if ($message): ?>
                <div class="mt-3"><?php echo $message; ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>