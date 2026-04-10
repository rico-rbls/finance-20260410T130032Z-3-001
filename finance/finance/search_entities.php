<?php
/**
 * search_entities.php
 * Endpoint for fetching real-time search recommendations (Students, POs, etc.)
 */
include 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: json/application');
    echo json_encode([]);
    exit;
}

$query = (isset($_GET['q']) ? trim($_GET['q']) : '');
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];
$searchTerm = "%$query%";

// 1. Search Students/Invoices (Only if query is non-numeric or long enough)
$stmt = $conn->prepare("SELECT DISTINCT student_name FROM invoices WHERE student_name LIKE ? LIMIT 5");
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'label' => $row['student_name'],
        'type' => 'Student',
        'link' => 'billing_view.php?search=' . urlencode($row['student_name']),
        'icon' => 'bi-person'
    ];
}

// 2. Search Specific Invoices by ID
if (is_numeric($query)) {
    $stmt = $conn->prepare("SELECT invoice_id, student_name FROM invoices WHERE invoice_id = ? LIMIT 1");
    $stmt->bind_param("i", $query);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $results[] = [
            'label' => 'Invoice #' . $row['invoice_id'] . ' (' . $row['student_name'] . ')',
            'type' => 'Record',
            'link' => 'billing_view.php?search=' . $row['invoice_id'],
            'icon' => 'bi-receipt'
        ];
    }
}

// 3. Search POs by ID
if (is_numeric($query)) {
    $stmt = $conn->prepare("SELECT po_id FROM purchase_orders WHERE po_id = ? LIMIT 1");
    $stmt->bind_param("i", $query);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $results[] = [
            'label' => 'Purchase Order #' . $row['po_id'],
            'type' => 'Procurement',
            'link' => 'procurement.php?search=' . $row['po_id'],
            'icon' => 'bi-truck'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($results);
exit;
