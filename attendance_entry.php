<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "দৈনিক হাজিরা";
include 'header.php';
include 'sidebar.php';

// Date selection logic: Default to today, but allow any past date from GET parameter
$selected_date = $_GET['date'] ?? date('Y-m-d');
$today = date('Y-m-d');

// Fetch employees included in attendance
$employees_stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE include_in_attendance = 1 ORDER BY full_name ASC");
$employees_stmt->execute();
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing attendance for the selected date to pre-fill the form
$existing_attendance = [];
if (!empty($employees)) {
    $stmt_check = $pdo->prepare("SELECT employee_id, status FROM attendance WHERE attendance_date = ?");
    $stmt_check->execute([$selected_date]);
    $records = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
    foreach ($records as $record) {
        $existing_attendance[$record['employee_id']] = $record['status'];
    }
}
$is_record_exists = !empty($existing_attendance);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-calendar-plus-fill"></i> দৈনিক হাজিরা ম্যানেজমেন্ট</h2>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="attendance_date_picker" class="form-label"><b>তারিখ নির্বাচন করুন</b></label>
                    <input type="date" id="attendance_date_picker" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" max="<?php echo $today; ?>">
                </div>
                <div class="col-md-8">
                    <?php if ($is_record_exists): ?>
                        <div class="alert alert-info mb-0 py-2">
                            <i class="bi bi-info-circle-fill"></i> এই তারিখের হাজিরা পূর্বে জমা দেওয়া হয়েছে। আপনি প্রয়োজনে তথ্য পরিবর্তন করতে পারেন।
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light mb-0 py-2">
                           <i class="bi bi-pencil-square"></i> এই তারিখের জন্য হাজিরা দিন।
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($employees)): ?>
        <div class="alert alert-warning text-center">
            <h4><i class="bi bi-exclamation-triangle-fill"></i> কোনো কর্মচারী পাওয়া যায়নি।</h4>
            <p>অনুগ্রহ করে প্রথমে <a href="employee_settings.php">কর্মচারী ম্যানেজমেন্ট</a> পাতা থেকে কর্মচারীদের "হাজিরায় অন্তর্ভুক্ত করুন"।</p>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header">
                <h4><?php echo date('d F, Y', strtotime($selected_date)); ?> তারিখের হাজিরা ফরম</h4>
            </div>
            <div class="card-body">
                <form id="attendance-form">
                    <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>কর্মচারীর নাম</th>
                                    <th class="text-center" style="min-width: 350px;">স্ট্যাটাস</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp):
                                    $current_status = $existing_attendance[$emp['id']] ?? 'present';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                    <td>
                                        <input type="hidden" name="employee_ids[]" value="<?php echo $emp['id']; ?>">
                                        <div class="d-flex justify-content-around flex-wrap">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="status[<?php echo $emp['id']; ?>]" id="present-<?php echo $emp['id']; ?>" value="present" <?php if($current_status == 'present') echo 'checked'; ?>>
                                                <label class="form-check-label" for="present-<?php echo $emp['id']; ?>">উপস্থিত</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="status[<?php echo $emp['id']; ?>]" id="absent-<?php echo $emp['id']; ?>" value="absent" <?php if($current_status == 'absent') echo 'checked'; ?>>
                                                <label class="form-check-label" for="absent-<?php echo $emp['id']; ?>">অনুপস্থিত</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="status[<?php echo $emp['id']; ?>]" id="leave-<?php echo $emp['id']; ?>" value="leave" <?php if($current_status == 'leave') echo 'checked'; ?>>
                                                <label class="form-check-label" for="leave-<?php echo $emp['id']; ?>">ছুটি</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="status[<?php echo $emp['id']; ?>]" id="half_day-<?php echo $emp['id']; ?>" value="half_day" <?php if($current_status == 'half_day') echo 'checked'; ?>>
                                                <label class="form-check-label" for="half_day-<?php echo $emp['id']; ?>">হাফ ডিউটি</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                            <?php echo $is_record_exists ? '<i class="bi bi-arrow-repeat"></i> আপডেট করুন' : '<i class="bi bi-check2-circle"></i> হাজিরা জমা দিন'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h4><i class="bi bi-calendar-month"></i> <?php echo date('F, Y', strtotime($selected_date)); ?> মাসের অ্যাটেনডেন্স সারাংশ</h4>
            </div>
            <div class="card-body">
                <div id="monthly-summary-container" class="table-responsive">
                    <p class="text-center p-4">লোড হচ্ছে...</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const api_url = 'api/attendance_api.php';
    const attendanceDatePicker = document.getElementById('attendance_date_picker');
    const attendanceForm = document.getElementById('attendance-form');
    const monthlySummaryContainer = document.getElementById('monthly-summary-container');

    if (attendanceDatePicker) {
        // 1. Handle date change to reload the page with the new date
        attendanceDatePicker.addEventListener('change', function() {
            const selectedDate = this.value;
            window.location.href = `attendance_entry.php?date=${selectedDate}`;
        });

        // 2. Handle form submission for saving/updating attendance
        if (attendanceForm) {
            attendanceForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('submit-btn');
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> সেভ হচ্ছে...';

                const formData = new FormData(this);
                formData.append('action', 'save_daily_attendance');

                fetch(api_url, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            loadMonthlySummary(); // Refresh summary table
                            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> আপডেট করুন';
                        } else {
                           submitBtn.innerHTML = originalText;
                        }
                        submitBtn.disabled = false;
                    });
            });
        }

        // 3. Load monthly summary table via AJAX
        function loadMonthlySummary() {
            if (!monthlySummaryContainer) return;
            
            const selectedDate = new Date(attendanceDatePicker.value);
            const year = selectedDate.getFullYear();
            const month = selectedDate.getMonth() + 1;

            fetch(`${api_url}?action=get_monthly_summary&year=${year}&month=${month}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderSummaryTable(data.data);
                    } else {
                        monthlySummaryContainer.innerHTML = `<p class="text-danger text-center">${data.message}</p>`;
                    }
                });
        }

        function renderSummaryTable(data) {
            const { employees, attendance_data, days_in_month } = data;
            
            let tableHTML = `<table class="table table-bordered table-sm text-center" style="font-size: 0.8rem;">`;
            
            // Table Header
            tableHTML += `<thead class="table-light"><tr><th style="min-width: 150px; vertical-align: middle;">কর্মচারী</th>`;
            for (let day = 1; day <= days_in_month; day++) {
                tableHTML += `<th>${day}</th>`;
            }
            tableHTML += `</tr></thead>`;

            // Table Body
            tableHTML += `<tbody>`;
            employees.forEach(emp => {
                tableHTML += `<tr><td class="text-start fw-bold">${emp.full_name}</td>`;
                for (let day = 1; day <= days_in_month; day++) {
                    const status = attendance_data[emp.id] && attendance_data[emp.id][day] ? attendance_data[emp.id][day] : '';
                    let symbol = '';
                    let cssClass = '';
                    let title = '';

                    switch (status) {
                        case 'present':  symbol = 'P'; cssClass = 'bg-success text-white'; title = 'Present'; break;
                        case 'absent':   symbol = 'A'; cssClass = 'bg-danger text-white'; title = 'Absent'; break;
                        case 'leave':    symbol = 'L'; cssClass = 'bg-info text-white'; title = 'Leave'; break;
                        case 'half_day': symbol = 'H'; cssClass = 'bg-warning text-dark'; title = 'Half-day'; break;
                        default:         symbol = '-'; cssClass = 'text-muted'; title = 'No Data';
                    }
                    tableHTML += `<td class="${cssClass}" title="${title}">${symbol}</td>`;
                }
                tableHTML += `</tr>`;
            });
            tableHTML += `</tbody></table>`;
            
            monthlySummaryContainer.innerHTML = tableHTML;
        }

        // Initial load of the summary table on page load
        loadMonthlySummary();
    }
});
</script>

<?php
include 'footer.php';
?>