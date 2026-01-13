<?php
// =================================================================
// File: connection_api.php
// Description: Handles all AJAX requests for New Connection management.
// =================================================================

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action specified.'];

switch ($action) {

    case 'save_new_connection':
    case 'update_connection':
        $is_update = ($action === 'update_connection');
        $id = filter_input(INPUT_POST, 'connection_id', FILTER_VALIDATE_INT);

        if ($is_update && !$id) {
            $response['message'] = 'অবৈধ আইডি।';
            break;
        }

        // Collect and sanitize data
        $connection_date = $_POST['connection_date'];
        $customer_id_code = trim($_POST['customer_id_code']);
        $customer_name = trim($_POST['customer_name']);
        $mobile_number = trim($_POST['mobile_number']);
        $address = trim($_POST['address']);
        $connection_type = trim($_POST['connection_type']);
        $materials_used = trim($_POST['materials_used']);
        $total_price = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0;
        $deposit_amount = filter_input(INPUT_POST, 'deposit_amount', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0;
        $order_taker_id = filter_input(INPUT_POST, 'order_taker_id', FILTER_VALIDATE_INT);
        $money_with_id = filter_input(INPUT_POST, 'money_with_id', FILTER_VALIDATE_INT);
        $due_amount = $total_price - $deposit_amount;

        try {
            if ($is_update) {
                $sql = "UPDATE new_connections SET connection_date=?, customer_id_code=?, customer_name=?, mobile_number=?, address=?, connection_type=?, materials_used=?, total_price=?, deposit_amount=?, due_amount=?, order_taker_id=?, money_with_id=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$connection_date, $customer_id_code, $customer_name, $mobile_number, $address, $connection_type, $materials_used, $total_price, $deposit_amount, $due_amount, $order_taker_id, $money_with_id, $id]);
                $response = ['success' => true, 'message' => 'কানেকশন সফলভাবে আপডেট করা হয়েছে!'];
            } else {
                $sql = "INSERT INTO new_connections (connection_date, customer_id_code, customer_name, mobile_number, address, connection_type, materials_used, total_price, deposit_amount, due_amount, order_taker_id, money_with_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$connection_date, $customer_id_code, $customer_name, $mobile_number, $address, $connection_type, $materials_used, $total_price, $deposit_amount, $due_amount, $order_taker_id, $money_with_id]);
                $response = ['success' => true, 'message' => 'নতুন কানেকশন সফলভাবে যোগ করা হয়েছে!'];
            }
        } catch (PDOException $e) {
            $response['message'] = ($e->errorInfo[1] == 1062) ? 'এই ID (কোড) টি ஏற்கனவே ব্যবহার করা হয়েছে।' : 'Database error: ' . $e->getMessage();
        }
        break;

    case 'get_connection_details':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        $sql = "SELECT nc.*, e_money.full_name as money_with_name 
                FROM new_connections nc 
                LEFT JOIN employees e_money ON nc.money_with_id = e_money.id 
                WHERE nc.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response = $record ? ['success' => true, 'data' => $record] : ['success' => false, 'message' => 'কানেকশন পাওয়া যায়নি।'];
        break;
        
    case 'delete_connection':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        try {
            $stmt = $pdo->prepare("DELETE FROM new_connections WHERE id = ?");
            $stmt->execute([$id]);
            $response = ['success' => true, 'message' => 'কানেকশন সফলভাবে ডিলিট করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = 'Database error.';
        }
        break;

    case 'get_address_suggestions':
        $query = $_GET['query'] ?? '';
        $stmt = $pdo->prepare("SELECT DISTINCT address FROM new_connections WHERE address LIKE ? LIMIT 5");
        $stmt->execute(['%' . $query . '%']);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['success' => true, 'data' => $suggestions];
        break;

    case 'save_due_payment':
        $connection_id = filter_input(INPUT_POST, 'connection_id', FILTER_VALIDATE_INT);
        $paid_amount = filter_input(INPUT_POST, 'paid_amount', FILTER_VALIDATE_FLOAT);
        $discount_amount = filter_input(INPUT_POST, 'discount_amount', FILTER_VALIDATE_FLOAT) ?: 0;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT money_with_id FROM new_connections WHERE id = ?");
            $stmt->execute([$connection_id]);
            $collected_by_id = $stmt->fetchColumn();

            $sql_log = "INSERT INTO due_payments (connection_id, payment_date, paid_amount, discount_amount, collected_by_id) VALUES (?, CURDATE(), ?, ?, ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$connection_id, $paid_amount, $discount_amount, $collected_by_id]);

            $total_reduction = $paid_amount + $discount_amount;
            $sql_update = "UPDATE new_connections SET deposit_amount = deposit_amount + ?, due_amount = due_amount - ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$paid_amount, $total_reduction, $connection_id]);
            
            $pdo->commit();
            $response = ['success' => true, 'message' => 'বকেয়া সফলভাবে জমা করা হয়েছে!'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;
    // --- NEW CONNECTION SEARCH ---
    case 'search_connections':
        $search_query = trim($_GET['query'] ?? '');
        if (empty($search_query)) {
            echo json_encode(['success' => false, 'message' => 'Search term required.']);
            exit;
        }

        $sql = "SELECT nc.*, e_order.full_name as order_taker_name, e_money.full_name as money_with_name
                FROM new_connections nc
                LEFT JOIN employees e_order ON nc.order_taker_id = e_order.id
                LEFT JOIN employees e_money ON nc.money_with_id = e_money.id
                WHERE nc.customer_id_code LIKE ? OR nc.mobile_number LIKE ?
                ORDER BY nc.connection_date DESC, nc.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['%' . $search_query . '%', '%' . $search_query . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results) {
            $response = ['success' => true, 'data' => $results];
        } else {
            $response = ['success' => true, 'data' => [], 'message' => 'No results found.'];
        }
        break;
}

echo json_encode($response);
?>