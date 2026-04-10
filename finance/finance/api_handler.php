<?php
/**
 * api_handler.php
 * Central Controller for all Financial Transactions
 */
include 'auth_check.php';
include 'finance_logic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Direct access denied. Only POST requests allowed.';
    exit;
}

$action = $_POST['action'] ?? '';

function requirePositiveAmount($value, $fieldName = 'amount') {
    $amount = (float) $value;
    if ($amount <= 0) {
        echo 'Invalid ' . $fieldName . '. Amount must be greater than zero.';
        exit;
    }
    return $amount;
}

function requirePositiveId($value, $fieldName = 'id') {
    $id = (int) $value;
    if ($id <= 0) {
        echo 'Invalid ' . $fieldName . '.';
        exit;
    }
    return $id;
}

if ($action === 'reserve') {
    $dept_id = requirePositiveId($_POST['dept_id'] ?? 0, 'department');
    $amount = requirePositiveAmount($_POST['amount'] ?? 0, 'reservation amount');
    $desc = trim($_POST['desc'] ?? 'Reservation');

    if ($desc === '') {
        echo 'Reservation description is required.';
        exit;
    }

    echo reserveBudget($dept_id, $amount, $desc);
    exit;
}

if ($action === 'approve_reservation') {
    $res_id = requirePositiveId($_POST['res_id'] ?? 0, 'reservation');
    $stmt = $conn->prepare("UPDATE budget_reservations SET status = 'committed' WHERE res_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $res_id);

    if (!$stmt->execute()) {
        echo 'Database error occurred.';
        exit;
    }

    echo ($stmt->affected_rows > 0) ? 'success' : 'Reservation is not pending or no longer exists.';
    exit;
}

if ($action === 'reject_reservation') {
    $res_id = requirePositiveId($_POST['res_id'] ?? 0, 'reservation');
    $stmt = $conn->prepare("UPDATE budget_reservations SET status = 'cancelled' WHERE res_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $res_id);

    if (!$stmt->execute()) {
        echo 'Database error occurred.';
        exit;
    }

    echo ($stmt->affected_rows > 0) ? 'success' : 'Reservation is not pending or no longer exists.';
    exit;
}

if ($action === 'cancel_reservation') {
    $res_id = requirePositiveId($_POST['res_id'] ?? 0, 'reservation');
    $stmt = $conn->prepare("UPDATE budget_reservations SET status = 'cancelled' WHERE res_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $res_id);

    if (!$stmt->execute()) {
        echo 'Database error occurred.';
        exit;
    }

    echo ($stmt->affected_rows > 0) ? 'Reservation cancelled.' : 'Only pending reservations can be cancelled.';
    exit;
}

if ($action === 'create_invoice') {
    $student_name = trim($_POST['student_name'] ?? '');
    $amt = requirePositiveAmount($_POST['total_amount'] ?? 0, 'invoice amount');
    $date = date('Y-m-d H:i:s');

    if ($student_name === '') {
        echo 'Student name is required.';
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO invoices (student_name, total_amount, status, date_issued) VALUES (?, ?, 'unpaid', ?)");
    $stmt->bind_param("sds", $student_name, $amt, $date);

    if ($stmt->execute()) {
        $invoice_id = $conn->insert_id;
        // Accounting: Debit AR (5), Credit Revenue (6)
        recordJournalEntry("Student Invoice #$invoice_id - $student_name", 'Student_Invoice', $invoice_id, ACC_AR, ACC_REVENUE, $amt);
        adjustAccountBalance(ACC_AR, $amt);     // Asset increases with Debit (+)
        adjustAccountBalance(ACC_REVENUE, -$amt); // Revenue increases with Credit (-) in a signed balance system
        echo 'Invoice created successfully.';
    } else {
        echo 'Error creating invoice.';
    }
    exit;
}

if ($action === 'payment') {
    $inv_id = requirePositiveId($_POST['inv_id'] ?? ($_POST['invoice_id'] ?? 0), 'invoice');
    $pay_amt = requirePositiveAmount($_POST['pay_amt'] ?? ($_POST['amount'] ?? 0), 'payment amount');
    $method = trim($_POST['method'] ?? 'cash');

    $stmt = $conn->prepare("SELECT invoice_id, total_amount FROM invoices WHERE invoice_id = ?");
    $stmt->bind_param("i", $inv_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();

    if (!$invoice) {
        echo 'Invoice not found.';
        exit;
    }

    $existingPaidStmt = $conn->prepare("SELECT IFNULL(SUM(amount_paid), 0) AS total_paid FROM payments WHERE invoice_id = ?");
    $existingPaidStmt->bind_param("i", $inv_id);
    $existingPaidStmt->execute();
    $alreadyPaid = (float) ($existingPaidStmt->get_result()->fetch_assoc()['total_paid'] ?? 0);
    $remaining = (float) $invoice['total_amount'] - $alreadyPaid;

    if ($pay_amt > $remaining) {
        echo 'Payment amount exceeds remaining balance.';
        exit;
    }

    $insert = $conn->prepare("INSERT INTO payments (invoice_id, amount_paid, payment_method) VALUES (?, ?, ?)");
    $insert->bind_param("ids", $inv_id, $pay_amt, $method);

    if (!$insert->execute()) {
        echo 'Error processing payment.';
        exit;
    }

    if (!recordJournalEntry("Student Payment Receipt - Invoice #$inv_id", 'Student_Invoice', $inv_id, ACC_CASH, ACC_AR, $pay_amt)) {
        echo 'Error: Could not update financial ledger.';
        exit;
    }

    adjustAccountBalance(ACC_CASH, $pay_amt); // Increase Cash (+)
    adjustAccountBalance(ACC_AR, -$pay_amt);  // Decrease AR (-)

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
    $dept_id = requirePositiveId($_POST['dept_id'] ?? 0, 'department');
    $vendor_id = requirePositiveId($_POST['vendor_id'] ?? 0, 'vendor');
    $amount = requirePositiveAmount($_POST['amount'] ?? 0, 'purchase order amount');

    // Handle File Upload (Optional Feature)
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/po/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_info = pathinfo($_FILES['attachment']['name']);
        $ext = strtolower($file_info['extension']);
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (in_array($ext, $allowed) && $_FILES['attachment']['size'] <= 2 * 1024 * 1024) {
            $new_name = "PO_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $new_name)) {
                $attachment_path = 'uploads/po/' . $new_name;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO purchase_orders (dept_id, vendor_id, total_amount, status, attachment_path) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("iids", $dept_id, $vendor_id, $amount, $attachment_path);

    if (!$stmt->execute()) {
        echo 'Error: Database failed to generate Purchase Order.';
        exit;
    }

    $po_id = $conn->insert_id;
    $reservation = reserveBudget($dept_id, $amount, "PO #$po_id Reservation", $po_id);

    if ($reservation !== 'Reservation Successful') {
        $rollbackPo = $conn->prepare("DELETE FROM purchase_orders WHERE po_id = ?");
        $rollbackPo->bind_param("i", $po_id);
        $rollbackPo->execute();
        echo $reservation;
        exit;
    }

    echo 'Purchase Order created successfully and linked to a budget reservation.';
    exit;
}

if ($action === 'approve_po') {
    $po_id = requirePositiveId($_POST['po_id'] ?? 0, 'purchase order');
    $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'approved' WHERE po_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $po_id);

    if (!$stmt->execute()) {
        echo 'Database error occurred.';
        exit;
    }

    echo ($stmt->affected_rows > 0) ? 'success' : 'Only pending purchase orders can be approved.';
    exit;
}

if ($action === 'cancel_po') {
    $po_id = requirePositiveId($_POST['po_id'] ?? 0, 'purchase order');
    $conn->begin_transaction();

    try {
        $poStmt = $conn->prepare("UPDATE purchase_orders SET status = 'cancelled' WHERE po_id = ? AND status = 'pending'");
        $poStmt->bind_param("i", $po_id);
        $poStmt->execute();

        if ($poStmt->affected_rows <= 0) {
            $conn->rollback();
            echo 'Only pending purchase orders can be cancelled.';
            exit;
        }

        $resStmt = $conn->prepare("UPDATE budget_reservations SET status = 'cancelled' WHERE po_id = ? AND status = 'pending'");
        $resStmt->bind_param("i", $po_id);
        $resStmt->execute();

        $conn->commit();
        echo 'Purchase order cancelled successfully.';
    } catch (Throwable $e) {
        $conn->rollback();
        echo 'Unable to cancel purchase order.';
    }
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
    $ap_id = requirePositiveId($_POST['ap_id'] ?? 0, 'AP invoice');
    $amount = requirePositiveAmount($_POST['amount'] ?? 0, 'vendor payment');
    $method = trim($_POST['method'] ?? 'cash');
    echo recordVendorPayment($ap_id, $amount, $method);
    exit;
}

if ($action === 'delete_invoice') {
    $inv_id = requirePositiveId($_POST['inv_id'] ?? 0, 'invoice');

    $invStmt = $conn->prepare("SELECT invoice_id, total_amount, status FROM invoices WHERE invoice_id = ?");
    $invStmt->bind_param("i", $inv_id);
    $invStmt->execute();
    $invoice = $invStmt->get_result()->fetch_assoc();

    if (!$invoice) {
        echo 'Invoice not found.';
        exit;
    }

    if ($invoice['status'] !== 'unpaid') {
        echo 'Only unpaid invoices can be deleted.';
        exit;
    }

    $payStmt = $conn->prepare("SELECT COUNT(*) AS c FROM payments WHERE invoice_id = ?");
    $payStmt->bind_param("i", $inv_id);
    $payStmt->execute();
    $paymentCount = (int) ($payStmt->get_result()->fetch_assoc()['c'] ?? 0);

    if ($paymentCount > 0) {
        echo 'Invoice cannot be deleted because payments already exist.';
        exit;
    }

    $conn->begin_transaction();
    try {
        $jeIds = [];
        $jeStmt = $conn->prepare("SELECT entry_id FROM journal_entries WHERE reference_type = 'Student_Invoice' AND reference_id = ? AND description LIKE 'Student Invoice #%'");
        $jeStmt->bind_param("i", $inv_id);
        $jeStmt->execute();
        $jeResult = $jeStmt->get_result();
        while ($row = $jeResult->fetch_assoc()) {
            $jeIds[] = (int) $row['entry_id'];
        }

        foreach ($jeIds as $entryId) {
            $delLed = $conn->prepare("DELETE FROM ledger_details WHERE entry_id = ?");
            $delLed->bind_param("i", $entryId);
            $delLed->execute();

            $delJe = $conn->prepare("DELETE FROM journal_entries WHERE entry_id = ?");
            $delJe->bind_param("i", $entryId);
            $delJe->execute();
        }

        $amount = (float) $invoice['total_amount'];
        adjustAccountBalance(5, -$amount);
        adjustAccountBalance(6, -$amount);

        $delInv = $conn->prepare("DELETE FROM invoices WHERE invoice_id = ?");
        $delInv->bind_param("i", $inv_id);
        $delInv->execute();

        if ($delInv->affected_rows <= 0) {
            throw new RuntimeException('Invoice delete failed');
        }

        $conn->commit();
        echo 'Invoice deleted successfully.';
    } catch (Throwable $e) {
        $conn->rollback();
        echo 'Unable to delete invoice.';
    }
    exit;
}

if ($action === 'update_budget') {
    $dept_id = requirePositiveId($_POST['dept_id'] ?? 0, 'department');
    $new_budget = requirePositiveAmount($_POST['new_amount'] ?? 0, 'new budget');

    $stmt = $conn->prepare("UPDATE departments SET total_budget = ? WHERE dept_id = ?");
    $stmt->bind_param("di", $new_budget, $dept_id);
    echo $stmt->execute() ? 'Department budget adjusted manually.' : 'Update failed.';
    exit;
}

echo 'Invalid Request Action.';
?>
