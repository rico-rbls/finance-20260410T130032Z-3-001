<?php
include 'db_connect.php';

function adjustAccountBalance($account_id, $delta) {
    global $conn;
    $stmt = $conn->prepare("UPDATE chart_of_accounts SET balance = balance + ? WHERE account_id = ?");
    $stmt->bind_param("di", $delta, $account_id);
    return $stmt->execute();
}

/**
 * Requirement: Budgeting & Reservations
 */
function reserveBudget($dept_id, $amount, $description, $po_id = null) {
    global $conn;

    $stmt = $conn->prepare("SELECT total_budget FROM departments WHERE dept_id = ?");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $dept = $stmt->get_result()->fetch_assoc();

    if (!$dept) {
        return "Department not found";
    }

    $stmt_res = $conn->prepare("SELECT IFNULL(SUM(amount_reserved), 0) AS reserved FROM budget_reservations WHERE dept_id = ? AND status != 'cancelled'");
    $stmt_res->bind_param("i", $dept_id);
    $stmt_res->execute();
    $already_reserved = (float) ($stmt_res->get_result()->fetch_assoc()['reserved'] ?? 0);

    if (($already_reserved + $amount) > (float) $dept['total_budget']) {
        return "Insufficient Budget Available";
    }

    if ($po_id !== null) {
        $insert = $conn->prepare("INSERT INTO budget_reservations (dept_id, po_id, amount_reserved, description) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iids", $dept_id, $po_id, $amount, $description);
    } else {
        $insert = $conn->prepare("INSERT INTO budget_reservations (dept_id, amount_reserved, description) VALUES (?, ?, ?)");
        $insert->bind_param("ids", $dept_id, $amount, $description);
    }

    return $insert->execute() ? "Reservation Successful" : "DB Error";
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

        $debit = $conn->prepare("INSERT INTO ledger_details (entry_id, account_id, debit) VALUES (?, ?, ?)");
        $debit->bind_param("iid", $entry_id, $debit_acc, $amount);
        $debit->execute();

        $credit = $conn->prepare("INSERT INTO ledger_details (entry_id, account_id, credit) VALUES (?, ?, ?)");
        $credit->bind_param("iid", $entry_id, $credit_acc, $amount);
        $credit->execute();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Requirement: Procurement Integration / AP Posting
 */
function createApInvoice($po_id, $vendor_id, $dept_id, $amount) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO ap_invoices (po_id, vendor_id, dept_id, amount_due, status) VALUES (?, ?, ?, ?, 'unpaid')");
    $stmt->bind_param("iiid", $po_id, $vendor_id, $dept_id, $amount);

    if (!$stmt->execute()) {
        return false;
    }

    return $conn->insert_id;
}

/**
 * Requirement: Vendor Payments / AP settlement
 */
function recordVendorPayment($ap_id, $amount, $method = 'cash') {
    global $conn;

    $stmt = $conn->prepare("SELECT amount_due FROM ap_invoices WHERE ap_id = ?");
    $stmt->bind_param("i", $ap_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();

    if (!$invoice) {
        return "AP invoice not found";
    }

    $paymentStmt = $conn->prepare("INSERT INTO vendor_payments (ap_id, amount_paid, payment_method) VALUES (?, ?, ?)");
    $paymentStmt->bind_param("ids", $ap_id, $amount, $method);

    if (!$paymentStmt->execute()) {
        return "Unable to record vendor payment";
    }

    if (!recordJournalEntry("Vendor Payment - AP #$ap_id", 'Vendor_Invoice', $ap_id, 3, 4, $amount)) {
        return "Unable to post ledger entry";
    }

    adjustAccountBalance(3, -$amount);
    adjustAccountBalance(4, -$amount);

    $paidStmt = $conn->prepare("SELECT IFNULL(SUM(amount_paid), 0) AS total_paid FROM vendor_payments WHERE ap_id = ?");
    $paidStmt->bind_param("i", $ap_id);
    $paidStmt->execute();
    $totalPaid = (float) $paidStmt->get_result()->fetch_assoc()['total_paid'];
    $status = ($totalPaid >= (float) $invoice['amount_due']) ? 'paid' : 'partial';

    $update = $conn->prepare("UPDATE ap_invoices SET status = ? WHERE ap_id = ?");
    $update->bind_param("si", $status, $ap_id);
    $update->execute();

    return "Vendor payment posted";
}
?>
