<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "সকল গ্রাহকের তালিকা";
include 'header.php';
include 'sidebar.php';

// Pagination Logic
$items_per_page = 50;
$current_page = $_GET['page'] ?? 1;
$offset = ($current_page - 1) * $items_per_page;
$total_records_stmt = $pdo->query("SELECT COUNT(*) FROM clients");
$total_records = $total_records_stmt->fetchColumn();
$total_pages = ceil($total_records / $items_per_page);

// Fetch clients for the current page
$stmt = $pdo->prepare("SELECT * FROM clients ORDER BY CustomerHeaderId DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header"><h3><i class="bi bi-people-fill"></i> সকল গ্রাহকের তালিকা (মোট: <?php echo $total_records; ?>)</h3></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>নাম</th>
                            <th>ইউজারনেম</th>
                            <th>মোবাইল</th>
                            <th>জোন</th>
                            <th>প্যাকেজ</th>
                            <th>মাসিক বিল</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr><td colspan="7" class="text-center">কোনো গ্রাহক পাওয়া যায়নি।</td></tr>
                        <?php else: foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['CustomerId']); ?></td>
                                <td><?php echo htmlspecialchars($client['CustomerName']); ?></td>
                                <td><?php echo htmlspecialchars($client['UserName']); ?></td>
                                <td><?php echo htmlspecialchars($client['MobileNumber']); ?></td>
                                <td><?php echo htmlspecialchars($client['ZoneName']); ?></td>
                                <td><?php echo htmlspecialchars($client['Package']); ?></td>
                                <td><?php echo htmlspecialchars($client['MonthlyBill']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
             <?php if($total_pages > 1): ?>
                <nav><ul class="pagination justify-content-center mt-4">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if($i == $current_page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>