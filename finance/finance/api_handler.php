<?php
/**
 * api_handler.php
 * Central Controller for all Financial Transactions
 */
include 'finance_logic.php';

// Ensure the request is coming via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- 1. BUDGETING: Reserve Funds ---
    if ($action === 'reserve') {
        $dept_id = intval($_POST['dept_id']);
        $amount  = floatval($_POST['amount']);
        $desc    = $_POST['desc'];
        
        // Calls logic from finance_logic.php
        echo reserveBudget($dept_id, $amount, $desc);
    } 
    
    // --- 2. STUDENT BILLING: Process Payments ---
    elseif ($action === 'payment') {
        $inv_id  = intval($_POST['inv_id']);
        $amount  = floatval($_POST['pay_amt']);
        
        $desc = "Student Payment Receipt - Invoice #$inv_id";
        
        // Automated Ledger Entry: 
        // Debit Account 1 (Cash/Bank) | Credit Account 2 (Accounts Receivable)
        $success = recordJournalEntry($desc, 'Student_Invoice', $inv_id, 1, 2, $amount);
        
        if ($success) {
            // Update the status of the invoice to 'paid'
            $conn->query("UPDATE invoices SET status = 'paid' WHERE invoice_id = $inv_id");
            echo "Payment Processed. Double-entry ledger (Cash & AR) updated.";
        } else {
            echo "Error: Could not update financial ledger.";
        }
    }

    // --- 3. PROCUREMENT: Create New Purchase Order ---
    elseif ($action === 'create_po') {
        $dept_id = intval($_POST['dept_id']);
        $vendor_id = intval($_POST['vendor_id']);
        $amount = floatval($_POST['amount']);
        
        $stmt = $conn->prepare("INSERT INTO purchase_orders (dept_id, vendor_id, total_amount, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iid", $dept_id, $vendor_id, $amount);
        
        if ($stmt->execute()) {
            echo "Purchase Order created successfully and set to 'Pending' for approval.";
        } else {
            echo "Error: Database failed to generate Purchase Order.";
        }
    }

    // --- 4. PROCUREMENT: Mark PO as Received & Post AP ---
    elseif ($action === 'receive_po') {
        $po_id = intval($_POST['po_id']);
        
        // We include procurement_logic.php specifically for this complex action
        if (file_exists('procurement_logic.php')) {
            include 'procurement_logic.php';
            echo processPurchaseOrder($po_id);
        } else {
            echo "Error: Procurement logic module is missing.";
        }
    }

    // --- 5. ADMIN: Manual Overrides ---
    elseif ($action === 'update_budget') {
        // Restricted to Admin role check (handled via logic file usually)
        $dept_id = intval($_POST['dept_id']);
        $new_budget = floatval($_POST['new_amount']);
        
        $stmt = $conn->prepare("UPDATE departments SET total_budget = ? WHERE dept_id = ?");
        $stmt->bind_param("di", $new_budget, $dept_id);
        echo $stmt->execute() ? "Department budget adjusted manually." : "Update failed.";
    }

    else {
        echo "Invalid Request Action.";
    }
} else {
    echo "Direct access denied. Only POST requests allowed.";
}
// Inside your switch/if-else logic in api_handler.php
if (isset($_POST['action']) && $_POST['action'] == 'approve_reservation') {
    $res_id = $_POST['res_id'];
    
    // Update status to 'approved'
    $stmt = $conn->prepare("UPDATE budget_reservations SET status = 'approved' WHERE res_id = ?");
    $stmt->bind_param("i", $res_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Database error occurred.";
    }
    $stmt->close();
    exit;
}
// Add this inside api_handler.php
if (isset($_POST['action']) && $_POST['action'] == 'process_payment') {
    $invoice_id = $_POST['invoice_id'];
    $payment_amount = $_POST['amount'];
    $method = $_POST['method'];

    // 1. Update Invoice Table
    $stmt = $conn->prepare("UPDATE invoices SET amount_paid = amount_paid + ?, status = 
                            CASE 
                                WHEN (amount_paid + ?) >= total_amount THEN 'paid' 
                                ELSE 'partial' 
                            END 
                            WHERE id = ?");
    $stmt->bind_param("ddi", $payment_amount, $payment_amount, $invoice_id);
    
    if ($stmt->execute()) {
        // 2. Ledger Update: Increase Cash (Asset)
        $conn->query("UPDATE chart_of_accounts SET balance = balance + $payment_amount WHERE account_name = 'Cash'");
        
        // 3. Ledger Update: Decrease Accounts Receivable (Asset)
        $conn->query("UPDATE chart_of_accounts SET balance = balance - $payment_amount WHERE account_name = 'Accounts Receivable'");
        
        echo "success";
    } else {
        echo "Database error.";
    }
    $stmt->close();
    exit;
}
// Add this to your existing api_handler.php

if (isset($_POST['action'])) {
    
    // ACTION: Create a New Invoice
    if ($_POST['action'] == 'create_invoice') {
        $name = $_POST['student_name'];
        $amt = $_POST['total_amount'];
        $date = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO invoices (student_name, total_amount, status, date_issued) VALUES (?, ?, 'unpaid', ?)");
        $stmt->bind_param("sds", $name, $amt, $date);
        
        if ($stmt->execute()) {
            // Accounting: Increase Accounts Receivable (Asset) on the Ledger
            $conn->query("UPDATE chart_of_accounts SET balance = balance + $amt WHERE account_name = 'Accounts Receivable'");
            header("Location: billing_view.php?success=created");
        }
        exit;
    }

    // ACTION: Record a Payment (The logic for your Modal)
    if ($_POST['action'] == 'payment') {
        $inv_id = $_POST['inv_id'];
        $pay_amt = $_POST['pay_amt'];

        // 1. Mark Invoice as Paid
        $stmt = $conn->prepare("UPDATE invoices SET status = 'paid' WHERE invoice_id = ?");
        $stmt->bind_param("i", $inv_id);
        
        if ($stmt->execute()) {
            // 2. DOUBLE ENTRY BOOKKEEPING
            // A. Increase Cash (Debit Asset)
            $conn->query("UPDATE chart_of_accounts SET balance = balance + $pay_amt WHERE account_name = 'Cash'");
            
            // B. Decrease Accounts Receivable (Credit Asset)
            $conn->query("UPDATE chart_of_accounts SET balance = balance - $pay_amt WHERE account_name = 'Accounts Receivable'");
            
            echo "Payment Posted Successfully. Ledger Adjusted: Cash (+), AR (-).";
        } else {
            echo "Error processing payment.";
        }
        exit;
    }
}

?>
