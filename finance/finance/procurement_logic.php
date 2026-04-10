<?php
include 'db_connect.php';
include_once 'finance_logic.php'; // To use the recordJournalEntry function

function processPurchaseOrder($po_id) {
    global $conn;

    // 1. Get PO details
    $sql = "SELECT * FROM purchase_orders WHERE po_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();

    if ($po['status'] == 'approved') {
        $amount = (float) $po['total_amount'];

        $desc = "PO #$po_id Received - Vendor: " . $po['vendor_id'];
        // Accounting: Debit Expense/Assets (10), Credit AP (8)
        $ledger_success = recordJournalEntry($desc, 'Purchase_Order', $po_id, ACC_SUPPLIES_EXP, ACC_AP, $amount);

        if ($ledger_success) {
            adjustAccountBalance(ACC_SUPPLIES_EXP, $amount); // Expense Increases (+)
            adjustAccountBalance(ACC_AP, -$amount);          // Liability Increases (-) in credit-negative system

            $ap_id = createApInvoice($po_id, $po['vendor_id'], $po['dept_id'], $amount);

            $updatePO = $conn->prepare("UPDATE purchase_orders SET status = 'received' WHERE po_id = ?");
            $updatePO->bind_param("i", $po_id);
            $updatePO->execute();

            $updateRes = $conn->prepare("UPDATE budget_reservations SET status = 'committed' WHERE po_id = ?");
            $updateRes->bind_param("i", $po_id);
            $updateRes->execute();

            return $ap_id ? "PO Received: AP invoice generated and ledger updated." : "PO Received: Ledger updated, but AP invoice could not be created.";
        }
    }
    return "Error: PO must be in 'approved' status to receive.";
}
?>
