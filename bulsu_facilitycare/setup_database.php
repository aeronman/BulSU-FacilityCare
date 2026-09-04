<?php
/**
 * BulSU FacilityCare - Database Setup Script
 * Run this script to initialize the database with schema and seed data.
 */

require_once __DIR__ . '/config/config.php';

echo "=== BulSU FacilityCare Database Setup ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "[1/3] Creating database tables...\n";

    $schema = file_get_contents(__DIR__ . '/sql/database.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        $pdo->exec($stmt);
    }
    echo "  Tables created successfully.\n\n";

    echo "[2/3] Inserting seed data...\n";

    $seed = file_get_contents(__DIR__ . '/sql/seed_data.sql');
    $statements = array_filter(array_map('trim', explode(';', $seed)));
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        $pdo->exec($stmt);
    }
    echo "  Seed data inserted successfully.\n\n";

    echo "[3/3] Setting up default users with correct passwords...\n";

    $users = [
        ['username' => 'admin', 'password' => 'admin123', 'role' => 'admin', 'email' => 'admin@bulsu.edu.ph'],
        ['username' => 'maintenance', 'password' => 'maintenance123', 'role' => 'maintenance', 'email' => 'maintenance@bulsu.edu.ph'],
        ['username' => 'john_maint', 'password' => 'maint1234', 'role' => 'maintenance', 'email' => 'john.maint@bulsu.edu.ph'],
        ['username' => 'student1', 'password' => 'user123', 'role' => 'student_staff', 'email' => 'student1@bulsu.edu.ph'],
        ['username' => 'faculty1', 'password' => 'user123', 'role' => 'student_staff', 'email' => 'faculty1@bulsu.edu.ph'],
        ['username' => 'staff1', 'password' => 'user123', 'role' => 'student_staff', 'email' => 'staff1@bulsu.edu.ph'],
    ];

    foreach ($users as $u) {
        $hash = password_hash($u['password'], PASSWORD_BCRYPT);
        $roleId = $pdo->query("SELECT id FROM roles WHERE name = '{$u['role']}'")->fetch()['id'];
        $userId = $pdo->query("SELECT id FROM users WHERE username = '{$u['username']}'")->fetch()['id'];

        $pdo->exec("UPDATE users SET password = '$hash' WHERE id = $userId");
        echo "  User '{$u['username']}' password set to '{$u['password']}'\n";
    }

    echo "\n=== Setup Complete ===\n";
    echo "\nDefault login credentials:\n";
    echo "  Admin:     admin / admin123\n";
    echo "  Maintenance: maintenance / maintenance123\n";
    echo "  User:      student1 / user123\n";
    echo "  Faculty:   faculty1 / user123\n";
    echo "  Staff:     staff1 / user123\n";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}
