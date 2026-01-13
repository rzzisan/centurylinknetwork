<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "অন্যান্য স্টক ম্যানেজমেন্ট";
include 'header.php';
include 'sidebar.php';

// Fetch data for dropdowns and stock summary
$products = $pdo->query("SELECT id, name, current_stock FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Filter and Pagination Logic
$params = [];
$sql_where = " WHERE 1=1";

// Existing Filters
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';
$filter_product_id = $_GET['product_id'] ?? '';
$filter_log_type = $_GET['log_type'] ?? '';
$filter_employee_id = $_GET['employee_id'] ?? '';

if ($filter_start_date) { $sql_where .= " AND psl.log_date >= :start_date"; $params[':start_date'] = $filter_start_date; }
if ($filter_end_date) { $sql_where .= " AND psl.log_date <= :end_date"; $params[':end_date'] = $filter_end_date; }
if ($filter_product_id) { $sql_where .= " AND psl.product_id = :product_id"; $params[':product_id'] = $filter_product_id; }
if ($filter_log_type) { $sql_where .= " AND psl.log_type = :log_type"; $params[':log_type'] = $filter_log_type; }
if ($filter_employee_id) { $sql_where .= " AND psl.employee_id = :employee_id"; $params[':employee_id'] = $filter_employee_id; }


// Rows per page logic
$items_per_page = 50; // Default value
if (isset($_GET['per_page'])) {
    if ($_GET['per_page'] == 'all') {
        $items_per_page = 0; // Will be used to signify no limit
    } else {
        $items_per_page = (int)$_GET['per_page'];
    }
}


// Pagination Logic
$current_page = $_GET['page'] ?? 1;
$offset = ($current_page - 1) * $items_per_page;
$total_records_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM product_stock_logs psl" . $sql_where
);
$total_records_stmt->execute($params);
$total_records = $total_records_stmt->fetchColumn();
$total_pages = ($items_per_page > 0) ? ceil($total_records / $items_per_page) : 1;


// Fetch stock log data with filters and pagination
$sql_limit = "";
if ($items_per_page > 0) {
    $sql_limit = " LIMIT :limit OFFSET :offset";
}

$sql = "SELECT psl.id, psl.log_date, p.name as product_name, psl.log_type, psl.quantity, psl.unit_price, psl.total_price, e.full_name as employee_name, psl.reference_customer_id
     FROM product_stock_logs psl
     JOIN products p ON psl.product_id = p.id
     LEFT JOIN employees e ON psl.employee_id = e.id
     $sql_where
     ORDER BY psl.log_date DESC, psl.created_at DESC
     $sql_limit";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
if ($items_per_page > 0) {
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
}
$stmt->execute();
$stock_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-hdd-stack-fill"></i> অন্যান্য স্টক ম্যানেজমেন্ট</h2>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#stock-in-modal"><i class="bi bi-box-arrow-in-down"></i> স্টক এন্ট্রি</button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#stock-out-modal"><i class="bi bi-box-arrow-up"></i> স্টক আউট</button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-product-modal"><i class="bi bi-plus-circle"></i> নতুন প্রোডাক্ট</button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3><i class="bi bi-graph-up"></i> বর্তমান স্টক</h3>
        </div>
        <div class="card-body">
            <div class="row">
                 <?php if(empty($products)): ?>
                    <div class="col"><p>কোনো প্রোডাক্ট যোগ করা হয়নি।</p></div>
                <?php else: foreach($products as $product): ?>
                    <div class="col-lg-3 col-md-4 mb-3">
                         <div class="card text-center bg-light h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text fs-2 fw-bold"><?php echo htmlspecialchars($product['current_stock']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h3><i class="bi bi-search"></i> ফিল্টার করুন</h3></div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3"><label class="form-label">শুরুর তারিখ</label><input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>"></div>
                <div class="col-md-3"><label class="form-label">শেষ তারিখ</label><input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>"></div>
                <div class="col-md-3"><label class="form-label">প্রোডাক্ট</label><select name="product_id" class="form-select"><option value="">সব</option><?php foreach($products as $p) echo "<option value='{$p['id']}' ".($filter_product_id == $p['id'] ? 'selected' : '').">{$p['name']}</option>"; ?></select></div>
                <div class="col-md-3"><label class="form-label">লগের ধরণ</label><select name="log_type" class="form-select"><option value="">সব</option><option value="in" <?php if($filter_log_type == 'in') echo 'selected';?>>স্টক ইন</option><option value="out" <?php if($filter_log_type == 'out') echo 'selected';?>>স্টক আউট</option></select></div>
                <div class="col-md-3"><label class="form-label">কর্মচারী</label><select name="employee_id" class="form-select"><option value="">সব</option><?php foreach($employees as $e) echo "<option value='{$e['id']}' ".($filter_employee_id == $e['id'] ? 'selected' : '').">{$e['full_name']}</option>"; ?></select></div>
                <div class="col-md-12"><button type="submit" class="btn btn-primary">ফিল্টার</button> <a href="other_stock.php" class="btn btn-secondary">রিসেট</a></div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form action="other_stock.php" method="get" class="d-flex align-items-center">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($filter_product_id); ?>">
            <input type="hidden" name="log_type" value="<?php echo htmlspecialchars($filter_log_type); ?>">
            <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($filter_employee_id); ?>">

            <label for="per_page" class="me-2">দেখানো হবে:</label>
            <select name="per_page" id="per_page" class="form-select w-auto" onchange="this.form.submit()">
                <option value="25" <?php echo ($items_per_page == 25) ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo ($items_per_page == 50) ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo ($items_per_page == 100) ? 'selected' : ''; ?>>100</option>
                <option value="all" <?php echo ($items_per_page == 0) ? 'selected' : ''; ?>>সব</option>
            </select>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h4><i class="bi bi-list-ol"></i> স্টক লগ (নতুন থেকে পুরাতন)</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr><th>নং</th><th>তারিখ</th><th>প্রোডাক্ট</th><th>ধরন</th><th>পরিমাণ</th><th>কর্মচারী/মূল্য</th><th>রেফারেন্স</th>
                        <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody id="log-table-body">
                        <?php if(empty($stock_logs)): ?>
                            <tr><td colspan="8" class="text-center">কোনো লগ নেই।</td></tr>
                        <?php else: foreach($stock_logs as $index => $log): ?>
                        <tr>
                            <td><?php echo $offset + $index + 1; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($log['log_date'])); ?></td>
                            <td><?php echo htmlspecialchars($log['product_name']); ?></td>
                            <td>
                                <?php if($log['log_type'] == 'in'): ?>
                                    <span class="badge bg-success">স্টক ইন</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">স্টক আউট</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $log['quantity']; ?></td>
                            <td>
                                <?php if($log['log_type'] == 'in'): ?>
                                    <?php echo number_format($log['total_price'], 2); ?> টাকা
                                <?php else: ?>
                                    <?php echo htmlspecialchars($log['employee_name']); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['reference_customer_id'] ?? ''); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" data-id="<?php echo $log['id']; ?>"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $log['id']; ?>"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
             <?php if($total_pages > 1): ?>
            <nav><ul class="pagination justify-content-center mt-4">
                <?php 
                $query_params = $_GET;
                unset($query_params['page']);
                $query_string = http_build_query($query_params);
                for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($i == $current_page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i . '&' . $query_string; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="add-product-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">নতুন প্রোডাক্ট যোগ করুন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="add-product-form">
                    <div class="mb-3"><label class="form-label">প্রোডাক্টের নাম</label><input type="text" name="product_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">কোম্পানি (ঐচ্ছিক)</label><input type="text" name="company" class="form-control"></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button><button type="submit" form="add-product-form" class="btn btn-primary">প্রোডাক্ট যোগ করুন</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="stock-in-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
             <div class="modal-header"><h5 class="modal-title">স্টক এন্ট্রি করুন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
             <div class="modal-body">
                <form id="stock-in-form">
                    <div class="mb-3"><label class="form-label">প্রোডাক্ট</label><select name="product_id" class="form-select" required><option value="">নির্বাচন করুন</option><?php foreach($products as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?></select></div>
                    <div class="mb-3"><label class="form-label">তারিখ</label><input type="date" name="log_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">পরিমাণ</label><input type="number" id="in_quantity" name="quantity" class="form-control" min="1" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">মূল্য (প্রতি পিস)</label><input type="number" id="in_unit_price" name="unit_price" class="form-control" step="0.01" min="0" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">মোট মূল্য</label><input type="text" id="in_total_price" class="form-control" disabled></div>
                </form>
             </div>
             <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button><button type="submit" form="stock-in-form" class="btn btn-success">স্টক যোগ করুন</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="stock-out-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">স্টক আউট / বিতরণ করুন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="stock-out-form">
                    <div class="mb-3"><label class="form-label">প্রোডাক্ট</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">নির্বাচন করুন</option>
                            <?php foreach($products as $p) echo "<option value='{$p['id']}'>{$p['name']} (স্টকে আছে: {$p['current_stock']})</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">তারিখ</label><input type="date" name="log_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="mb-3"><label class="form-label">পরিমাণ</label><input type="number" name="quantity" class="form-control" min="1" required></div>
                    <div class="mb-3"><label class="form-label">কর্মচারী</label><select name="employee_id" class="form-select" required><option value="">নির্বাচন করুন</option><?php foreach($employees as $e) echo "<option value='{$e['id']}'>{$e['full_name']}</option>"; ?></select></div>
                    <div class="mb-3"><label class="form-label">রেফারেন্স কাস্টমার আইডি (ঐচ্ছিক)</label><input type="text" name="reference_customer_id" class="form-control"></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button><button type="submit" form="stock-out-form" class="btn btn-warning">প্রদান করুন</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit-log-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">স্টক লগ এডিট করুন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="edit-log-form">
                    <input type="hidden" id="edit_log_id" name="log_id">
                    <div class="mb-3"><label class="form-label">তারিখ</label><input type="date" id="edit_log_date" name="log_date" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">প্রোডাক্ট</label><select id="edit_product_id" name="product_id" class="form-select" required><?php foreach($products as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?></select></div>
                    <div class="mb-3"><label class="form-label">পরিমাণ</label><input type="number" id="edit_quantity" name="quantity" class="form-control" min="1" required></div>
                    
                    <div id="edit-stock-in-fields" style="display: none;">
                         <div class="mb-3"><label class="form-label">মূল্য (প্রতি পিস)</label><input type="number" id="edit_unit_price" name="unit_price" class="form-control" step="0.01" min="0"></div>
                    </div>
                    <div id="edit-stock-out-fields" style="display: none;">
                        <div class="mb-3"><label class="form-label">কর্মচারী</label><select id="edit_employee_id" name="employee_id" class="form-select"><?php foreach($employees as $e) echo "<option value='{$e['id']}'>{$e['full_name']}</option>"; ?></select></div>
                        <div class="mb-3"><label class="form-label">রেফারেন্স কাস্টমার আইডি</label><input type="text" id="edit_reference_customer_id" name="reference_customer_id" class="form-control"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button><button type="submit" form="edit-log-form" class="btn btn-primary">আপডেট করুন</button></div>
        </div>
    </div>
</div>

<script src="assets/other_stock.js"></script>

<?php
include 'footer.php';
?>