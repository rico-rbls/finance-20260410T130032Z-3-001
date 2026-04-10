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

// Function to get Income Statement Data
function getIncomeStatement() {
    global $conn;
    $sql = "SELECT account_name, account_type, balance 
            FROM chart_of_accounts 
            WHERE account_type IN ('Revenue', 'Expense')
            ORDER BY account_type, account_name";
    return $conn->query($sql);
}

// Function to get Cash Flow Summary
function getCashFlow() {
    global $conn;
    $sql = "SELECT 
                COALESCE(SUM(CASE WHEN ld.debit > 0 THEN ld.debit ELSE 0 END), 0) AS cash_in,
                COALESCE(SUM(CASE WHEN ld.credit > 0 THEN ld.credit ELSE 0 END), 0) AS cash_out,
                COALESCE(SUM(CASE WHEN ld.debit > 0 THEN ld.debit ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN ld.credit > 0 THEN ld.credit ELSE 0 END), 0) AS net_cash
            FROM ledger_details ld
            WHERE ld.account_id = 4";
    return $conn->query($sql);
}
?>
