<?php
include 'db_connect.php';

// Array of users to create
// Format: Username => [Password, Full Name, Role]
$usersToCreate = [
    'admin_user'   => ['admin123', 'Finance Director', 'admin'],
    'officer_one'  => ['staff456', 'Accountant Smith', 'finance_officer'],
    'head_it'      => ['dept789', 'IT Dept Head', 'dept_head'],
    'student_test' => ['student999', 'John Doe', 'student']
];

echo "<h3>Initializing System Users...</h3>";

foreach ($usersToCreate as $username => $info) {
    // 1. Hash the password
    $hashedPassword = password_hash($info[0], PASSWORD_DEFAULT);
    $fullName = $info[1];
    $role = $info[2];

    // 2. Check if user already exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        // 3. Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashedPassword, $fullName, $role);
        
        if ($stmt->execute()) {
            echo "<p style='color:green;'>[SUCCESS] Created $role: <strong>$username</strong> (Password: {$info[0]})</p>";
        } else {
            echo "<p style='color:red;'>[ERROR] Could not create $username</p>";
        }
    } else {
        echo "<p style='color:orange;'>[SKIP] User <strong>$username</strong> already exists.</p>";
    }
}

echo "<hr><p><strong>Security Note:</strong> Delete this file after use to prevent unauthorized user creation.</p>";
?>
