<?php
include 'db_connect.php';

echo "<h3>Seeding Mock Data for UniFinance System...</h3>";

// 1. Seed Vendors
$vendors = [
    ['Global Tech Solutions', 'support@gt-solutions.com'],
    ['Elite Office Supplies', 'sales@eliteoffices.com'],
    ['SafeTravel Logistics', 'booking@safetravel.ph']
];

foreach ($vendors as $v) {
    $stmt = $conn->prepare("INSERT IGNORE INTO vendors (vendor_name, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $v[0], $v[1]);
    $stmt->execute();
}
echo "✔️ Vendors seeded.<br>";

// 2. Seed Departments (if not already present with total_budget)
$depts = [
    ['College of Computing Science and Engineering', 1000000.00],
    ['College of Tourism and Hospitality', 500000.00],
    ['College of Human Kinetics', 350000.00],
    ['College of Teacher Education', 250000.00],
    ['College of Business Administration', 600000.00]
];

foreach ($depts as $d) {
    $stmt = $conn->prepare("INSERT IGNORE INTO departments (dept_name, total_budget) VALUES (?, ?)");
    $stmt->bind_param("sd", $d[0], $d[1]);
    $stmt->execute();
}
echo "✔️ Departments synced.<br>";

// 3. Seed Invoices (Student Billing)
$students = ['Alice Walker', 'Bob Jenkins', 'Charlie Davis', 'Diana Prince'];
foreach ($students as $s) {
    $amt = rand(5000, 25000);
    $conn->query("INSERT INTO invoices (student_name, total_amount, status, date_issued) VALUES ('$s', $amt, 'unpaid', NOW())");
}
echo "✔️ Student Invoices seeded.<br>";

// 4. Seed Purchase Orders (Procurement)
$pos = [
    [1, 1, 15000.00, 'approved'],
    [2, 2, 5000.00, 'pending'],
    [3, 3, 12000.00, 'received']
];

foreach ($pos as $p) {
    $conn->query("INSERT INTO purchase_orders (dept_id, vendor_id, total_amount, status) VALUES ({$p[0]}, {$p[1]}, {$p[2]}, '{$p[3]}')");
}
echo "✔️ Purchase Orders seeded.<br>";

// 5. Seed Budget Reservations
$reservations = [
    [1, 'Cloud Server Hosting Annual Subscription', 45000.00, 'committed'],
    [2, 'Culinary Lab Modernization Equipment', 120000.00, 'pending'],
    [3, 'Athletic Gear for Varsity Sports', 32000.00, 'cancelled']
];

foreach ($reservations as $r) {
    $conn->query("INSERT INTO budget_reservations (dept_id, description, amount_reserved, status) VALUES ({$r[0]}, '{$r[1]}', {$r[2]}, '{$r[3]}')");
}
echo "✔️ Budget Reservations seeded.<br>";

echo "<br><strong>Mock Data Injection Complete.</strong>";
?>
