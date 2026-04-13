<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdmin();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: ' . BASE_URL . '/public/admin/manage_dictionaries.php?error=Invalid dictionary ID');
    exit;
}

$stmt = $conn->prepare("
    SELECT dict_id, dict_identifier, name, type, source_lang_1, description, is_active
    FROM dictionaries WHERE dict_id = ?
");
$stmt->execute([$id]);
$dictionary = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dictionary) {
    header('Location: ' . BASE_URL . '/public/admin/manage_dictionaries.php?error=Dictionary not found');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dict_identifier = trim($_POST['dict_identifier'] ?? '');
    $name            = trim($_POST['name']            ?? '');
    $type            = trim($_POST['type']            ?? '');
    $source_lang_1   = trim($_POST['source_lang_1']   ?? '');
    $description     = trim($_POST['description']     ?? '');
    $is_active       = isset($_POST['is_active']) ? 1 : 0;

    if ($dict_identifier === '' || $name === '' || $type === '' || $source_lang_1 === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $conn->prepare("
            UPDATE dictionaries
            SET dict_identifier = ?, name = ?, type = ?, source_lang_1 = ?, description = ?, is_active = ?
            WHERE dict_id = ?
        ")->execute([$dict_identifier, $name, $type, $source_lang_1, $description, $is_active, $id]);

        header('Location: ' . BASE_URL . '/public/admin/manage_dictionaries.php?success=Dictionary updated successfully');
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    .container { width: 90%; max-width: 900px; margin: 30px auto; }
    .form-box { background: white; padding: 20px; border-radius: 8px; }
    input[type="text"], textarea { width: 100%; max-width: 500px; padding: 8px; margin-top: 5px; margin-bottom: 15px; }
    .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
    .checkbox-row input[type="checkbox"] { width: auto; margin: 0; }
    button { background: #007bff; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
    .top-links { margin-bottom: 20px; }
    .top-links a { text-decoration: none; margin-right: 15px; color: #007bff; font-weight: bold; }
    .error { color: red; font-weight: bold; margin-bottom: 15px; }
    body.dark .form-box { background: #1e1e1e; color: white; }
    body.dark input[type="text"], body.dark textarea, body.dark select { background: #2d2d2d; color: white; border: 1px solid #444; }
    body.dark .top-links a { color: #60a5fa; }
</style>

<div class="container">
    <h1>Edit Dictionary</h1>

    <div class="top-links">
        <a href="manage_dictionaries.php">← Back to Manage Dictionaries</a>
        <a href="logout.php">Logout</a>
    </div>

    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <div class="form-box">
        <form method="POST">
            <label>Identifier:</label><br>
            <input type="text" name="dict_identifier" value="<?php echo htmlspecialchars($dictionary['dict_identifier'] ?? ''); ?>" required><br>

            <label>Name:</label><br>
            <input type="text" name="name" value="<?php echo htmlspecialchars($dictionary['name'] ?? ''); ?>" required><br>

            <label>Type:</label><br>
            <select name="type" required style="width:100%; max-width:500px; padding:8px; margin-top:5px; margin-bottom:15px; box-sizing:border-box;">
                <option value="bilingual"  <?php echo ($dictionary['type'] ?? '') === 'bilingual'  ? 'selected' : ''; ?>>Bilingual</option>
                <option value="trilingual" <?php echo ($dictionary['type'] ?? '') === 'trilingual' ? 'selected' : ''; ?>>Trilingual</option>
            </select><br>

            <label>Source Language:</label><br>
            <input type="text" name="source_lang_1" value="<?php echo htmlspecialchars($dictionary['source_lang_1'] ?? ''); ?>" required><br>

            <label>Description:</label><br>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($dictionary['description'] ?? ''); ?></textarea><br>

            <div class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" <?php echo !empty($dictionary['is_active']) ? 'checked' : ''; ?>>
                <label style="margin: 0;">Active</label>
            </div>

            <button type="submit">Update Dictionary</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
