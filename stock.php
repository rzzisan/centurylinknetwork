<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "স্টক এন্ট্রি";
include 'header.php';
include 'sidebar.php';

// Fetch brands for dropdown
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch all stock entry data with brand name
$stock_entries = $pdo->query(
    "SELECT se.id, b.name as brand_name, se.quantity, se.purchase_date, se.created_at
     FROM stock_entries se
     JOIN brands b ON se.brand_id = b.id
     ORDER BY se.purchase_date DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container-fluid">
    <h2>স্টক এন্ট্রি</h2>

    <div class="card mb-4">
         <div class="card-header"><h3>নতুন স্টক এন্ট্রি করুন</h3></div>
         <div class="card-body">
            <form id="add-stock-form" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="add_stock_brand_id" class="form-label">ব্র্যান্ড</label>
                    <select id="add_stock_brand_id" name="brand_id" class="form-select" required>
                        <option value="">ব্র্যান্ড নির্বাচন করুন</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="add_stock_quantity" class="form-label">পরিমাণ</label>
                    <input type="number" class="form-control" id="add_stock_quantity" name="quantity" min="1" required>
                </div>
                 <div class="col-md-3">
                    <label for="add_stock_purchase_date" class="form-label">ক্রয়ের তারিখ</label>
                    <input type="date" class="form-control" id="add_stock_purchase_date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">এন্ট্রি করুন</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>সকল স্টক এন্ট্রি</h3></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="stock-table" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ব্র্যান্ড</th>
                            <th>পরিমাণ</th>
                            <th>ক্রয়ের তারিখ</th>
                            <th>এন্ট্রির তারিখ</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stock_entries)): ?>
                            <tr><td colspan="5" class="text-center">কোনো স্টক এন্ট্রি নেই।</td></tr>
                        <?php else: foreach ($stock_entries as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($item['purchase_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y, h:i A', strtotime($item['created_at']))); ?></td>
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

<div class="modal fade" id="edit-stock-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">স্টক এন্ট্রি এডিট করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-stock-form">
                    <input type="hidden" id="edit_stock_id" name="id">
                    <div class="mb-3">
                        <label for="edit_stock_brand_id" class="form-label">ব্র্যান্ড</label>
                        <select class="form-select" id="edit_stock_brand_id" name="brand_id" required>
                            <option value="">ব্র্যান্ড নির্বাচন করুন</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_stock_quantity" class="form-label">পরিমাণ</label>
                        <input type="number" class="form-control" id="edit_stock_quantity" name="quantity" min="1" required>
                    </div>
                     <div class="mb-3">
                        <label for="edit_stock_purchase_date" class="form-label">ক্রয়ের তারিখ</label>
                        <input type="date" class="form-control" id="edit_stock_purchase_date" name="purchase_date" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="submit" form="edit-stock-form" class="btn btn-primary">সেভ করুন</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/management.js"></script>

<?php
include 'footer.php';
?>