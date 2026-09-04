<?php
/**
 * Store Facility - POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/admin/facilities');
}

$func = new Functions();
$db = Database::getInstance();

$id = $_POST['id'] ?? null;
$data = [
    'building' => trim($_POST['building'] ?? ''),
    'floor' => trim($_POST['floor'] ?? ''),
    'room_number' => trim($_POST['room_number'] ?? ''),
    'location_name' => trim($_POST['location_name'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'criticality_weight' => (float)($_POST['criticality_weight'] ?? 1.00),
    'is_active' => isset($_POST['is_active']) ? 1 : 0,
];

if (empty($data['building']) || empty($data['location_name'])) {
    $_SESSION['error_message'] = 'Building and Location Name are required.';
    redirect('/admin/facilities');
}

if ($id) {
    $db->query(
        "UPDATE facilities SET building = :building, floor = :floor, room_number = :room_number,
         location_name = :location_name, description = :description,
         criticality_weight = :cw, is_active = :is_active WHERE id = :id",
        [
            'building' => $data['building'], 'floor' => $data['floor'],
            'room_number' => $data['room_number'], 'location_name' => $data['location_name'],
            'description' => $data['description'], 'cw' => $data['criticality_weight'],
            'is_active' => $data['is_active'], 'id' => $id,
        ]
    );
    $_SESSION['success_message'] = 'Facility updated successfully.';
} else {
    $db->query(
        "INSERT INTO facilities (building, floor, room_number, location_name, description, criticality_weight, is_active)
         VALUES (:building, :floor, :room_number, :location_name, :description, :cw, :is_active)",
        [
            'building' => $data['building'], 'floor' => $data['floor'],
            'room_number' => $data['room_number'], 'location_name' => $data['location_name'],
            'description' => $data['description'], 'cw' => $data['criticality_weight'],
            'is_active' => $data['is_active'],
        ]
    );
    $_SESSION['success_message'] = 'Facility added successfully.';
}

redirect('/admin/facilities');
