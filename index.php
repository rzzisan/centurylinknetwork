<?php
require_once 'config.php';
redirect_if_not_logged_in();

$page_title = "ড্যাশবোর্ড";
include 'header.php';
include 'sidebar.php';

// Calculate Current ONU Stock
$stock_query = "
    SELECT
        b.name as brand_name,
        (SELECT IFNULL(SUM(se.quantity), 0) FROM stock_entries se WHERE se.brand_id = b.id) -
        (SELECT COUNT(*) FROM onu_assignments oa WHERE oa.brand_name = b.name)
        AS current_stock
    FROM brands b
";
$stock_summary = $pdo->query($stock_query)->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total Fiber Cable Stock
$fiber_stock_query = "SELECT SUM(current_meter) as total_fiber FROM fiber_drums";
$total_fiber_stock = $pdo->query($fiber_stock_query)->fetchColumn();

// Other Products Stock
$other_products_stock = $pdo->query("SELECT name, current_stock FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-graph-up"></i> Present Stock at a glanc </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-3 col-md-4 mb-3">
                     <div class="card text-center bg-info text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Fiber Cable</h5>
                            <p class="card-text fs-2 fw-bold"><?php echo htmlspecialchars(number_format($total_fiber_stock ?? 0)); ?> m</p>
                        </div>
                    </div>
                </div>
                
                <?php if(empty($stock_summary)): ?><p>স্টকে কোনো ONU নেই।</p>
                <?php else: foreach($stock_summary as $stock): ?>
                    <div class="col-lg-3 col-md-4 mb-3">
                         <div class="card text-center h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($stock['brand_name']); ?></h5>
                                <p class="card-text fs-2 fw-bold"><?php echo htmlspecialchars($stock['current_stock']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>

                <?php foreach($other_products_stock as $product): ?>
                    <div class="col-lg-3 col-md-4 mb-3">
                         <div class="card text-center bg-light h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text fs-2 fw-bold"><?php echo htmlspecialchars($product['current_stock']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>