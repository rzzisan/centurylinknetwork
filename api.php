<?php
// =================================================================
// File: api.php (UPDATED)
// Description: Handles all AJAX requests for ONU, Brand, Stock, and Cable management.
// =================================================================

require_once 'config.php'; // <--- এই লাইনটি যোগ করা হয়েছে

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// is_admin() ফাংশনটি মুছে ফেলা হয়েছে কারণ এটির আর প্রয়োজন নেই।

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

switch ($action) {

    // --- Customer Duplicate Check ---
    case 'check_customer':
        $customerId = filter_input(INPUT_POST, 'customer_id', FILTER_SANITIZE_NUMBER_INT);
        $recordId = filter_input(INPUT_POST, 'record_id', FILTER_SANITIZE_NUMBER_INT);
        $sql = "SELECT * FROM onu_assignments WHERE customer_id = ? AND id != ? ORDER BY assignment_date DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId, $recordId ?: 0]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $response = ['success' => true, 'found' => true, 'data' => $result];
        } else {
            $response = ['success' => true, 'found' => false];
        }
        break;

    // --- Brand Management Cases ---
    case 'get_brand':
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT id, name, price FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        $response = $record ? ['success' => true, 'data' => $record] : ['success' => false, 'message' => 'Brand not found.'];
        break;

    case 'save_brand':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $name = trim($_POST['name']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

        if (empty($name) || $price === false) {
            $response['message'] = 'Please provide valid brand name and price.';
            break;
        }
        try {
            if (empty($id)) {
                $stmt = $pdo->prepare("INSERT INTO brands (name, price) VALUES (?, ?)");
                $stmt->execute([$name, $price]);
                $response = ['success' => true, 'message' => 'ব্র্যান্ড সফলভাবে যোগ করা হয়েছে!'];
            } else {
                $stmt = $pdo->prepare("UPDATE brands SET name = ?, price = ? WHERE id = ?");
                $stmt->execute([$name, $price, $id]);
                $response = ['success' => true, 'message' => 'ব্র্যান্ড সফলভাবে আপডেট করা হয়েছে!'];
            }
        } catch (PDOException $e) {
            $response['message'] = ($e->errorInfo[1] == 1062) ? 'এই নামের ব্র্যান্ড ஏற்கனவே আছে।' : 'Database error.';
        }
        break;

    case 'delete_brand':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_entries WHERE brand_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $response['message'] = 'এই ব্র্যান্ডটি ডিলিট করা যাবে না কারণ এটি স্টকে ব্যবহার করা হয়েছে।';
            break;
        }
        try {
            $delete_stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
            $delete_stmt->execute([$id]);
            $response = ['success' => true, 'message' => 'ব্র্যান্ড সফলভাবে ডিলিট করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = 'Database error.';
        }
        break;

    // --- Stock Entry Management Cases ---
    case 'get_stock_entry':
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT id, brand_id, quantity, purchase_date FROM stock_entries WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        $response = $record ? ['success' => true, 'data' => $record] : ['success' => false, 'message' => 'Stock entry not found.'];
        break;

    case 'save_stock_entry':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $brand_id = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $purchase_date = $_POST['purchase_date'];

        if (!$brand_id || !$quantity || empty($purchase_date)) {
            $response['message'] = 'Please fill all fields correctly.';
            break;
        }
        try {
            if (empty($id)) {
                $stmt = $pdo->prepare("INSERT INTO stock_entries (brand_id, quantity, purchase_date) VALUES (?, ?, ?)");
                $stmt->execute([$brand_id, $quantity, $purchase_date]);
                $response = ['success' => true, 'message' => 'স্টক এন্ট্রি সফলভাবে যোগ করা হয়েছে!'];
            } else {
                $stmt = $pdo->prepare("UPDATE stock_entries SET brand_id = ?, quantity = ?, purchase_date = ? WHERE id = ?");
                $stmt->execute([$brand_id, $quantity, $purchase_date, $id]);
                $response = ['success' => true, 'message' => 'স্টক এন্ট্রি সফলভাবে আপডেট করা হয়েছে!'];
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error.';
        }
        break;

    case 'delete_stock_entry':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        try {
            $stmt = $pdo->prepare("DELETE FROM stock_entries WHERE id = ?");
            $stmt->execute([$id]);
            $response = ['success' => true, 'message' => 'স্টক এন্ট্রি সফলভাবে ডিলিট করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = 'Database error.';
        }
        break;

    // --- Fiber Cable Management Cases ---
    case 'save_fiber_drum':
        // is_admin() check removed
        $drum_code = trim($_POST['drum_code']);
        $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
        $fiber_core = trim($_POST['fiber_core']);
        $company = trim($_POST['company']);
        $total_meter = filter_input(INPUT_POST, 'total_meter', FILTER_VALIDATE_INT);
        if (empty($drum_code) || !$year || empty($fiber_core) || empty($company) || !$total_meter) {
            $response['message'] = 'অনুগ্রহ করে সকল তথ্য সঠিকভাবে পূরণ করুন।';
            break;
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO fiber_drums (drum_code, year, fiber_core, company, total_meter, current_meter) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$drum_code, $year, $fiber_core, $company, $total_meter, $total_meter]);
            $response = ['success' => true, 'message' => 'নতুন ড্রাম সফলভাবে যোগ করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = ($e->errorInfo[1] == 1062) ? 'এই ড্রাম কোডটি ஏற்கனவே আছে।' : 'Database error: ' . $e->getMessage();
        }
        break;
        
    case 'get_fiber_drum':
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT * FROM fiber_drums WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        $response = $record ? ['success' => true, 'data' => $record] : ['success' => false, 'message' => 'ড্রাম খুঁজে পাওয়া যায়নি।'];
        break;
        
    case 'update_fiber_drum':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $drum_code = trim($_POST['drum_code']);
        $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
        $fiber_core = trim($_POST['fiber_core']);
        $company = trim($_POST['company']);
        $new_total_meter = filter_input(INPUT_POST, 'total_meter', FILTER_VALIDATE_INT);

        if (!$id || empty($drum_code) || !$year || empty($fiber_core) || empty($company) || !$new_total_meter) {
            $response['message'] = 'অনুগ্রহ করে সকল তথ্য সঠিকভাবে পূরণ করুন।';
            break;
        }
        try {
            $stmt = $pdo->prepare("SELECT total_meter, current_meter FROM fiber_drums WHERE id = ?");
            $stmt->execute([$id]);
            $drum = $stmt->fetch(PDO::FETCH_ASSOC);
            $used_meter = $drum['total_meter'] - $drum['current_meter'];

            if ($new_total_meter < $used_meter) {
                $response['message'] = 'নতুন মোট মিটার ব্যবহৃত মিটারের (' . $used_meter . 'm) চেয়ে কম হতে পারবে না।';
                break;
            }
            $new_current_meter = $new_total_meter - $used_meter;
            
            $stmt = $pdo->prepare("UPDATE fiber_drums SET drum_code=?, year=?, fiber_core=?, company=?, total_meter=?, current_meter=? WHERE id=?");
            $stmt->execute([$drum_code, $year, $fiber_core, $company, $new_total_meter, $new_current_meter, $id]);
            $response = ['success' => true, 'message' => 'ড্রামের তথ্য সফলভাবে আপডেট করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = ($e->errorInfo[1] == 1062) ? 'এই ড্রাম কোডটি ஏற்கனவே আছে।' : 'Database error: ' . $e->getMessage();
        }
        break;
        
    case 'delete_fiber_drum':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cable_usage_logs WHERE drum_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $response['message'] = 'এই ড্রামটি ডিলিট করা যাবে না কারণ এটির ব্যবহারের হিসাব রয়েছে। প্রথমে খরচের হিসাব ডিলিট করুন।';
            break;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM fiber_drums WHERE id = ?");
            $stmt->execute([$id]);
            $response = ['success' => true, 'message' => 'ড্রাম সফলভাবে ডিলিট করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = 'Database error.';
        }
        break;

    case 'save_cable_usage':
        $pdo->beginTransaction();
        try {
            $drum_id = filter_input(INPUT_POST, 'drum_id', FILTER_VALIDATE_INT);
            $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
            $usage_date = $_POST['usage_date'];
            $meter_at_start = filter_input(INPUT_POST, 'meter_at_start', FILTER_VALIDATE_INT);
            $meter_at_end = filter_input(INPUT_POST, 'meter_at_end', FILTER_VALIDATE_INT);
            $meter_used = filter_input(INPUT_POST, 'meter_used', FILTER_VALIDATE_INT);
            $customer_ids = $_POST['customer_id'] ?? [];
            $meter_used_details = $_POST['meter_used_detail'] ?? [];

            if (!$drum_id || !$employee_id || empty($usage_date) || !isset($meter_at_end)) {
                 throw new Exception('অনুগ্রহ করে সকল প্রধান তথ্য পূরণ করুন।');
            }

            $sql_log = "INSERT INTO cable_usage_logs (drum_id, employee_id, usage_date, meter_at_start, meter_at_end, meter_used) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$drum_id, $employee_id, $usage_date, $meter_at_start, $meter_at_end, $meter_used]);
            $usage_log_id = $pdo->lastInsertId();

            $sql_detail = "INSERT INTO cable_usage_details (usage_log_id, customer_id, meter_used) VALUES (?, ?, ?)";
            $stmt_detail = $pdo->prepare($sql_detail);
            foreach ($customer_ids as $key => $customer_id) {
                if (!empty($customer_id) && !empty($meter_used_details[$key])) {
                    $stmt_detail->execute([$usage_log_id, $customer_id, $meter_used_details[$key]]);
                }
            }

            $sql_update = "UPDATE fiber_drums SET current_meter = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$meter_at_end, $drum_id]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'খরচের হিসাব সফলভাবে সেভ করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;
    
    case 'get_cable_usage_log':
        $id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("SELECT * FROM cable_usage_logs WHERE id = ?");
        $stmt->execute([$id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($log) {
            $stmt_details = $pdo->prepare("SELECT * FROM cable_usage_details WHERE usage_log_id = ?");
            $stmt_details->execute([$id]);
            $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
            $log['details'] = $details;
            $response = ['success' => true, 'data' => $log];
        } else {
            $response['message'] = 'খরচের হিসাব পাওয়া যায়নি।';
        }
        break;

    case 'update_cable_usage_log':
        // is_admin() check removed
        $log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
        if (!$log_id) {
            $response['message'] = 'অবৈধ আইডি।';
            break;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT drum_id, meter_used FROM cable_usage_logs WHERE id = ?");
            $stmt->execute([$log_id]);
            $original_log = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$original_log) throw new Exception('মূল খরচের হিসাব পাওয়া যায়নি।');
            
            $original_meter_used = $original_log['meter_used'];
            $drum_id = $original_log['drum_id'];

            $new_usage_date = $_POST['usage_date'];
            $new_employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
            $customer_ids = $_POST['customer_id'] ?? [];
            $meter_used_details = $_POST['meter_used_detail'] ?? [];
            
            $new_total_used = 0;
            foreach ($meter_used_details as $meter) {
                if (!empty($meter)) $new_total_used += (int)$meter;
            }

            $meter_at_start = filter_input(INPUT_POST, 'meter_at_start', FILTER_VALIDATE_INT);
            $new_meter_at_end = $meter_at_start - $new_total_used;

            $stmt = $pdo->prepare("UPDATE cable_usage_logs SET usage_date=?, employee_id=?, meter_at_end=?, meter_used=? WHERE id=?");
            $stmt->execute([$new_usage_date, $new_employee_id, $new_meter_at_end, $new_total_used, $log_id]);
            
            $stmt = $pdo->prepare("DELETE FROM cable_usage_details WHERE usage_log_id = ?");
            $stmt->execute([$log_id]);

            $stmt_detail = $pdo->prepare("INSERT INTO cable_usage_details (usage_log_id, customer_id, meter_used) VALUES (?, ?, ?)");
            foreach ($customer_ids as $key => $customer_id) {
                if (!empty($customer_id) && !empty($meter_used_details[$key])) {
                    $stmt_detail->execute([$log_id, $customer_id, $meter_used_details[$key]]);
                }
            }

            $meter_difference = $original_meter_used - $new_total_used;
            $stmt = $pdo->prepare("UPDATE fiber_drums SET current_meter = current_meter + ? WHERE id = ?");
            $stmt->execute([$meter_difference, $drum_id]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'খরচের হিসাব সফলভাবে আপডেট করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;

    case 'delete_cable_usage_log':
        // is_admin() check removed
        $log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
        if (!$log_id) {
            $response['message'] = 'অবৈধ আইডি।';
            break;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT drum_id, meter_used FROM cable_usage_logs WHERE id = ?");
            $stmt->execute([$log_id]);
            $log = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$log) throw new Exception('খরচের হিসাব পাওয়া যায়নি।');

            $stmt = $pdo->prepare("DELETE FROM cable_usage_logs WHERE id = ?");
            $stmt->execute([$log_id]);

            $stmt = $pdo->prepare("UPDATE fiber_drums SET current_meter = current_meter + ? WHERE id = ?");
            $stmt->execute([$log['meter_used'], $log['drum_id']]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'খরচের হিসাব সফলভাবে ডিলিট করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;

    // --- ONU Assignment Cases ---
    case 'get_stock_brands':
        $sql = "SELECT b.name FROM brands b WHERE 
                ((SELECT IFNULL(SUM(se.quantity), 0) FROM stock_entries se WHERE se.brand_id = b.id) - 
                (SELECT COUNT(*) FROM onu_assignments oa WHERE oa.brand_name = b.name)) > 0 
                ORDER BY b.name ASC";
        $stmt = $pdo->query($sql);
        $brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $response = ['success' => true, 'data' => $brands];
        break;

    case 'get_record':
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT * FROM onu_assignments WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            $response = ['success' => true, 'data' => $record];
        } else {
            $response['message'] = 'Record not found.';
        }
        break;

    case 'save_record':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'record_id', FILTER_SANITIZE_NUMBER_INT);
        $date = $_POST['assignment_date'] ?? null;
        $customerId = filter_input(INPUT_POST, 'customer_id', FILTER_SANITIZE_NUMBER_INT);
        $brand = filter_input(INPUT_POST, 'brand_name', FILTER_SANITIZE_STRING);
        $mac = filter_input(INPUT_POST, 'mac_address', FILTER_SANITIZE_STRING);
        $purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING);
        $assignedTo = isset($_POST['assigned_to']) ? implode(', ', $_POST['assigned_to']) : '';
        $created_by = $_SESSION['employee_id'];

        try {
            if (empty($id)) {
                $sql = "INSERT INTO onu_assignments (assignment_date, customer_id, brand_name, mac_address, purpose, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$date, $customerId, $brand, $mac, $purpose, $assignedTo, $created_by]);
                $response = ['success' => true, 'message' => 'নতুন তথ্য যোগ করা হয়েছে!'];
            } else {
                $sql = "UPDATE onu_assignments SET assignment_date=?, customer_id=?, brand_name=?, mac_address=?, purpose=?, assigned_to=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$date, $customerId, $brand, $mac, $purpose, $assignedTo, $id]);
                $response = ['success' => true, 'message' => 'তথ্য আপডেট করা হয়েছে!'];
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        break;

    case 'delete_record':
        // is_admin() check removed
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        try {
            $stmt = $pdo->prepare("DELETE FROM onu_assignments WHERE id = ?");
            $stmt->execute([$id]);
            $response = ['success' => true, 'message' => 'রেকর্ড সফলভাবে ডিলিট করা হয়েছে!'];
        } catch (PDOException $e) {
            $response['message'] = 'Database error.';
        }
        break;

    // --- Other Product Stock Management Cases (NEW) ---
    case 'save_product':
        // is_admin() check removed
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
        if (!$product_id || empty($log_date) || !$quantity || $unit_price === false) {
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
            // Check for sufficient stock
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
    // --- ONU ASSIGNMENT SEARCH ---
    case 'search_onu_assignments':
        $search_query = trim($_GET['query'] ?? '');
        if (empty($search_query)) {
            echo json_encode(['success' => false, 'message' => 'Search term required.']);
            exit;
        }

        $sql = "SELECT oa.*, e_creator.full_name as creator_name 
                FROM onu_assignments oa 
                LEFT JOIN employees e_creator ON oa.created_by = e_creator.id 
                WHERE oa.customer_id LIKE ? OR oa.mac_address LIKE ?
                ORDER BY oa.assignment_date DESC";
        
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