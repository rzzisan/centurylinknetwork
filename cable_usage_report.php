<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "ফাইবার খরচের বিবরণ";
include 'header.php';
include 'sidebar.php';

// Common data fetches
$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$selected_drum_id = filter_input(INPUT_GET, 'drum_id', FILTER_VALIDATE_INT);
$drum_details = null;
$usage_data = [];

if ($selected_drum_id) {
    // Fetch details of the selected drum
    $stmt = $pdo->prepare("SELECT * FROM fiber_drums WHERE id = ?");
    $stmt->execute([$selected_drum_id]);
    $drum_details = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch all usage logs and their details for the selected drum
    $sql = "SELECT
                cul.id as log_id, cul.usage_date, cul.meter_at_start, cul.meter_at_end, cul.meter_used as total_used,
                e.full_name as employee_name, e.id as employee_id,
                cud.id as detail_id, cud.customer_id, cud.meter_used as detail_used
            FROM cable_usage_logs cul
            JOIN employees e ON cul.employee_id = e.id
            LEFT JOIN cable_usage_details cud ON cul.id = cud.usage_log_id
            WHERE cul.drum_id = ?
            ORDER BY cul.usage_date DESC, cul.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_drum_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group the results by log_id for easy display
    foreach ($results as $row) {
        $log_id = $row['log_id'];
        if (!isset($usage_data[$log_id])) {
            $usage_data[$log_id] = [
                'usage_date' => $row['usage_date'],
                'employee_name' => $row['employee_name'],
                'employee_id' => $row['employee_id'],
                'meter_at_start' => $row['meter_at_start'],
                'meter_at_end' => $row['meter_at_end'],
                'total_used' => $row['total_used'],
                'details' => []
            ];
        }
        if ($row['customer_id']) {
            $usage_data[$log_id]['details'][] = [
                'detail_id' => $row['detail_id'],
                'customer_id' => $row['customer_id'],
                'meter_used' => $row['detail_used']
            ];
        }
    }
} else {
    // If no drum is selected, fetch all drums to show a list
    $all_drums = $pdo->query("SELECT id, drum_code, year, company, total_meter, current_meter FROM fiber_drums ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="container-fluid">
    <?php if ($selected_drum_id && $drum_details): ?>
        <a href="cable_management.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> সকল ড্রামের তালিকায় ফিরে যান</a>
        <div class="card">
            <div class="card-header">
                <h3>ড্রাম কোড: <?php echo htmlspecialchars($drum_details['drum_code']); ?> (<?php echo htmlspecialchars($drum_details['year']); ?>) - এর খরচের বিবরণ</h3>
                <p class="mb-0"><strong>কোম্পানি:</strong> <?php echo htmlspecialchars($drum_details['company']); ?> | <strong>কোর:</strong> <?php echo htmlspecialchars($drum_details['fiber_core']); ?></p>
            </div>
            <div class="card-body">
                <?php if (empty($usage_data)): ?>
                    <div class="alert alert-info">এই ড্রামের জন্য কোনো খরচের হিসাব পাওয়া যায়নি।</div>
                <?php else: ?>
                    <div class="accordion" id="usageAccordion">
                        <?php foreach ($usage_data as $log_id => $log): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $log_id; ?>">
                                        <strong><?php echo date('d-m-Y', strtotime($log['usage_date'])); ?></strong> &nbsp;-&nbsp; 
                                        গ্রহীতা: <?php echo htmlspecialchars($log['employee_name']); ?> &nbsp;-&nbsp; 
                                        মোট খরচ: <span class="badge bg-danger ms-1"><?php echo $log['total_used']; ?> মিটার</span>
                                    </button>
                                </h2>
                                <div id="collapse-<?php echo $log_id; ?>" class="accordion-collapse collapse" data-bs-parent="#usageAccordion">
                                    <div class="accordion-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <p class="mb-0">
                                                কাজের শুরুতে ছিল: <strong><?php echo $log['meter_at_start']; ?> মিটার</strong> | 
                                                কাজের শেষে ছিল: <strong><?php echo $log['meter_at_end']; ?> মিটার</strong>
                                            </p>
                                            <div>
                                                <button class="btn btn-sm btn-warning btn-edit-log" data-log-id="<?php echo $log_id; ?>"><i class="bi bi-pencil-square"></i> এডিট</button>
                                                <button class="btn btn-sm btn-danger btn-delete-log" data-log-id="<?php echo $log_id; ?>"><i class="bi bi-trash"></i> ডিলিট</button>
                                            </div>
                                        </div>
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr><th>কাস্টমার আইডি</th><th>খরচ (মিটার)</th></tr>
                                            </thead>
                                            <tbody>
                                            <?php if(empty($log['details'])): ?>
                                                <tr><td colspan="2" class="text-center">কোনো বিবরণ যোগ করা হয়নি।</td></tr>
                                            <?php else: foreach ($log['details'] as $detail): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($detail['customer_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($detail['meter_used']); ?></td>
                                                </tr>
                                            <?php endforeach; endif;?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-box-seam"></i> ড্রাম নির্বাচন করুন</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr><th>ড্রাম কোড</th><th>কোম্পানি</th><th>মোট মিটার</th><th>বর্তমান মিটার</th><th>অ্যাকশন</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_drums as $drum): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($drum['drum_code']); ?> (<?php echo htmlspecialchars($drum['year']); ?>)</td>
                                    <td><?php echo htmlspecialchars($drum['company']); ?></td>
                                    <td><?php echo htmlspecialchars($drum['total_meter']); ?></td>
                                    <td><?php echo htmlspecialchars($drum['current_meter']); ?></td>
                                    <td>
                                        <a href="?drum_id=<?php echo $drum['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye"></i> বিস্তারিত দেখুন
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="edit-log-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">খরচের হিসাব এডিট করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-log-form">
                    <input type="hidden" id="edit_log_id" name="log_id">
                    <input type="hidden" id="edit_meter_at_start" name="meter_at_start">
                    
                    <div class="row g-3">
                        <div class="col-md-6"><label for="edit_usage_date" class="form-label">তারিখ</label><input type="date" class="form-control" id="edit_usage_date" name="usage_date" required></div>
                         <div class="col-md-6">
                            <label for="edit_employee_id" class="form-label">গ্রহীতা</label>
                            <select id="edit_employee_id" name="employee_id" class="form-select" required>
                                <?php foreach($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5>খরচের বিবরণ</h5>
                    <div id="edit-details-container">
                        </div>
                    <button type="button" id="edit-add-detail-btn" class="btn btn-sm btn-success mt-2"><i class="bi bi-plus"></i> আরও বিবরণ যোগ করুন</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="submit" form="edit-log-form" class="btn btn-primary">আপডেট করুন</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/report.js"></script>
<?php
include 'footer.php';
?>