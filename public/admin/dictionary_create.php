<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_dictionaries.php');
    exit;
}

$dict_identifier = trim($_POST['dict_identifier'] ?? '');
$name = trim($_POST['name'] ?? '');
$type = trim($_POST['type'] ?? '');
$source_lang_1 = trim($_POST['source_lang_1'] ?? '');
$description = trim($_POST['description'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($dict_identifier === '' || $name === '' || $type === '' || $source_lang_1 === '') {
    header('Location: manage_dictionaries.php?error=Please fill in all required fields');
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO dictionaries (dict_identifier, name, type, source_lang_1, description, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $dict_identifier,
        $name,
        $type,
        $source_lang_1,
        $description,
        $is_active
    ]);

    header('Location: manage_dictionaries.php?success=Dictionary created successfully');
    exit;
} catch (PDOException $e) {
    header('Location: manage_dictionaries.php?error=Failed to create dictionary');
    exit;
}