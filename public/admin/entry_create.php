<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_dictionaries.php');
    exit;
}

$dictionary_id = (int) ($_POST['dictionary_id'] ?? 0);
$word = trim($_POST['word'] ?? '');
$part_of_speech = trim($_POST['part_of_speech'] ?? '');
$meaning = trim($_POST['meaning'] ?? '');
$translation1 = trim($_POST['translation1'] ?? '');
$translation2 = trim($_POST['translation2'] ?? '');

if ($dictionary_id <= 0 || $word === '' || $meaning === '') {
    header('Location: manage_entries.php?dictionary_id=' . $dictionary_id . '&error=Please fill in all required fields');
    exit;
}

try {
    $sql = "INSERT INTO dictionary_entries
            (dictionary_id, word, part_of_speech, meaning, translation1, translation2, created_at)
            VALUES
            (:dictionary_id, :word, :part_of_speech, :meaning, :translation1, :translation2, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':dictionary_id' => $dictionary_id,
        ':word' => $word,
        ':part_of_speech' => $part_of_speech,
        ':meaning' => $meaning,
        ':translation1' => $translation1,
        ':translation2' => $translation2
    ]);

    header('Location: manage_entries.php?dictionary_id=' . $dictionary_id . '&success=Entry created successfully');
    exit;
} catch (PDOException $e) {
    header('Location: manage_entries.php?dictionary_id=' . $dictionary_id . '&error=Failed to create entry');
    exit;
}
?>