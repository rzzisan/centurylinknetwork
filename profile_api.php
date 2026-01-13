<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

switch ($action) {
    case 'change_password':
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $employee_id = $_SESSION['employee_id'];

        if (empty($current_password) || empty($new_password)) {
            $response['message'] = 'অনুগ্রহ করে সকল ঘর পূরণ করুন।';
            break;
        }

        try {
            $stmt = $pdo->prepare("SELECT password FROM employees WHERE id = ?");
            $stmt->execute([$employee_id]);
            $hashed_password = $stmt->fetchColumn();

            if (password_verify($current_password, $hashed_password)) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $update_stmt->execute([$new_hashed_password, $employee_id]);
                $response = ['success' => true, 'message' => 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে!'];
            } else {
                $response['message'] = 'আপনার বর্তমান পাসওয়ার্ডটি ভুল।';
            }
        } catch (PDOException $e) {
            $response['message'] = 'ডাটাবেস ত্রুটি: ' . $e->getMessage();
        }
        break;
}

echo json_encode($response);
?>