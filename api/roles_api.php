<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to perform this action.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_role':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Role name cannot be empty.');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Role already exists.');
            }

            $stmt = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
            $stmt->execute([$name]);

            echo json_encode(['success' => true, 'message' => 'Role added successfully.']);
            break;

        case 'edit_role':
            $id = trim($_POST['id'] ?? '');
            $new_name = trim($_POST['new_name'] ?? '');

            if (empty($id) || empty($new_name)) {
                throw new Exception('Role ID and new name cannot be empty.');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE name = ? AND id != ?");
            $stmt->execute([$new_name, $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Role "' . $new_name . '" already exists.');
            }

            $stmt = $pdo->prepare("UPDATE roles SET name = ? WHERE id = ?");
            $stmt->execute([$new_name, $id]);

            echo json_encode(['success' => true, 'message' => 'Role updated successfully.']);
            break;

        case 'delete_role':
            $id = trim($_POST['id'] ?? '');
            if (empty($id)) {
                throw new Exception('Role ID cannot be empty.');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE role_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete role. It is currently assigned to one or more employees.');
            }

            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Role deleted successfully.']);
            break;

        default:
            throw new Exception('Invalid action.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
