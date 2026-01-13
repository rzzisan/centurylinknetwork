<?php
require_once '../config.php';

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}



header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

switch ($action) {

    case 'save_employee_settings':
        $salaries = $_POST['salary'] ?? [];
        $includes = $_POST['include'] ?? [];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE employees SET salary = ?, include_in_attendance = ? WHERE id = ?");
            
            $all_employees_stmt = $pdo->query("SELECT id FROM employees");
            $all_employee_ids = $all_employees_stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($all_employee_ids as $id) {
                $salary = $salaries[$id] ?? 0;
                $include_in_attendance = isset($includes[$id]) ? 1 : 0;
                $stmt->execute([$salary, $include_in_attendance, $id]);
            }
            
            $pdo->commit();
            $response = ['success' => true, 'message' => 'কর্মচারীর তথ্য সফলভাবে সেভ করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;

    case 'save_daily_attendance':
        $attendance_date = $_POST['attendance_date'];
        $employee_ids = $_POST['employee_ids'] ?? [];
        $statuses = $_POST['status'] ?? [];

        if (empty($attendance_date) || empty($employee_ids)) {
            $response['message'] = 'কোনো কর্মচারী পাওয়া যায়নি অথবা তারিখ নির্বাচন করা হয়নি।';
            break;
        }

        $pdo->beginTransaction();
        try {
            // Delete existing records for this date to replace them (UPSERT logic)
            $stmt_delete = $pdo->prepare("DELETE FROM attendance WHERE attendance_date = ?");
            $stmt_delete->execute([$attendance_date]);

            // Insert new/updated records
            $stmt_insert = $pdo->prepare("INSERT INTO attendance (employee_id, attendance_date, status) VALUES (?, ?, ?)");
            foreach ($employee_ids as $emp_id) {
                $status = $statuses[$emp_id] ?? 'absent';
                $stmt_insert->execute([$emp_id, $attendance_date, $status]);
            }
            
            $pdo->commit();
            $response = ['success' => true, 'message' => 'হাজিরা সফলভাবে সেভ করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;

    case 'save_overtime':
        // This part remains unchanged
        $overtime_date = $_POST['overtime_date'];
        $absent_employee_id = $_POST['absent_employee_id'];
        $overtime_employee_id = $_POST['overtime_employee_id'];

        if (empty($overtime_date) || empty($absent_employee_id) || empty($overtime_employee_id)) {
            $response['message'] = 'অনুগ্রহ করে সকল তথ্য পূরণ করুন।';
            break;
        }

        if ($absent_employee_id == $overtime_employee_id) {
            $response['message'] = 'একজন কর্মচারী নিজের অনুপস্থিতির জন্য ওভারটাইম করতে পারে না।';
            break;
        }
        
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM overtime WHERE overtime_date = ? AND absent_employee_id = ?");
        $stmt_check->execute([$overtime_date, $absent_employee_id]);
        if($stmt_check->fetchColumn() > 0){
             $response['message'] = 'এই কর্মচারীর জন্য আজকের ওভারটাইম ইতিমধ্যে এন্ট্রি করা হয়েছে।';
            break;
        }

        $pdo->beginTransaction();
        try {
            $stmt_salary = $pdo->prepare("SELECT salary FROM employees WHERE id = ?");
            $stmt_salary->execute([$absent_employee_id]);
            $salary = $stmt_salary->fetchColumn();

            if ($salary <= 0) {
                throw new Exception('মূল কর্মচারীর বেতন নির্ধারণ করা হয়নি।');
            }

            $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($overtime_date)), date('Y', strtotime($overtime_date)));
            $daily_wage = $salary / $days_in_month;

            $stmt_insert = $pdo->prepare("INSERT INTO overtime (overtime_date, absent_employee_id, overtime_employee_id, amount) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$overtime_date, $absent_employee_id, $overtime_employee_id, $daily_wage]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'ওভারটাইম সফলভাবে সেভ করা হয়েছে!'];

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = 'একটি ত্রুটি ঘটেছে: ' . $e->getMessage();
        }
        break;

    case 'get_monthly_summary':
        $year = $_GET['year'];
        $month = $_GET['month'];
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $employees_stmt = $pdo->prepare("SELECT id, full_name FROM employees WHERE include_in_attendance = 1 ORDER BY full_name ASC");
        $employees_stmt->execute();
        $employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $attendance_data = [];
        $stmt = $pdo->prepare(
            "SELECT employee_id, DAY(attendance_date) as day, status 
             FROM attendance 
             WHERE YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?"
        );
        $stmt->execute([$year, $month]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as $record) {
            $attendance_data[$record['employee_id']][$record['day']] = $record['status'];
        }

        $response = [
            'success' => true,
            'data' => [
                'employees' => $employees,
                'attendance_data' => $attendance_data,
                'days_in_month' => $days_in_month,
                'month_name' => date('F, Y', mktime(0, 0, 0, $month, 1, $year))
            ]
        ];
        break;

    case 'get_payroll_report':
        // This part remains unchanged
        $month_year = $_POST['month'];
        $employee_id = $_POST['employee_id'];
        
        $year = date('Y', strtotime($month_year));
        $month = date('m', strtotime($month_year));
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $stmt_emp = $pdo->prepare("SELECT full_name, salary FROM employees WHERE id = ?");
        $stmt_emp->execute([$employee_id]);
        $employee = $stmt_emp->fetch(PDO::FETCH_ASSOC);

        if (!$employee || $employee['salary'] <= 0) {
            $response['message'] = 'এই কর্মচারীর বেতন নির্ধারণ করা হয়নি।';
            break;
        }

        $base_salary = $employee['salary'];
        $daily_rate = $base_salary / $days_in_month;

        $stmt_att = $pdo->prepare(
            "SELECT id, attendance_date, status FROM attendance 
             WHERE employee_id = ? AND YEAR(attendance_date) = ? AND MONTH(attendance_date) = ? 
             ORDER BY attendance_date ASC"
        );
        $stmt_att->execute([$employee_id, $year, $month]);
        $attendance_records = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_ot = $pdo->prepare(
            "SELECT overtime_date, amount FROM overtime 
             WHERE overtime_employee_id = ? AND YEAR(overtime_date) = ? AND MONTH(overtime_date) = ?"
        );
        $stmt_ot->execute([$employee_id, $year, $month]);
        $overtime_records = $stmt_ot->fetchAll(PDO::FETCH_ASSOC);
        
        $total_present = 0; $total_absent = 0; $total_half_day = 0; $total_leave = 0; $total_overtime_amount = 0;
        
        foreach($attendance_records as $rec) {
            if($rec['status'] == 'present') $total_present++;
            if($rec['status'] == 'absent') $total_absent++;
            if($rec['status'] == 'half_day') $total_half_day++;
            if($rec['status'] == 'leave') $total_leave++;
        }
        foreach($overtime_records as $ot) { $total_overtime_amount += $ot['amount']; }

        $paid_leave_days = 2;
        $unpaid_leave_days = max(0, $total_leave - $paid_leave_days);
        $deductible_absent_days = $total_absent + $unpaid_leave_days;
        $absent_deduction = $deductible_absent_days * $daily_rate;
        $half_day_deduction = $total_half_day * ($daily_rate / 2);
        $total_deduction = $absent_deduction + $half_day_deduction;
        $net_salary = $base_salary - $total_deduction + $total_overtime_amount;
        
        $response = [
            'success' => true,
            'data' => [
                'employee_name' => $employee['full_name'], 'month_name' => date('F, Y', strtotime($month_year)),
                'summary' => [
                    'base_salary' => number_format($base_salary, 2), 'daily_rate' => number_format($daily_rate, 2),
                    'total_days' => $days_in_month, 'present_days' => $total_present + $total_half_day,
                    'absent_days' => $total_absent, 'half_days' => $total_half_day, 'leave_days' => $total_leave,
                    'paid_leave' => $paid_leave_days, 'deductible_days' => $deductible_absent_days,
                    'overtime_amount' => number_format($total_overtime_amount, 2), 'absent_deduction' => number_format($absent_deduction, 2),
                    'half_day_deduction' => number_format($half_day_deduction, 2), 'total_deduction' => number_format($total_deduction, 2),
                    'net_salary' => number_format($net_salary, 2)
                ], 'details' => $attendance_records
            ]
        ];
        break;

    case 'update_single_attendance':
        $attendance_id = $_POST['attendance_id'];
        $status = $_POST['status'];

        try {
            $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
            $stmt->execute([$status, $attendance_id]);
            $response = ['success' => true, 'message' => 'হাজিরা সফলভাবে আপডেট করা হয়েছে!'];
        } catch (Exception $e) {
            $response['message'] = 'আপডেট করতে সমস্যা হয়েছে: ' . $e->getMessage();
        }
        break;
}

echo json_encode($response);
?>