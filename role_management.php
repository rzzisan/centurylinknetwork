<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "ভূমিকা ব্যবস্থাপনা";
include 'header.php';
include 'sidebar.php';

$roles_data = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2>ভূমিকা ব্যবস্থাপনা</h2>

    <div class="card mb-4">
        <div class="card-header"><h3>নতুন ভূমিকা যোগ করুন</h3></div>
        <div class="card-body">
            <form id="add-role-form" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label for="add_role_name" class="form-label">ভূমিকার নাম</label>
                    <input type="text" class="form-control" id="add_role_name" name="name" placeholder="e.g., admin" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">যোগ করুন</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-person-badge-fill"></i> সকল ভূমিকা</h3>
                <div class="d-flex">
                    <input type="text" id="search-roles" class="form-control me-2" placeholder="ভূমিকা খুঁজুন...">
                    <button id="search-btn" class="btn btn-primary me-2">খুঁজুন</button>
                    <button id="show-all-btn" class="btn btn-secondary">সব দেখান</button>
                </div>
            </div>
        </div>
        <div class="card-body">
             <div class="table-responsive">
                <table id="roles-table" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ভূমিকার নাম</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($roles_data)): ?>
                            <tr><td colspan="2" class="text-center">কোনো ভূমিকা যোগ করা হয়নি।</td></tr>
                        <?php else: foreach ($roles_data as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-edit" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>"><i class="bi bi-pencil-square"></i> এডিট</button>
                                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>"><i class="bi bi-trash"></i> ডিলিট</button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit-role-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ভূমিকা এডিট করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-role-form">
                    <input type="hidden" id="edit_role_id" name="id">
                    <div class="mb-3">
                        <label for="edit_role_name" class="form-label">নতুন ভূমিকার নাম</label>
                        <input type="text" class="form-control" id="edit_role_name" name="new_name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="submit" form="edit-role-form" class="btn btn-primary">সেভ করুন</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/roles.js"></script>

<?php
include 'footer.php';
?>