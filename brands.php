<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "ব্র্যান্ড ম্যানেজমেন্ট";
include 'header.php';
include 'sidebar.php';

$brands_data = $pdo->query("SELECT id, name, price, created_at FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2>ব্র্যান্ড ম্যানেজমেন্ট</h2>

    <div class="card mb-4">
        <div class="card-header"><h3>নতুন ব্র্যান্ড যোগ করুন</h3></div>
        <div class="card-body">
            <form id="add-brand-form" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="add_brand_name" class="form-label">ব্র্যান্ডের নাম</label>
                    <input type="text" class="form-control" id="add_brand_name" name="name" placeholder="e.g., Venus" required>
                </div>
                <div class="col-md-5">
                    <label for="add_brand_price" class="form-label">ক্রয় মূল্য (Price)</label>
                    <input type="number" class="form-control" id="add_brand_price" name="price" step="0.01" min="0" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">যোগ করুন</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>সকল ব্র্যান্ড</h3></div>
        <div class="card-body">
             <div class="table-responsive">
                <table id="brands-table" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ব্র্যান্ডের নাম</th>
                            <th>ক্রয় মূল্য</th>
                            <th>তৈরির তারিখ</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($brands_data)): ?>
                            <tr><td colspan="4" class="text-center">কোনো ব্র্যান্ড যোগ করা হয়নি।</td></tr>
                        <?php else: foreach ($brands_data as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($item['created_at']))); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-edit" data-id="<?php echo $item['id']; ?>"><i class="bi bi-pencil-square"></i> এডিট</button>
                                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $item['id']; ?>"><i class="bi bi-trash"></i> ডিলিট</button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit-brand-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ব্র্যান্ড এডিট করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-brand-form">
                    <input type="hidden" id="edit_brand_id" name="id">
                    <div class="mb-3">
                        <label for="edit_brand_name" class="form-label">ব্র্যান্ডের নাম</label>
                        <input type="text" class="form-control" id="edit_brand_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_brand_price" class="form-label">ক্রয় মূল্য</label>
                        <input type="number" class="form-control" id="edit_brand_price" name="price" step="0.01" min="0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="submit" form="edit-brand-form" class="btn btn-primary">সেভ করুন</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/management.js"></script>

<?php
include 'footer.php';
?>