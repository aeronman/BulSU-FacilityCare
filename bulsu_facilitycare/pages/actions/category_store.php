<?php
/**
 * Store Category - Admin POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/admin/categories');
}

$db = Database::getInstance();

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$criticalityWeight = (float)($_POST['criticality_weight'] ?? 1.00);
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (empty($name)) {
    $_SESSION['error_message'] = 'Category name is required.';
    redirect('/admin/categories');
}

$db->query(
    "INSERT INTO categories (name, description, criticality_weight, is_active)
     VALUES (:name, :description, :cw, :is_active)",
    [
        'name' => $name,
        'description' => $description,
        'cw' => $criticalityWeight,
        'is_active' => $is_active,
    ]
);

$_SESSION['success_message'] = 'Category added successfully.';
redirect('/admin/categories');
