<?php
require_once 'config/database.php';

$stmt = $conn->query("SELECT * FROM dictionaries");
$dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (count($dictionaries) > 0): ?>
  <?php foreach ($dictionaries as $row): ?>

    <div class="card mb-3">
      <div class="card-body">
        <h5><?php echo $row['name']; ?></h5>
        <p>
          <?php echo $row['source_language']; ?>
          →
          <?php echo $row['target_language']; ?>
        </p>
      </div>
    </div>

  <?php endforeach; ?>
<?php else: ?>
  <p>No dictionaries found.</p>
<?php endif; ?>