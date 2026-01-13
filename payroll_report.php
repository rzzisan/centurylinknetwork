<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "রিপোর্ট ও বেতন শিট";
include 'header.php';
include 'sidebar.php';

// Fetch all employees for the filter dropdown
$employees = $pdo->query("SELECT id, full_name FROM employees WHERE include_in_attendance = 1 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container-fluid">
    <h2><i class="bi bi-file-earmark-bar-graph-fill"></i> রিপোর্ট ও বেতন শিট</h2>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5>ফিল্টার করুন</h5>
        </div>
        <div class="card-body">
            <form id="payroll-report-form" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="month" class="form-label">মাস ও বছর</label>
                    <input type="month" class="form-control" id="month" name="month" value="<?php echo date('Y-m'); ?>" required>
                </div>
                <div class="col-md-5">
                    <label for="employee_id" class="form-label">কর্মচারী</label>
                    <select class="form-select" id="employee_id" name="employee_id" required>
                        <option value="">কর্মচারী নির্বাচন করুন</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">রিপোর্ট দেখুন</button>
                </div>
            </form>
        </div>
    </div>

    <div id="report-result-container">
        <div class="alert alert-info text-center">অনুগ্রহ করে মাস এবং কর্মচারী নির্বাচন করে "রিপোর্ট দেখুন" বাটনে ক্লিক করুন।</div>
    </div>
</div>

<div class="modal fade" id="edit-attendance-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">হাজিরা পরিবর্তন করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-attendance-form">
                    <input type="hidden" id="edit_attendance_id" name="attendance_id">
                    <p><strong>কর্মচারী:</strong> <span id="modal_employee_name"></span></p>
                    <p><strong>তারিখ:</strong> <span id="modal_attendance_date"></span></p>
                    <div class="form-group">
                        <label for="edit_status" class="form-label">নতুন স্ট্যাটাস</label>
                        <select id="edit_status" name="status" class="form-select">
                            <option value="present">উপস্থিত</option>
                            <option value="absent">অনুপস্থিত</option>
                            <option value="half_day">হাফ ডিউটি</option>
                            <option value="leave">ছুটি</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="submit" form="edit-attendance-form" class="btn btn-primary">সেভ করুন</button>
            </div>
        </div>
    </div>
</div>

<style>
    .payslip { border: 1px solid #ddd; padding: 20px; border-radius: 5px; background-color: #f9f9f9; }
    .payslip h4 { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
    .table-sm td, .table-sm th { padding: 0.4rem; }
</style>
<script src="assets/payroll_report.js"></script>

<?php
include 'footer.php';
?>