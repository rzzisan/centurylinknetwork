<?php
// =================================================================
// File: onu/api/cable_api.php
// Description: Handles all AJAX requests for the new Fiber Cable management system.
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

    case 'save_fiber_drum':
        $pdo->beginTransaction();
        try {
            $drum_code = trim($_POST['drum_code']);
            $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
            $fiber_core = trim($_POST['fiber_core']);
            $company = trim($_POST['company']);
            $total_meter = filter_input(INPUT_POST, 'total_meter', FILTER_VALIDATE_INT);
            $metering_direction = $_POST['metering_direction'];
            $start_meter_mark = filter_input(INPUT_POST, 'start_meter_mark', FILTER_VALIDATE_INT);

            if (empty($drum_code) || !$year || empty($fiber_core) || !$total_meter || empty($metering_direction) || !isset($start_meter_mark)) {
                throw new Exception('অনুগ্রহ করে সকল তথ্য সঠিকভাবে পূরণ করুন।');
            }
            
            if ($metering_direction == 'desc') {
                $end_meter_mark = $start_meter_mark - $total_meter + 1;
            } else { // asc
                $end_meter_mark = $start_meter_mark + $total_meter - 1;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO fiber_drums (drum_code, year, fiber_core, company, total_meter, current_meter, metering_direction, start_meter_mark, end_meter_mark) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$drum_code, $year, $fiber_core, $company, $total_meter, $total_meter, $metering_direction, $start_meter_mark, $end_meter_mark]);
            
            $pdo->commit();
            $response = ['success' => true, 'message' => 'নতুন ড্রাম সফলভাবে যোগ করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = ($e->errorInfo[1] == 1062) ? 'এই ড্রাম কোডটি ஏற்கனவே আছে।' : 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;
        
    case 'get_drum_details':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if(!$id) {
            $response['message'] = 'Invalid ID.';
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM fiber_drums WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        $response = $record ? ['success' => true, 'data' => $record] : ['success' => false, 'message' => 'Drum not found.'];
        break;

    case 'update_drum':
        $pdo->beginTransaction();
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $drum_code = trim($_POST['drum_code']);
            $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
            $fiber_core = trim($_POST['fiber_core']);
            $company = trim($_POST['company']);
            $new_total_meter = filter_input(INPUT_POST, 'total_meter', FILTER_VALIDATE_INT);
            $metering_direction = $_POST['metering_direction'];
            $start_meter_mark = filter_input(INPUT_POST, 'start_meter_mark', FILTER_VALIDATE_INT);

            if (!$id || empty($drum_code) || !$year || !$new_total_meter || !isset($start_meter_mark)) {
                throw new Exception('অনুগ্রহ করে সকল তথ্য সঠিকভাবে পূরণ করুন।');
            }

            // Fetch original drum data to calculate usage
            $stmt_orig = $pdo->prepare("SELECT total_meter, current_meter FROM fiber_drums WHERE id = ?");
            $stmt_orig->execute([$id]);
            $original_drum = $stmt_orig->fetch(PDO::FETCH_ASSOC);
            $used_meter = $original_drum['total_meter'] - $original_drum['current_meter'];

            if ($new_total_meter < $used_meter) {
                throw new Exception("নতুন মোট মিটার ({$new_total_meter}m) ব্যবহৃত মিটারের ({$used_meter}m) চেয়ে কম হতে পারবে না।");
            }
            
            $new_current_meter = $new_total_meter - $used_meter;

            if ($metering_direction == 'desc') {
                $end_meter_mark = $start_meter_mark - $new_total_meter + 1;
            } else { // asc
                $end_meter_mark = $start_meter_mark + $new_total_meter - 1;
            }

            $stmt_update = $pdo->prepare(
                "UPDATE fiber_drums SET drum_code=?, year=?, fiber_core=?, company=?, total_meter=?, current_meter=?, 
                 metering_direction=?, start_meter_mark=?, end_meter_mark=? WHERE id=?"
            );
            $stmt_update->execute([$drum_code, $year, $fiber_core, $company, $new_total_meter, $new_current_meter, $metering_direction, $start_meter_mark, $end_meter_mark, $id]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'ড্রামের তথ্য সফলভাবে আপডেট করা হয়েছে!'];
        
        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = ($e->errorInfo[1] == 1062) ? 'এই ড্রাম কোডটি ஏற்கனவே আছে।' : 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;

    case 'delete_drum':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $response['message'] = 'Invalid ID.';
            break;
        }

        try {
            // Check for usage logs before deleting
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM cable_usage_logs WHERE drum_id = ?");
            $stmt_check->execute([$id]);
            if ($stmt_check->fetchColumn() > 0) {
                throw new Exception('এই ড্রামটি ডিলিট করা যাবে না কারণ এটির ব্যবহারের হিসাব রয়েছে। প্রথমে খরচের হিসাব ডিলিট করুন।');
            }
            
            $stmt_delete = $pdo->prepare("DELETE FROM fiber_drums WHERE id = ?");
            $stmt_delete->execute([$id]);
            
            $response = ['success' => true, 'message' => 'ড্রাম সফলভাবে ডিলিট করা হয়েছে!'];

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'save_cable_usage':
        $pdo->beginTransaction();
        try {
            $drum_id = filter_input(INPUT_POST, 'drum_id', FILTER_VALIDATE_INT);
            $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
            $usage_date = $_POST['usage_date'];
            $total_meter_used = filter_input(INPUT_POST, 'total_meter_used', FILTER_VALIDATE_INT);
            
            $customer_ids = $_POST['customer_id'] ?? [];
            $meters_used_detail = $_POST['meter_used_detail'] ?? [];

            if (!$drum_id || !$employee_id || empty($usage_date) || !isset($total_meter_used) || $total_meter_used <= 0) {
                 throw new Exception('অনুগ্রহ করে সকল প্রধান তথ্য পূরণ করুন এবং খরচের পরিমাণ ০ এর বেশি হতে হবে।');
            }

            $stmt_drum = $pdo->prepare("SELECT current_meter FROM fiber_drums WHERE id = ?");
            $stmt_drum->execute([$drum_id]);
            $current_meter = $stmt_drum->fetchColumn();

            if ($current_meter < $total_meter_used) {
                throw new Exception("অপর্যাপ্ত স্টক! ড্রামে {$current_meter}m ক্যাবল আছে, কিন্তু খরচ দেখানো হয়েছে {$total_meter_used}m।");
            }
            $new_current_meter = $current_meter - $total_meter_used;
            
            $sql_log = "INSERT INTO cable_usage_logs (drum_id, employee_id, usage_date, meter_at_start, meter_at_end, meter_used) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_log = $pdo->prepare($sql_log);
            $stmt_log->execute([$drum_id, $employee_id, $usage_date, $current_meter, $new_current_meter, $total_meter_used]);
            $usage_log_id = $pdo->lastInsertId();

            $sql_detail = "INSERT INTO cable_usage_details (usage_log_id, customer_id, meter_used) VALUES (?, ?, ?)";
            $stmt_detail = $pdo->prepare($sql_detail);
            foreach ($customer_ids as $key => $customer_id) {
                if (!empty($customer_id) && !empty($meters_used_detail[$key])) {
                    $stmt_detail->execute([$usage_log_id, $customer_id, $meters_used_detail[$key]]);
                }
            }
            
            $sql_update = "UPDATE fiber_drums SET current_meter = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$new_current_meter, $drum_id]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'খরচের হিসাব সফলভাবে সেভ করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;
}

echo json_encode($response);
?>