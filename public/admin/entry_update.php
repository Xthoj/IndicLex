<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

$entry_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$dict_id = (int)($_GET['dict_id'] ?? $_POST['dict_id'] ?? 0);

if ($entry_id <= 0 || $dict_id <= 0) {
    header("Location: manage_dictionaries.php?error=Invalid entry");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang_1 = trim($_POST['lang_1'] ?? '');
    $lang_2 = trim($_POST['lang_2'] ?? '');
    $lang_3 = trim($_POST['lang_3'] ?? '');
    $pronunciation = trim($_POST['pronunciation'] ?? '');
    $part_of_speech = trim($_POST['part_of_speech'] ?? '');
    $example = trim($_POST['example'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE dictionary_entries
        SET
            lang_1 = ?,
            lang_2 = ?,
            lang_3 = ?,
            pronunciation = ?,
            part_of_speech = ?,
            example = ?,
            notes = ?,
            is_active = ?
        WHERE entry_id = ?
    ");

    $stmt->execute([
        $lang_1,
        $lang_2,
        $lang_3,
        $pronunciation,
        $part_of_speech,
        $example,
        $notes,
        $is_active,
        $entry_id
    ]);

    header("Location: manage_entries.php?dict_id=$dict_id&success=Entry updated successfully");
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM dictionary_entries
    WHERE entry_id = ?
");
$stmt->execute([$entry_id]);

$entry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entry) {
    header("Location: manage_entries.php?dict_id=$dict_id&error=Entry not found");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
        }

        h1 {
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 80px;
        }

        .checkbox-row {
            margin-top: 15px;
        }

        button {
            margin-top: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        a.back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-link" href="manage_entries.php?dict_id=<?php echo $dict_id; ?>">
            ← Back to Entries
        </a>

        <h1>Edit Entry</h1>

        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $entry['entry_id']; ?>">
            <input type="hidden" name="dict_id" value="<?php echo $dict_id; ?>">

            <label>Language 1</label>
            <input type="text" name="lang_1" value="<?php echo htmlspecialchars($entry['lang_1'] ?? ''); ?>" required>

            <label>Language 2</label>
            <input type="text" name="lang_2" value="<?php echo htmlspecialchars($entry['lang_2'] ?? ''); ?>">

            <label>Language 3</label>
            <input type="text" name="lang_3" value="<?php echo htmlspecialchars($entry['lang_3'] ?? ''); ?>">

            <label>Pronunciation</label>
            <input type="text" name="pronunciation" value="<?php echo htmlspecialchars($entry['pronunciation'] ?? ''); ?>">

            <label>Part of Speech</label>
            <input type="text" name="part_of_speech" value="<?php echo htmlspecialchars($entry['part_of_speech'] ?? ''); ?>">

            <label>Example</label>
            <textarea name="example"><?php echo htmlspecialchars($entry['example'] ?? ''); ?></textarea>

            <label>Notes</label>
            <textarea name="notes"><?php echo htmlspecialchars($entry['notes'] ?? ''); ?></textarea>

            <div class="checkbox-row">
                <label>
                    <input type="checkbox" name="is_active" value="1"
                        <?php echo !empty($entry['is_active']) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <button type="submit">Update Entry</button>
        </form>
    </div>
</body>
</html>