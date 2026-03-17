<?php
require_once '../config/database.php';

$stmt = $conn->query("
    SELECT name, description, created_at
    FROM dictionaries
    ORDER BY created_at DESC
");

$dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (count($dictionaries) > 0): ?>
  <?php foreach ($dictionaries as $row): ?>

    <div class="card mb-3">
      <div class="card-body">

        <h5><?php echo htmlspecialchars($row['name']); ?></h5>

        <?php if (!empty($row['description'])): ?>
          <p><?php echo htmlspecialchars($row['description']); ?></p>
        <?php endif; ?>

        <?php if (!empty($row['created_at'])): ?>
          <small class="text-muted">
            Created on: <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
          </small>
        <?php endif; ?>

      </div>
    </div>

  <?php endforeach; ?>
<?php else: ?>
  <p>No dictionaries found.</p>
<?php endif; ?>