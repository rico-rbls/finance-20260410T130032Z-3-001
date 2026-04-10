<?php
include 'db_connect.php';
include 'finance_logic.php'; // To use the recordJournalEntry function

function processPurchaseOrder($po_id) {
    global $conn;

    // 1. Get PO details
    $sql = "SELECT * FROM purchase_orders WHERE po_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();

    if ($po['status'] == 'approved') {
        $amount = $po['total_amount'];
        
        // 2. Automate Journal Entry: Debit Expense (Account ID 4), Credit AP (Account ID 3)
        // Note: Assumes Account 3 is Accounts Payable, Account 4 is General Expense
        $desc = "PO #$po_id Received - Vendor ID: " . $po['vendor_id'];
        $ledger_success = recordJournalEntry($desc, 'Purchase_Order', $po_id, 4, 3, $amount);

        if ($ledger_success) {
            // 3. Update PO status to 'received'
            $conn->query("UPDATE purchase_orders SET status = 'received' WHERE po_id = $po_id");
            
            // 4. Finalize the Budget Reservation (Commit it)
            $conn->query("UPDATE budget_reservations SET status = 'committed' 
                          WHERE dept_id = {$po['dept_id']} AND description LIKE '%PO #$po_id%'");
            
            return "PO Received: Inventory Updated & AP Invoice Generated.";
        }
    }
    return "Error: PO must be in 'approved' status to receive.";
}
?>
