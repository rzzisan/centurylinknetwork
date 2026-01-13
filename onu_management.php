<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "ONU বরাদ্দ ম্যানেজমেন্ট";
include 'header.php';
include 'sidebar.php';

// Fetch lists for filters
$employees_list = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$brands_list = $pdo->query("SELECT name FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// Pagination and Filter Logic
$items_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';
$filter_assigned_to = $_GET['assigned_to'] ?? '';
$filter_brand = $_GET['brand'] ?? '';
$filter_created_by = $_GET['created_by'] ?? '';
$filter_purpose = $_GET['purpose'] ?? '';

$sql_base = "FROM onu_assignments oa LEFT JOIN employees e_creator ON oa.created_by = e_creator.id WHERE 1=1";
$params = [];

// Build query and parameters
if ($filter_start_date) { $sql_base .= " AND oa.assignment_date >= :start_date"; $params[':start_date'] = $filter_start_date . ' 00:00:00'; }
if ($filter_end_date) { $sql_base .= " AND oa.assignment_date <= :end_date"; $params[':end_date'] = $filter_end_date . ' 23:59:59'; }
if ($filter_assigned_to) { $sql_base .= " AND FIND_IN_SET(:assigned_to, oa.assigned_to)"; $params[':assigned_to'] = $filter_assigned_to; }
if ($filter_brand) { $sql_base .= " AND oa.brand_name = :brand"; $params[':brand'] = $filter_brand; }
if ($filter_created_by) { $sql_base .= " AND oa.created_by = :created_by"; $params[':created_by'] = $filter_created_by; }
if ($filter_purpose) { $sql_base .= " AND oa.purpose = :purpose"; $params[':purpose'] = $filter_purpose; }

// Get total records for pagination
$count_sql = "SELECT COUNT(*) " . $sql_base;
$total_records_stmt = $pdo->prepare($count_sql);
$total_records_stmt->execute($params);
$total_records = $total_records_stmt->fetchColumn();
$total_pages = !empty($items_per_page) ? ceil($total_records / $items_per_page) : 1;

// Get records for the current page
$sql = "SELECT oa.*, e_creator.full_name as creator_name " . $sql_base . " ORDER BY oa.assignment_date DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header"><h3><i class="bi bi-search"></i> সার্চ এবং ফিল্টার</h3></div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="কাস্টমার আইডি অথবা MAC Address দিয়ে সার্চ করুন...">
            </div>
             <form action="onu_management.php" method="get" class="row g-3 border-top pt-3">
                <div class="col-md-4"><label class="form-label">শুরুর তারিখ</label><input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>"></div>
                <div class="col-md-4"><label class="form-label">শেষ তারিখ</label><input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>"></div>
                <div class="col-md-4"><label class="form-label">বরাদ্দ গ্রহীতা</label><select name="assigned_to" class="form-select"><option value="">সকল কর্মচারী</option><?php foreach ($employees_list as $emp): ?><option value="<?php echo htmlspecialchars($emp['full_name']); ?>" <?php echo ($filter_assigned_to == $emp['full_name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['full_name']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">ব্র্যান্ড</label><select name="brand" class="form-select"><option value="">সকল ব্র্যান্ড</option><?php foreach($brands_list as $brand): ?><option value="<?php echo htmlspecialchars($brand); ?>" <?php echo ($filter_brand == $brand) ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">এন্ট্রি করেছেন</label><select name="created_by" class="form-select"><option value="">সকলে</option><?php foreach ($employees_list as $emp): ?><option value="<?php echo $emp['id']; ?>" <?php echo ($filter_created_by == $emp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['full_name']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4">
                    <label class="form-label">কারণ</label>
                    <select name="purpose" class="form-select">
                        <option value="">সকল কারণ</option>
                        <?php
                        $purpose_options = ["New Connection", "Warranty", "Convert to ONU", "বিক্রয় করা হয়েছে", "পয়েন্টে লাগানো হয়েছে"];
                        foreach ($purpose_options as $option) {
                            $selected = ($filter_purpose == $option) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($option) . "\" $selected>" . htmlspecialchars($option) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-12"><button type="submit" class="btn btn-primary">ফিল্টার</button> <a href="onu_management.php" class="btn btn-secondary">রিসেট</a></div>
            </form>
        </div>
    </div>


    <div class="d-flex justify-content-between align-items-center mb-3">
        <form action="onu_management.php" method="get" class="d-flex align-items-center">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            <input type="hidden" name="assigned_to" value="<?php echo htmlspecialchars($filter_assigned_to); ?>">
            <input type="hidden" name="brand" value="<?php echo htmlspecialchars($filter_brand); ?>">
            <input type="hidden" name="created_by" value="<?php echo htmlspecialchars($filter_created_by); ?>">
            <input type="hidden" name="purpose" value="<?php echo htmlspecialchars($filter_purpose); ?>">
            <label for="per_page" class="me-2">Show:</label>
            <select name="per_page" id="per_page" class="form-select w-auto" onchange="this.form.submit()">
                <option value="25" <?php echo ($items_per_page == 25) ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo ($items_per_page == 50) ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo ($items_per_page == 100) ? 'selected' : ''; ?>>100</option>
            </select>
        </form>
        <button id="add-new-btn" class="btn btn-primary"><i class="bi bi-plus-circle"></i> নতুন ONU বরাদ্দ করুন</button>
    </div>

    <div class="card">
        <div class="card-header"><h4>ONU Assignment Log</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr><th>নং</th><th>তারিখ ও সময়</th><th>কাস্টমার আইডি</th><th>ব্র্যান্ড</th><th>MAC Address</th><th>কারণ</th><th>বরাদ্দ গ্রহীতা</th><th>এন্ট্রি করেছেন</th>
                        <th>অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody id="onuTableBody">
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="9" class="text-center">কোনো তথ্য পাওয়া যায়নি।</td></tr>
                        <?php else: foreach ($assignments as $index => $item): ?>
                            <tr data-id="<?php echo $item['id']; ?>">
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y, h:i A', strtotime($item['assignment_date']))); ?></td>
                                <td><?php echo htmlspecialchars($item['customer_id']); ?></td>
                                <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['mac_address']); ?></td>
                                <td><?php echo htmlspecialchars($item['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($item['assigned_to']); ?></td>
                                <td><?php echo htmlspecialchars($item['creator_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-edit" data-id="<?php echo $item['id']; ?>"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $item['id']; ?>"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="pagination-container">
                 <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <?php
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $query_string = http_build_query($query_params);
                        
                        if ($current_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $current_page - 1; ?>&<?php echo $query_string; ?>">Previous</a></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $query_string; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $current_page + 1; ?>&<?php echo $query_string; ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="form-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">নতুন ONU বরাদ্দ করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="onu-form">
                    <input type="hidden" id="record_id" name="record_id" value="">
                    <div class="row g-3">
                        <div class="col-md-6"><label for="assignment_date" class="form-label">তারিখ এবং সময়</label><input type="datetime-local" class="form-control" id="assignment_date" name="assignment_date" required></div>
                        <div class="col-md-6"><label for="customer_id" class="form-label">কাস্টমার আইডি</label><input type="text" class="form-control" id="customer_id" name="customer_id" pattern="\d{4}" title="শুধুমাত্র ৪ সংখ্যার নম্বর দিন" required></div>
                        <div class="col-md-6"><label for="brand_name" class="form-label">ব্র্যান্ড (স্টক থেকে)</label><select class="form-select" id="brand_name" name="brand_name" required><option value="">ব্র্যান্ড নির্বাচন করুন</option></select></div>
                        <div class="col-md-6"><label for="mac_address" class="form-label">ONU MAC Address</label><input type="text" class="form-control" id="mac_address" name="mac_address" required></div>
                        <div class="col-md-12"><label for="purpose" class="form-label">কারণ</label><select class="form-select" id="purpose" name="purpose" required><option value="">নির্বাচন করুন</option><option value="New Connection">New Connection</option><option value="Warranty">Warranty</option><option value="Convert to ONU">Convert to ONU</option><option value="বিক্রয় করা হয়েছে">বিক্রয় করা হয়েছে</option><option value="পয়েন্টে লাগানো হয়েছে">পয়েন্টে লাগানো হয়েছে</option></select></div>
                        <div class="col-md-12">
                            <label class="form-label">বরাদ্দ গ্রহীতা</label>
                            <div class="border p-2 rounded" style="max-height: 150px; overflow-y: auto;">
                                <?php $all_employees = $pdo->query("SELECT full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_COLUMN); ?>
                                <?php foreach ($all_employees as $employee): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="assigned_to[]" value="<?php echo htmlspecialchars($employee); ?>" id="emp_<?php echo htmlspecialchars($employee); ?>">
                                    <label class="form-check-label" for="emp_<?php echo htmlspecialchars($employee); ?>"><?php echo htmlspecialchars($employee); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                <button type="submit" form="onu-form" class="btn btn-primary">সেভ করুন</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('onuTableBody');
    const paginationContainer = document.getElementById('pagination-container');
    const originalTableContent = tableBody.innerHTML;
    const originalPaginationContent = paginationContainer.innerHTML;
    let typingTimer;
    const doneTypingInterval = 300; // 300ms

    // Function to perform search
    function performSearch() {
        const query = searchInput.value.trim();

        if (query.length > 2) {
            paginationContainer.style.display = 'none'; // Hide pagination during search
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center">সার্চ করা হচ্ছে...</td></tr>';

            fetch(`api.php?action=search_onu_assignments&query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    tableBody.innerHTML = '';
                    if (data.success && data.data.length > 0) {
                        data.data.forEach((item, index) => {
                            const date = new Date(item.assignment_date);
                            const formattedDate = `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getFullYear()}, ${date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })}`;
                            
                            const row = `<tr>
                                <td>${index + 1}</td>
                                <td>${formattedDate}</td>
                                <td>${item.customer_id}</td>
                                <td>${item.brand_name}</td>
                                <td>${item.mac_address}</td>
                                <td>${item.purpose}</td>
                                <td>${item.assigned_to}</td>
                                <td>${item.creator_name || 'N/A'}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-edit" data-id="${item.id}"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-sm btn-danger btn-delete" data-id="${item.id}"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>`;
                            tableBody.innerHTML += row;
                        });
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="9" class="text-center">কোনো ফলাফল পাওয়া যায়নি।</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.innerHTML = '<tr><td colspan="9" class="text-center">সার্চ করতে সমস্যা হয়েছে।</td></tr>';
                });

        } else if (query.length === 0) {
            // Restore original content if search box is cleared
            tableBody.innerHTML = originalTableContent;
            paginationContainer.innerHTML = originalPaginationContent;
            paginationContainer.style.display = 'block';
        }
    }

    // Event listener for search input
    searchInput.addEventListener('keyup', () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(performSearch, doneTypingInterval);
    });

     searchInput.addEventListener('keydown', () => {
        clearTimeout(typingTimer);
    });
});
</script>

<?php
include 'footer.php';
?>