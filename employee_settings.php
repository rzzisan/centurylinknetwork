<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "কর্মচারী ম্যানেজমেন্ট";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salaries = $_POST['salary'] ?? [];
    $includes = $_POST['include'] ?? [];
    $roles = $_POST['role_id'] ?? [];

    try {
        $pdo->beginTransaction();

        foreach ($salaries as $id => $salary) {
            $include = isset($includes[$id]) ? 1 : 0;
            $role_id = $roles[$id] ?? null; 

            $stmt = $pdo->prepare("UPDATE employees SET salary = ?, include_in_attendance = ?, role_id = ? WHERE id = ?");
            $stmt->execute([$salary, $include, $role_id, $id]);
        }

        $pdo->commit();
        $success_message = "Employee settings updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to update settings: " . $e->getMessage();
    }
}

include 'header.php';
include 'sidebar.php';

// Fetch all employees
$employees = $pdo->query("SELECT id, full_name, salary, include_in_attendance, role_id FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch all distinct roles
$roles_list = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-people-fill"></i> কর্মচারী ম্যানেজমেন্ট</h2>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4>সকল কর্মচারীর তালিকা</h4>
            <small>এখান থেকে কর্মচারীদের মাসিক বেতন, হাজিরা এবং ভূমিকা নির্ধারণ করুন।</small>
        </div>
        <div class="card-body">
            <form id="employee-settings-form" method="POST">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>কর্মচারীর নাম</th>
                                <th>মাসিক বেতন (টাকা)</th>
                                <th>ভূমিকা</th>
                                <th>হাজিরায় অন্তর্ভুক্ত?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                <td>
                                    <input type="number" class="form-control" name="salary[<?php echo $emp['id']; ?>]" value="<?php echo htmlspecialchars($emp['salary']); ?>" step="0.01" min="0">
                                </td>
                                <td>
                                    <select class="form-select" name="role_id[<?php echo $emp['id']; ?>]">
                                        <option value="">ভূমিকা নির্বাচন করুন</option>
                                        <?php foreach ($roles_list as $role): ?>
                                            <option value="<?php echo $role['id']; ?>" <?php if ($emp['role_id'] === $role['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars(ucfirst($role['name'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="include[<?php echo $emp['id']; ?>]" value="1" <?php if($emp['include_in_attendance']) echo 'checked'; ?>>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">সেভ করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>