<?php
// =================================================================
// File: onu/api/other_stock_api.php
// Description: Handles all AJAX requests for the Other Stock management page.
// =================================================================

// ফিক্স: কনফিগারেশন ফাইল ইনক্লুড করা হলো
require_once '../config.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action specified.'];

switch ($action) {

    case 'save_product':
        $product_name = trim($_POST['product_name']);
        $company = trim($_POST['company']);
        if (empty($product_name)) {
            $response['message'] = 'প্রোডাক্টের নাম আবশ্যক।';
            break;
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, company) VALUES (?, ?)");
            $stmt->execute([$product_name, $company]);
            $response = ['success' => true, 'message' => 'নতুন প্রোডাক্ট সফলভাবে যোগ করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = ($e->errorInfo[1] == 1062) ? 'এই নামের প্রোডাক্ট ஏற்கனவே আছে।' : 'Database error.';
        }
        break;

    case 'save_stock_in':
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $log_date = $_POST['log_date'];
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $unit_price = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
        
        // ভ্যালিডেশন চেক
        if (!$product_id || empty($log_date) || !$quantity || $unit_price === false || $unit_price === null) {
            $response['message'] = 'অনুগ্রহ করে সকল তথ্য সঠিকভাবে পূরণ করুন।';
            break;
        }
        $total_price = $quantity * $unit_price;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO product_stock_logs (product_id, log_date, log_type, quantity, unit_price, total_price) VALUES (?, ?, 'in', ?, ?, ?)");
            $stmt->execute([$product_id, $log_date, $quantity, $unit_price, $total_price]);
            
            $stmt_update = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmt_update->execute([$quantity, $product_id]);
            
            $pdo->commit();
            $response = ['success' => true, 'message' => 'স্টক সফলভাবে যোগ করা হয়েছে!'];
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;

    case 'save_stock_out':
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $log_date = $_POST['log_date'];
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
        $customer_id = trim($_POST['reference_customer_id']);
        
        if (!$product_id || empty($log_date) || !$quantity || !$employee_id) {
            $response['message'] = 'অনুগ্রহ করে সকল তথ্য সঠিকভাবে পূরণ করুন।';
            break;
        }

        $pdo->beginTransaction();
        try {
            $stmt_check = $pdo->prepare("SELECT current_stock FROM products WHERE id = ?");
            $stmt_check->execute([$product_id]);
            $current_stock = $stmt_check->fetchColumn();

            if ($current_stock < $quantity) {
                 throw new Exception('অপর্যাপ্ত স্টক! স্টকে আছে: ' . $current_stock);
            }

            $stmt = $pdo->prepare("INSERT INTO product_stock_logs (product_id, log_date, log_type, quantity, employee_id, reference_customer_id) VALUES (?, ?, 'out', ?, ?, ?)");
            $stmt->execute([$product_id, $log_date, $quantity, $employee_id, $customer_id]);
            
            $stmt_update = $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?");
            $stmt_update->execute([$quantity, $product_id]);
            
            $pdo->commit();
            $response = ['success' => true, 'message' => 'প্রোডাক্ট সফলভাবে বিতরণ করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = $e->getMessage();
        }
        break;

    case 'get_stock_log':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $response['message'] = 'Invalid ID.';
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM product_stock_logs WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        $response = $record ? ['success' => true, 'data' => $record] : ['success' => false, 'message' => 'Log not found.'];
        break;

    case 'update_stock_log':
        $log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
        if (!$log_id) {
            $response['message'] = 'Invalid Log ID.';
            break;
        }

        $pdo->beginTransaction();
        try {
            // Get original log details
            $stmt_orig = $pdo->prepare("SELECT * FROM product_stock_logs WHERE id = ?");
            $stmt_orig->execute([$log_id]);
            $original_log = $stmt_orig->fetch(PDO::FETCH_ASSOC);

            if (!$original_log) {
                throw new Exception("Original log not found.");
            }

            // Reverse the original stock change
            $qty_diff = 0;
            if ($original_log['log_type'] == 'in') {
                $qty_diff = -$original_log['quantity'];
            } else { // 'out'
                $qty_diff = +$original_log['quantity'];
            }
            $stmt_rev = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmt_rev->execute([$qty_diff, $original_log['product_id']]);

            // Get new data from POST
            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $log_date = $_POST['log_date'];
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            $new_qty_diff = 0;

            if ($original_log['log_type'] == 'in') {
                $unit_price = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
                $total_price = $quantity * $unit_price;
                $stmt_upd = $pdo->prepare("UPDATE product_stock_logs SET product_id=?, log_date=?, quantity=?, unit_price=?, total_price=? WHERE id=?");
                $stmt_upd->execute([$product_id, $log_date, $quantity, $unit_price, $total_price, $log_id]);
                $new_qty_diff = +$quantity;
            } else { // 'out'
                $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
                $customer_id = trim($_POST['reference_customer_id']);
                $stmt_upd = $pdo->prepare("UPDATE product_stock_logs SET product_id=?, log_date=?, quantity=?, employee_id=?, reference_customer_id=? WHERE id=?");
                $stmt_upd->execute([$product_id, $log_date, $quantity, $employee_id, $customer_id, $log_id]);
                $new_qty_diff = -$quantity;
            }

            // Apply the new stock change
            $stmt_new = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmt_new->execute([$new_qty_diff, $product_id]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'স্টক লগ সফলভাবে আপডেট করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;

    case 'delete_stock_log':
        $log_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$log_id) {
            $response['message'] = 'Invalid Log ID.';
            break;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT product_id, log_type, quantity FROM product_stock_logs WHERE id = ?");
            $stmt->execute([$log_id]);
            $log = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$log) {
                throw new Exception("Log not found.");
            }

            // Delete the log
            $stmt_del = $pdo->prepare("DELETE FROM product_stock_logs WHERE id = ?");
            $stmt_del->execute([$log_id]);

            // Adjust product stock
            $quantity_change = 0;
            if ($log['log_type'] == 'in') {
                $quantity_change = -$log['quantity'];
            } else { // 'out'
                $quantity_change = +$log['quantity'];
            }

            $stmt_update = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
            $stmt_update->execute([$quantity_change, $log['product_id']]);
            
            $pdo->commit();
            $response = ['success' => true, 'message' => 'স্টক লগ সফলভাবে ডিলিট করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;
}

echo json_encode($response);
?>