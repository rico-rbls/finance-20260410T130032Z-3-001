<?php
/**
 * api_handler.php
 * Central Controller for all Financial Transactions
 */
include 'finance_logic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Direct access denied. Only POST requests allowed.';
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'reserve') {
    $dept_id = intval($_POST['dept_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $desc = trim($_POST['desc'] ?? 'Reservation');
    echo reserveBudget($dept_id, $amount, $desc);
    exit;
}

if ($action === 'approve_reservation') {
    $res_id = intval($_POST['res_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE budget_reservations SET status = 'committed' WHERE res_id = ?");
    $stmt->bind_param("i", $res_id);
    echo $stmt->execute() ? 'success' : 'Database error occurred.';
    exit;
}

if ($action === 'reject_reservation') {
    $res_id = intval($_POST['res_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE budget_reservations SET status = 'cancelled' WHERE res_id = ?");
    $stmt->bind_param("i", $res_id);
    echo $stmt->execute() ? 'success' : 'Database error occurred.';
    exit;
}

if ($action === 'create_invoice') {
    $student_name = trim($_POST['student_name'] ?? '');
    $amt = floatval($_POST['total_amount'] ?? 0);
    $date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO invoices (student_name, total_amount, status, date_issued) VALUES (?, ?, 'unpaid', ?)");
    $stmt->bind_param("sds", $student_name, $amt, $date);

    if ($stmt->execute()) {
        $invoice_id = $conn->insert_id;
        recordJournalEntry("Student Invoice #$invoice_id - $student_name", 'Student_Invoice', $invoice_id, 5, 6, $amt);
        adjustAccountBalance(5, $amt);
        adjustAccountBalance(6, $amt);
        echo 'Invoice created successfully.';
    } else {
        echo 'Error creating invoice.';
    }
    exit;
}

if ($action === 'payment') {
    $inv_id = intval($_POST['inv_id'] ?? ($_POST['invoice_id'] ?? 0));
    $pay_amt = floatval($_POST['pay_amt'] ?? ($_POST['amount'] ?? 0));
    $method = trim($_POST['method'] ?? 'cash');

    $stmt = $conn->prepare("SELECT invoice_id, total_amount FROM invoices WHERE invoice_id = ?");
    $stmt->bind_param("i", $inv_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();

    if (!$invoice) {
        echo 'Invoice not found.';
        exit;
    }

    $insert = $conn->prepare("INSERT INTO payments (invoice_id, amount_paid, payment_method) VALUES (?, ?, ?)");
    $insert->bind_param("ids", $inv_id, $pay_amt, $method);

    if (!$insert->execute()) {
        echo 'Error processing payment.';
        exit;
    }

    if (!recordJournalEntry("Student Payment Receipt - Invoice #$inv_id", 'Student_Invoice', $inv_id, 4, 5, $pay_amt)) {
        echo 'Error: Could not update financial ledger.';
        exit;
    }

    adjustAccountBalance(4, $pay_amt);
    adjustAccountBalance(5, -$pay_amt);

    $paidStmt = $conn->prepare("SELECT IFNULL(SUM(amount_paid), 0) AS total_paid FROM payments WHERE invoice_id = ?");
    $paidStmt->bind_param("i", $inv_id);
    $paidStmt->execute();
    $totalPaid = (float) $paidStmt->get_result()->fetch_assoc()['total_paid'];
    $newStatus = ($totalPaid >= (float) $invoice['total_amount']) ? 'paid' : 'partial';

    $statusStmt = $conn->prepare("UPDATE invoices SET status = ? WHERE invoice_id = ?");
    $statusStmt->bind_param("si", $newStatus, $inv_id);
    $statusStmt->execute();

    echo $newStatus === 'paid' ? 'Payment Processed. Invoice marked as paid.' : 'Partial payment processed.';
    exit;
}

if ($action === 'create_po') {
    $dept_id = intval($_POST['dept_id'] ?? 0);
    $vendor_id = intval($_POST['vendor_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO purchase_orders (dept_id, vendor_id, total_amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iid", $dept_id, $vendor_id, $amount);

    if (!$stmt->execute()) {
        echo 'Error: Database failed to generate Purchase Order.';
        exit;
    }

    $po_id = $conn->insert_id;
    $reservation = reserveBudget($dept_id, $amount, "PO #$po_id Reservation", $po_id);

    if ($reservation !== 'Reservation Successful') {
        $conn->query("DELETE FROM purchase_orders WHERE po_id = $po_id");
        echo $reservation;
        exit;
    }

    echo 'Purchase Order created successfully and linked to a budget reservation.';
    exit;
}

if ($action === 'approve_po') {
    $po_id = intval($_POST['po_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'approved' WHERE po_id = ?");
    $stmt->bind_param("i", $po_id);
    echo $stmt->execute() ? 'success' : 'Database error occurred.';
    exit;
}

if ($action === 'receive_po') {
    $po_id = intval($_POST['po_id'] ?? 0);
    if (file_exists('procurement_logic.php')) {
        include 'procurement_logic.php';
        echo processPurchaseOrder($po_id);
    } else {
        echo 'Error: Procurement logic module is missing.';
    }
    exit;
}

if ($action === 'record_vendor_payment') {
    $ap_id = intval($_POST['ap_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = trim($_POST['method'] ?? 'cash');
    echo recordVendorPayment($ap_id, $amount, $method);
    exit;
}

if ($action === 'update_budget') {
    $dept_id = intval($_POST['dept_id'] ?? 0);
    $new_budget = floatval($_POST['new_amount'] ?? 0);

    $stmt = $conn->prepare("UPDATE departments SET total_budget = ? WHERE dept_id = ?");
    $stmt->bind_param("di", $new_budget, $dept_id);
    echo $stmt->execute() ? 'Department budget adjusted manually.' : 'Update failed.';
    exit;
}

echo 'Invalid Request Action.';
?>
