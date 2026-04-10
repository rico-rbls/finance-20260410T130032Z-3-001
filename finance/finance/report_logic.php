<?php
include 'db_connect.php';

// Function to get Budget vs Actual Data
function getBudgetData() {
    global $conn;
    $sql = "SELECT d.dept_name, d.total_budget, 
            IFNULL(SUM(r.amount_reserved), 0) as spent_reserved,
            (d.total_budget - IFNULL(SUM(r.amount_reserved), 0)) as remaining
            FROM departments d
            LEFT JOIN budget_reservations r ON d.dept_id = r.dept_id AND r.status != 'cancelled'
            GROUP BY d.dept_id";
    return $conn->query($sql);
}

// Function to get Balance Sheet Data
function getBalanceSheet() {
    global $conn;
    $sql = "SELECT account_name, account_type, balance FROM chart_of_accounts ORDER BY account_type";
    return $conn->query($sql);
}
?>
