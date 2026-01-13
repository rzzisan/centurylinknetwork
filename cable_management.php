<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "ক্যাবল ম্যানেজমেন্ট";
include 'header.php';
include 'sidebar.php';

$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$drums = $pdo->query("SELECT id, drum_code, year, current_meter, metering_direction FROM fiber_drums WHERE current_meter > 0 ORDER BY drum_code ASC")->fetchAll(PDO::FETCH_ASSOC);
$drum_stock = $pdo->query(
    "SELECT * FROM fiber_drums ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-bezier"></i> ফাইবার ক্যাবল ম্যানেজমেন্ট</h2>
        <div>
             <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#log-usage-modal"><i class="bi bi-pencil-square"></i> খরচ এন্ট্রি করুন</button>
             <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-drum-modal"><i class="bi bi-plus-circle"></i> নতুন ড্রাম যোগ করুন</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h3><i class="bi bi-box-seam"></i> সকল ড্রামের স্টক (<a href="cable_usage_report.php">খরচের বিস্তারিত রিপোর্ট দেখুন</a>)</h3></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="drum-stock-table" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ড্রাম কোড</th>
                            <th>সাল</th>
                            <th>কোর</th>
                            <th>কোম্পানি</th>
                            <th>মিটারিং ধরণ</th>
                            <th>শুরুর মার্ক</th>
                            <th>মোট মিটার</th>
                            <th>বর্তমান মিটার</th>
                            <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($drum_stock)): ?>
                            <tr><td colspan="9" class="text-center">কোনো ড্রামের স্টক এন্ট্রি নেই।</td></tr>
                        <?php else: foreach($drum_stock as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['drum_code']); ?></td>
                                <td><?php echo htmlspecialchars($item['year']); ?></td>
                                <td><?php echo htmlspecialchars($item['fiber_core']); ?></td>
                                <td><?php echo htmlspecialchars($item['company']); ?></td>
                                <td>
                                    <?php if($item['metering_direction'] == 'desc'): ?>
                                        <span class="badge bg-primary">শেষ থেকে শুরু</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">শুরু থেকে শেষ</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['start_meter_mark']); ?></td>
                                <td><?php echo htmlspecialchars($item['total_meter']); ?>m</td>
                                <td class="fw-bold"><?php echo htmlspecialchars($item['current_meter']); ?>m</td>
                                <td>
                                    <a href="cable_usage_report.php?drum_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info" title="বিস্তারিত দেখুন"><i class="bi bi-eye"></i></a>
                                    <button class="btn btn-sm btn-warning btn-edit-drum" data-id="<?php echo $item['id']; ?>" title="এডিট করুন"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-sm btn-danger btn-delete-drum" data-id="<?php echo $item['id']; ?>" title="ডিলিট করুন"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add-drum-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">নতুন ড্রাম যোগ করুন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="add-drum-form">
                     <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">ড্রাম কোড</label><input type="text" class="form-control" name="drum_code" required></div>
                        <div class="col-md-6"><label class="form-label">সাল (Year)</label><input type="number" class="form-control" name="year" value="<?php echo date('Y'); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">ফাইবার কোর</label><select name="fiber_core" class="form-select" required><option value="">নির্বাচন করুন</option><option value="2F">2F</option><option value="4F">4F</option><option value="6F">6F</option><option value="12F">12F</option><option value="24F">24F</option></select></div>
                        <div class="col-md-6"><label class="form-label">কোম্পানি</label><input type="text" class="form-control" name="company" required></div>
                        <hr><div class="col-md-6"><label class="form-label">মিটারিং এর ধরণ</label><select name="metering_direction" class="form-select" required><option value="desc">শেষ থেকে শুরু (e.g., 500 -> 1)</option><option value="asc">শুরু থেকে শেষ (e.g., 1 -> 500)</option></select></div>
                        <div class="col-md-6"><label class="form-label">মোট মিটার</label><input type="number" class="form-control" name="total_meter" min="1" required></div>
                        <div class="col-md-6"><label class="form-label">শুরুর মার্ক ( বাইরের মাথায় যা লেখা)</label><input type="number" class="form-control" name="start_meter_mark" required></div>
                        <div class="col-md-6"><label class="form-label">শেষের মার্ক (স্বয়ংক্রিয় হিসাব)</label><input type="text" id="end_meter_mark_display" class="form-control" disabled></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button><button type="submit" form="add-drum-form" class="btn btn-primary">সেভ করুন</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit-drum-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">ড্রামের তথ্য এডিট করুন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="edit-drum-form">
                    <input type="hidden" name="id">
                     <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">ড্রাম কোড</label><input type="text" class="form-control" name="drum_code" required></div>
                        <div class="col-md-6"><label class="form-label">সাল (Year)</label><input type="number" class="form-control" name="year" required></div>
                        <div class="col-md-6"><label class="form-label">ফাইবার কোর</label><select name="fiber_core" class="form-select" required><option value="">নির্বাচন করুন</option><option value="2F">2F</option><option value="4F">4F</option><option value="6F">6F</option><option value="12F">12F</option><option value="24F">24F</option></select></div>
                        <div class="col-md-6"><label class="form-label">কোম্পানি</label><input type="text" class="form-control" name="company" required></div>
                        <hr><div class="col-md-6"><label class="form-label">মিটারিং এর ধরণ</label><select name="metering_direction" class="form-select" required><option value="desc">শেষ থেকে শুরু (e.g., 500 -> 1)</option><option value="asc">শুরু থেকে শেষ (e.g., 1 -> 500)</option></select></div>
                        <div class="col-md-6"><label class="form-label">মোট মিটার</label><input type="number" class="form-control" name="total_meter" min="1" required></div>
                        <div class="col-md-6"><label class="form-label">শুরুর মার্ক</label><input type="number" class="form-control" name="start_meter_mark" required></div>
                        <div class="col-md-6"><label class="form-label">শেষের মার্ক (স্বয়ংক্রিয় হিসাব)</label><input type="text" id="edit_end_meter_mark_display" class="form-control" disabled></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button><button type="submit" form="edit-drum-form" class="btn btn-primary">আপডেট করুন</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="log-usage-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">ফাইবার খরচ এন্ট্রি করুন</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="log-usage-form">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">ড্রাম (কোড)</label><select id="drum_id" name="drum_id" class="form-select" required><option value="">ড্রাম নির্বাচন করুন</option><?php foreach($drums as $drum): ?><option value="<?php echo $drum['id']; ?>" data-meter="<?php echo $drum['current_meter']; ?>" data-direction="<?php echo $drum['metering_direction']; ?>"><?php echo htmlspecialchars($drum['drum_code'] . ' (' . $drum['year'] . ') - ' . $drum['current_meter'] . 'm'); ?></option><?php endforeach; ?></select></div>
                         <div class="col-md-4"><label class="form-label">গ্রহীতা</label><select name="employee_id" class="form-select" required><option value="">কর্মচারী নির্বাচন করুন</option><?php foreach($employees as $employee): ?><option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['full_name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label">তারিখ</label><input type="date" class="form-control" name="usage_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                    </div>
                    <hr class="my-3"><h5>খরচের বিবরণ</h5>
                    <div class="row g-3 mb-2 fw-bold"><div class="col-md-4">কাস্টমার আইডি</div><div class="col-md-3">শুরুর মার্ক</div><div class="col-md-3">শেষের মার্ক</div><div class="col-md-1">খরচ</div><div class="col-md-1"></div></div>
                    <div id="usage-details-container"></div>
                    <button type="button" id="add-detail-btn" class="btn btn-sm btn-success mt-2"><i class="bi bi-plus"></i> আরও কাস্টমার যোগ করুন</button>
                    <hr class="my-3">
                    <div class="row justify-content-end">
                        <div class="col-md-4"><label class="form-label">ড্রামে অবশিষ্ট আছে</label><input type="text" id="current_meter_display" class="form-control" disabled></div>
                        <div class="col-md-4"><label class="form-label">আজ মোট খরচ হয়েছে</label><input type="text" id="total_meter_used_display" class="form-control" disabled><input type="hidden" name="total_meter_used" id="total_meter_used_hidden"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button><button type="submit" form="log-usage-form" class="btn btn-primary">খরচ সেভ করুন</button></div>
        </div>
    </div>
</div>

<script src="assets/cable.js"></script>
<?php
include 'footer.php';
?>