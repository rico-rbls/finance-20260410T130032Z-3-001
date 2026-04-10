<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "finance_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * System-Wide Financial Account Constants
 * Matches the finance_db.sql indices for robustness
 */
if (!defined('ACC_CASH')) define('ACC_CASH', 4);
if (!defined('ACC_AR')) define('ACC_AR', 5);
if (!defined('ACC_REVENUE')) define('ACC_REVENUE', 6);
if (!defined('ACC_AP')) define('ACC_AP', 8);
if (!defined('ACC_SUPPLIES_EXP')) define('ACC_SUPPLIES_EXP', 10);
?>
