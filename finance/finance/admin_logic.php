<?php
include 'db_connect.php';
include 'auth_check.php';
restrictTo(['admin']); // Strict access control

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'update_budget') {
        $dept_id = intval($_POST['dept_id']);
        $new_budget = floatval($_POST['new_amount']);
        
        $stmt = $conn->prepare("UPDATE departments SET total_budget = ? WHERE dept_id = ?");
        $stmt->bind_param("di", $new_budget, $dept_id);
        echo $stmt->execute() ? "Budget Updated Successfully" : "Error Updating Budget";
    }

    if ($action === 'adjust_account') {
        $acc_id = intval($_POST['account_id']);
        $new_balance = floatval($_POST['balance']);
        
        $stmt = $conn->prepare("UPDATE chart_of_accounts SET balance = ? WHERE account_id = ?");
        $stmt->bind_param("di", $new_balance, $acc_id);
        echo $stmt->execute() ? "Account Balance Adjusted" : "Error Adjusting Account";
    }
    exit();
}
?>