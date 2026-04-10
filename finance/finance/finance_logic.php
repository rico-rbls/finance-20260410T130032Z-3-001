<?php
include 'db_connect.php';

/**
 * Requirement: Budgeting & Reservations
 */
function reserveBudget($dept_id, $amount, $description) {
    global $conn;
    
    // Check current budget vs existing reservations
    $sql = "SELECT total_budget FROM departments WHERE dept_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total_budget'];

    $sql_res = "SELECT SUM(amount_reserved) as reserved FROM budget_reservations WHERE dept_id = ? AND status != 'cancelled'";
    $stmt_res = $conn->prepare($sql_res);
    $stmt_res->bind_param("i", $dept_id);
    $stmt_res->execute();
    $already_reserved = $stmt_res->get_result()->fetch_assoc()['reserved'] ?? 0;

    if (($already_reserved + $amount) <= $total) {
        $insert = $conn->prepare("INSERT INTO budget_reservations (dept_id, amount_reserved, description) VALUES (?, ?, ?)");
        $insert->bind_param("ids", $dept_id, $amount, $description);
        return $insert->execute() ? "Reservation Successful" : "DB Error";
    } else {
        return "Insufficient Budget Available";
    }
}

/**
 * Requirement: Accounting & Ledgers (Double-Entry Automation)
 */
function recordJournalEntry($desc, $ref_type, $ref_id, $debit_acc, $credit_acc, $amount) {
    global $conn;
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO journal_entries (description, reference_type, reference_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $desc, $ref_type, $ref_id);
        $stmt->execute();
        $entry_id = $conn->insert_id;

        // Debit Entry
        $d = $conn->prepare("INSERT INTO ledger_details (entry_id, account_id, debit) VALUES (?, ?, ?)");
        $d->bind_param("iid", $entry_id, $debit_acc, $amount);
        $d->execute();

        // Credit Entry
        $c = $conn->prepare("INSERT INTO ledger_details (entry_id, account_id, credit) VALUES (?, ?, ?)");
        $c->bind_param("iid", $entry_id, $credit_acc, $amount);
        $c->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?>
