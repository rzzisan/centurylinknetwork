<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "ওভারটাইম এন্ট্রি";
include 'header.php';
include 'sidebar.php';

// Fetch all employees included in attendance for dropdowns
$employees = $pdo->query("SELECT id, full_name FROM employees WHERE include_in_attendance = 1 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$today = date('Y-m-d');
?>
<div class="container-fluid">
    <h2><i class="bi bi-person-plus-fill"></i> ওভারটাইম এন্ট্রি</h2>
    <p class="text-muted">কোনো কর্মচারী অনুপস্থিত থাকলে, তার পরিবর্তে অন্য কর্মচারী ওভারটাইম করলে এখান থেকে এন্ট্রি করুন।</p>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form id="overtime-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="overtime_date" class="form-label"><b>তারিখ</b></label>
                        <input type="date" class="form-control" id="overtime_date" name="overtime_date" value="<?php echo $today; ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="absent_employee_id" class="form-label"><b>মূল কর্মচারী (যার ডিউটি)</b></label>
                        <select class="form-select" id="absent_employee_id" name="absent_employee_id" required>
                            <option value="" selected>কর্মচারী নির্বাচন করুন</option>
                            <?php foreach($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="overtime_employee_id" class="form-label"><b>যে ওভারটাইম করছে</b></label>
                        <select class="form-select" id="overtime_employee_id" name="overtime_employee_id" required>
                            <option value="">কর্মচারী নির্বাচন করুন</option>
                            <?php foreach($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">ওভারটাইম সেভ করুন</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-5">
        <h4><i class="bi bi-clock-half"></i> হাফ ডিউটি সংক্রান্ত নোট</h4>
        <p>কোনো কর্মচারীর **হাফ ডিউটি** এন্ট্রি করার জন্য, অনুগ্রহ করে <a href="payroll_report.php">রিপোর্ট পেজ</a> থেকে নির্দিষ্ট দিনের হাজিরা এডিট করে স্ট্যাটাস "হাফ ডিউটি" নির্বাচন করুন। নতুন করে হাফ ডিউটি এন্ট্রি করার প্রয়োজন নেই।</p>
    </div>

</div>
<script src="assets/attendance.js"></script>

<?php
include 'footer.php';
?>