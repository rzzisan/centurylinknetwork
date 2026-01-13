<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "নতুন কানেকশন";
include 'header.php';
include 'sidebar.php';

// Fetch data for dropdowns
$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT name FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// Filter Logic
$params = [];
$sql_where = " WHERE 1=1";
if (!empty($_GET['start_date'])) { $sql_where .= " AND nc.connection_date >= :start_date"; $params[':start_date'] = $_GET['start_date']; }
if (!empty($_GET['end_date'])) { $sql_where .= " AND nc.connection_date <= :end_date"; $params[':end_date'] = $_GET['end_date']; }
if (!empty($_GET['connection_type'])) { $sql_where .= " AND nc.connection_type = :connection_type"; $params[':connection_type'] = $_GET['connection_type']; }
if (!empty($_GET['materials_used'])) { $sql_where .= " AND FIND_IN_SET(:materials_used, nc.materials_used)"; $params[':materials_used'] = $_GET['materials_used']; }
if (!empty($_GET['order_taker_id'])) { $sql_where .= " AND nc.order_taker_id = :order_taker_id"; $params[':order_taker_id'] = $_GET['order_taker_id']; }
if (!empty($_GET['money_with_id'])) { $sql_where .= " AND nc.money_with_id = :money_with_id"; $params[':money_with_id'] = $_GET['money_with_id']; }

// Pagination Logic
$show_options = [25, 50, 100];
$items_per_page_default = 50; // আপনার অনুরোধ অনুযায়ী ডিফল্ট ৫০ করা হলো

$show = $_GET['show'] ?? $items_per_page_default;

if (in_array($show, $show_options)) {
    $items_per_page = (int)$show;
    $limit_sql = " LIMIT :limit OFFSET :offset";
    $is_all = false;
} elseif ($show == 'all') {
    $limit_sql = ""; // 'all' হলে কোনো লিমিট থাকবে না
    $is_all = true;
} else {
    $show = $items_per_page_default; // ভুল ইনপুটের ক্ষেত্রে ডিফল্টে ফিরে যান
    $items_per_page = $items_per_page_default;
    $limit_sql = " LIMIT :limit OFFSET :offset";
    $is_all = false;
}

$current_page = $_GET['page'] ?? 1;

$total_records_stmt = $pdo->prepare("SELECT COUNT(*) FROM new_connections nc" . $sql_where);
$total_records_stmt->execute($params);
$total_records = $total_records_stmt->fetchColumn();

if ($is_all) {
    $total_pages = 1;
    $current_page = 1;
    $offset = 0;
    $items_per_page = $total_records; // টেবিলের মোট রেকর্ড সংখ্যা
} else {
    $total_pages = ceil($total_records / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
}

// Fetch connection data
$sql = "SELECT nc.*, e_order.full_name as order_taker_name, e_money.full_name as money_with_name
        FROM new_connections nc
        LEFT JOIN employees e_order ON nc.order_taker_id = e_order.id
        LEFT JOIN employees e_money ON nc.money_with_id = e_money.id
        $sql_where
        ORDER BY nc.connection_date DESC, nc.id DESC
        $limit_sql"; // এখানে ডায়নামিক $limit_sql ভেরিয়েবল ব্যবহার করা হয়েছে

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }

if (!$is_all) { // 'all' না হলেই শুধু LIMIT এবং OFFSET বাইন্ড করুন
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
}

$stmt->execute();
$connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>নতুন কানেকশন লগ</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#connection-modal"><i class="bi bi-plus-circle"></i> নতুন কানেকশন যোগ করুন</button>
    </div>    
    <div class="card mb-4">
        <div class="card-header"><h3><i class="bi bi-search"></i> সার্চ এবং ফিল্টার</h3></div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="connectionSearchInput" class="form-control form-control-lg" placeholder="ID (কোড) অথবা মোবাইল নম্বর দিয়ে সার্চ করুন...">
            </div>
            <form method="get" class="row g-3 border-top pt-3">
                <div class="col-md-3"><label class="form-label">শুরুর তারিখ</label><input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">শেষ তারিখ</label><input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>"></div>
                <div class="col-md-3"><label class="form-label">ধরণ</label><select name="connection_type" class="form-select"><option value="">সব</option><option value="নতুন লাইন" <?php if(($_GET['connection_type'] ?? '') == 'নতুন লাইন') echo 'selected';?>>নতুন লাইন</option><option value="ফাইবার কনভার্ট" <?php if(($_GET['connection_type'] ?? '') == 'ফাইবার কনভার্ট') echo 'selected';?>>ফাইবার কনভার্ট</option></select></div>
                <div class="col-md-3"><label class="form-label">মালামাল</label><select name="materials_used" class="form-select"><option value="">সব</option><option value="অনু" <?php if(($_GET['materials_used'] ?? '') == 'অনু') echo 'selected';?>>অনু</option><option value="ফাইবার" <?php if(($_GET['materials_used'] ?? '') == 'ফাইবার') echo 'selected';?>>ফাইবার</option><option value="অনু-রাউটার" <?php if(($_GET['materials_used'] ?? '') == 'অনু-রাউটার') echo 'selected';?>>অনু-রাউটার</option></select></div>
                <div class="col-md-3"><label class="form-label">অর্ডার গ্রহীতা</label><select name="order_taker_id" class="form-select"><option value="">সব</option><?php foreach($employees as $e) echo "<option value='{$e['id']}' ".((($_GET['order_taker_id'] ?? '') == $e['id']) ? 'selected' : '').">{$e['full_name']}</option>";?></select></div>
                <div class="col-md-3"><label class="form-label">টাকা যার কাছে</label><select name="money_with_id" class="form-select"><option value="">সব</option><?php foreach($employees as $e) echo "<option value='{$e['id']}' ".((($_GET['money_with_id'] ?? '') == $e['id']) ? 'selected' : '').">{$e['full_name']}</option>";?></select></div>
                
                <div class="col-md-3">
                    <label class="form-label">দেখাও</label>
                    <select name="show" class="form-select">
                        <option value="25" <?php if($show == 25) echo 'selected'; ?>>25</option>
                        <option value="50" <?php if($show == 50) echo 'selected'; ?>>50</option>
                        <option value="100" <?php if($show == 100) echo 'selected'; ?>>100</option>
                        <option value="all" <?php if($show == 'all') echo 'selected'; ?>>সব</option>
                    </select>
                </div>

                <div class="col-md-12"><button type="submit" class="btn btn-primary">ফিল্টার</button> <a href="new_connection.php" class="btn btn-secondary">রিসেট</a></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>নং</th>
                            <th>ID</th>
                            <th>তারিখ</th>
                            <th>নাম ও ঠিকানা</th>
                            <th>মোবাইল</th>
                            <th>ধরণ</th>
                            <th>মালামাল</th>
                            <th>মূল্য</th>
                            <th>জমা</th>
                            <th>বাকি</th>
                            <th>অর্ডার গ্রহীতা</th>
                            <th>টাকা যার কাছে</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody id="connectionTableBody">
                        <?php if(empty($connections)): ?>
                            <tr><td colspan="13" class="text-center">কোনো তথ্য পাওয়া যায়নি।</td></tr>
                        <?php else: foreach($connections as $key => $conn): ?>
                        <tr>
                            <td><?php echo $offset + $key + 1; ?></td> 
                            <td><?php echo htmlspecialchars($conn['customer_id_code']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($conn['connection_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($conn['customer_name']); ?></strong><br><small><?php echo htmlspecialchars($conn['address']); ?></small></td>
                            <td><?php echo htmlspecialchars($conn['mobile_number']); ?></td>
                            <td><?php echo htmlspecialchars($conn['connection_type']); ?></td>
                            <td><?php echo htmlspecialchars(str_replace(',', ', ', $conn['materials_used'])); ?></td>
                            <td><?php echo number_format($conn['total_price']); ?></td>
                            <td><?php echo number_format($conn['deposit_amount']); ?></td>
                            <td class="<?php echo ($conn['due_amount'] > 0) ? 'text-danger fw-bold' : ''; ?>"><?php echo number_format($conn['due_amount']); ?></td>
                            <td><?php echo htmlspecialchars($conn['order_taker_name']); ?></td>
                            <td><?php echo htmlspecialchars($conn['money_with_name']); ?></td>
                            <td>
                                <?php if($conn['due_amount'] > 0): ?>
                                    <button class="btn btn-danger btn-sm btn-due" data-id="<?php echo $conn['id']; ?>" title="বকেয়া পরিশোধ করুন">Due</button>
                                <?php endif; ?>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="<?php echo $conn['id']; ?>" title="এডিট করুন" data-bs-toggle="modal" data-bs-target="#connection-modal"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-danger btn-sm btn-delete" data-id="<?php echo $conn['id']; ?>" title="ডিলিট করুন"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if(!$is_all): ?>
            <div id="connectionPaginationContainer" class="mt-4">
                 <?php if($total_pages > 1): ?>
                <nav><ul class="pagination justify-content-center">
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
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="modal fade" id="connection-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="connection-modal-title">নতুন কানেকশন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="connection-form">
                    <input type="hidden" name="connection_id" id="connection_id">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">তারিখ</label><input type="date" name="connection_date" id="connection_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="col-md-4"><label class="form-label">ID (কোড)</label><input type="text" name="customer_id_code" id="customer_id_code" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">নাম</label><input type="text" name="customer_name" id="customer_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">মোবাইল নাম্বার</label><input type="text" name="mobile_number" id="mobile_number" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">ঠিকানা</label><input type="text" name="address" id="address" class="form-control" list="address-suggestions" required><datalist id="address-suggestions"></datalist></div>
                        <div class="col-md-6"><label class="form-label">ধরণ</label><select name="connection_type" id="connection_type" class="form-select" required><option value="নতুন লাইন">নতুন লাইন</option><option value="ফাইবার কনভার্ট">ফাইবার কনভার্ট</option></select></div>
                        <div class="col-md-6"><label class="form-label">মালামাল</label>
                            <div>
                                <input type="checkbox" class="form-check-input" name="materials[]" value="অনু" id="mat_onu"> <label for="mat_onu">অনু</label> &nbsp;
                                <input type="checkbox" class="form-check-input" name="materials[]" value="ফাইবার" id="mat_fiber"> <label for="mat_fiber">ফাইবার</label> &nbsp;
                                <input type="checkbox" class="form-check-input" name="materials[]" value="অনু-রাউটার" id="mat_router"> <label for="mat_router">অনু-রাউটার</label>
                            </div>
                        </div>

                        <!-- ONU Assignment Section -->
                        <div class="col-md-12 border p-3 rounded bg-light" id="onu_assignment_section" style="display:none;">
                            <h6>ONU বরাদ্দ বিবরণ (ঐচ্ছিক)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">ONU ব্র্যান্ড</label>
                                    <select name="onu_brand" id="onu_brand" class="form-select">
                                        <option value="">ব্র্যান্ড নির্বাচন করুন</option>
                                        <?php foreach($brands as $brand) echo "<option value='".htmlspecialchars($brand)."'>".htmlspecialchars($brand)."</option>"; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">MAC Address</label>
                                    <input type="text" name="onu_mac" id="onu_mac" class="form-control" placeholder="MAC Address">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">বরাদ্দ গ্রহীতা (Allocator)</label>
                                    <div class="border p-2 rounded bg-white" style="max-height: 100px; overflow-y: auto;">
                                        <?php foreach($employees as $e): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="onu_assigned_to[]" value="<?php echo htmlspecialchars($e['full_name']); ?>" id="onu_emp_<?php echo $e['id']; ?>">
                                            <label class="form-check-label" for="onu_emp_<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['full_name']); ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4"><label class="form-label">টাকা</label><input type="number" name="total_price" id="total_price" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">জমা</label><input type="number" name="deposit_amount" id="deposit_amount" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">বাকি</label><input type="text" id="due_amount_display" class="form-control" disabled></div>
                        <div class="col-md-6"><label class="form-label">অর্ডার গ্রহীতা</label><select name="order_taker_id" id="order_taker_id" class="form-select" required><?php foreach($employees as $e) echo "<option value='{$e['id']}'>{$e['full_name']}</option>"; ?></select></div>
                        <div class="col-md-6"><label class="form-label">টাকা কার কাছে?</label><select name="money_with_id" id="money_with_id" class="form-select" required><?php foreach($employees as $e) echo "<option value='{$e['id']}'>{$e['full_name']}</option>"; ?></select></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ</button><button type="submit" form="connection-form" class="btn btn-primary">সেভ করুন</button></div>
        </div>
    </div>
</div>
<div class="modal fade" id="due-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">বকেয়া পরিশোধ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="due-form">
                    <input type="hidden" name="connection_id" id="due_connection_id">
                    <p>মোট বকেয়া: <strong id="due_total_display"></strong> টাকা</p>
                    <div class="mb-3"><label class="form-label">জমা</label><input type="number" name="paid_amount" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">ডিসকাউন্ট</label><input type="number" name="discount_amount" class="form-control" value="0"></div>
                    <p>আগের টাকা যার কাছে ছিল: <strong id="due_money_with_name"></strong></p>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ</button><button type="submit" form="due-form" class="btn btn-primary">জমা করুন</button></div>
        </div>
    </div>
</div>
<script src="assets/connection.js"></script>
<?php
include 'footer.php';
?>