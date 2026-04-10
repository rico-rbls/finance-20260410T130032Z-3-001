<?php
include 'db_connect.php';

function test($scenario, $success) {
    echo $success ? "✅ [PASS] $scenario<br>" : "❌ [FAIL] $scenario<br>";
}

echo "<h2>UniFinance System: Automated CRUD & Logic Audit</h2>";

// 1. TEST: Student Invoice CRUD (Create/Read)
$student_test = "Test Student " . time();
$amt = 1500.00;
$stmt = $conn->prepare("INSERT INTO invoices (student_name, total_amount, status) VALUES (?, ?, 'unpaid')");
$stmt->bind_param("sd", $student_test, $amt);
$stmt->execute();
$inv_id = $conn->insert_id;

$stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
$stmt->bind_param("i", $inv_id);
$stmt->execute();
$check_inv = $stmt->get_result()->fetch_assoc();
test("Create Student Invoice", ($check_inv['student_name'] === $student_test));

// 2. TEST: Budget Reservation & Department Logic
$dept_id = 1; // CCSE
$initial_budget = $conn->query("SELECT total_budget FROM departments WHERE dept_id = $dept_id")->fetch_assoc()['total_budget'];
$res_amt = 500.00;
$conn->query("INSERT INTO budget_reservations (dept_id, description, amount_reserved, status) VALUES ($dept_id, 'Audit Test', $res_amt, 'pending')");
$res_id = $conn->insert_id;
test("Budget Reservation Entry", ($res_id > 0));

// 3. TEST: Purchase Order workflow
$po_amt = 2500.00;
$conn->query("INSERT INTO purchase_orders (dept_id, vendor_id, total_amount, status) VALUES ($dept_id, 1, $po_amt, 'pending')");
$po_id = $conn->insert_id;
test("Create Purchase Order", ($po_id > 0));

// 4. TEST: Ledger Integrity (Accounting Balance Check)
// Check if Cash is an Asset and Accounts Payable is a Liability
$cash_acc = $conn->query("SELECT * FROM chart_of_accounts WHERE account_id = 4")->fetch_assoc();
$ap_acc = $conn->query("SELECT * FROM chart_of_accounts WHERE account_id = 3")->fetch_assoc();
test("Ledger Config: Cash is Asset", ($cash_acc['account_type'] === 'Asset'));
test("Ledger Config: AP is Liability", ($ap_acc['account_type'] === 'Liability'));

// 5. TEST: File Upload Path (Structural check)
$upload_dir = __DIR__ . '/uploads/po/';
test("Upload Directory Existence", is_dir($upload_dir));

// CLEANUP (Optional for non-destructive tests but recommended)
$conn->query("DELETE FROM invoices WHERE invoice_id = $inv_id");
$conn->query("DELETE FROM budget_reservations WHERE res_id = $res_id");
$conn->query("DELETE FROM purchase_orders WHERE po_id = $po_id");

echo "<br><strong>Audit Results Complete.</strong>";
?>
