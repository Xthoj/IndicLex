<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/public/admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_username = trim($_POST['username'] ?? '');
    $login_password = $_POST['password'] ?? '';

    try {
        $stmt = $conn->prepare("
            SELECT admin_id, username, password_hash, role
            FROM admin_users
            WHERE username = :username
            LIMIT 1
        ");
        $stmt->execute([':username' => $login_username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($login_password, $admin['password_hash'])) {
            $_SESSION['admin_id']       = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role']     = $admin['role'];

            header('Location: ' . BASE_URL . '/public/admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

  <div class="login-container">
    <h1>Admin Login</h1>

    <?php if (!empty($error)): ?>
      <div class="error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>

      <button type="submit">Login</button>
    </form>

    <a class="back-link" href="../index.php">← Back to Home</a>
  </div>

<?php include '../../includes/footer.php'; ?>