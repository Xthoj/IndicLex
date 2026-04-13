<?php
/**
 * public/catalog.php
 * Lists all active dictionaries using professor's schema.
 */
require_once '../config/database.php';

$stmt = $conn->query(
    "SELECT dict_id, name, type, source_lang_1, source_lang_2, source_lang_3,
     description, entry_count, created_at
     FROM dictionaries
     WHERE is_active = 1
     ORDER BY created_at DESC"
);
$dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<style>
.catalog-page {
    padding-top: 120px;
    padding-bottom: 100px;
    min-height: 100vh;
}

.catalog-wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.catalog-wrap h2 {
    margin-bottom: 1.5rem;
}

.dict-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem 1.2rem;
    margin-bottom: 1rem;
    background: #fff;
    color: #111827;
}

.dict-card h5 {
    margin: 0 0 .4rem;
    font-size: 1.1rem;
    color: #111827;
}

.dict-card p {
    color: #374151;
}

.dict-type {
    display: inline-block;
    font-size: .75rem;
    padding: .15rem .5rem;
    border-radius: 999px;
    margin-bottom: .4rem;
    font-weight: bold;
}

.type-bilingual {
    background: #dbeafe;
    color: #1e40af;
}

.type-trilingual {
    background: #ede9fe;
    color: #5b21b6;
}

.lang-tags {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
    margin: .4rem 0;
}

.lang-tag {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: .8rem;
    padding: .1rem .5rem;
    color: #374151;
}

.entry-count {
    font-size: .85rem;
    color: #6b7280;
}
</style>

<div class="catalog-page">
  <div class="catalog-wrap">
<h2>📚 Dictionary Catalog</h2>

 <?php if (empty($dictionaries)): ?>
  <p>No dictionaries found. <a href="upload_xlsx.php">Import one to get started.</a></p>
    <?php else: ?>
        <?php foreach ($dictionaries as $d): ?>
            <div class="dict-card">
                <h5><?= htmlspecialchars($d['name']) ?></h5>

                <span class="dict-type type-<?= htmlspecialchars($d['type']) ?>">
                    <?= ucfirst(htmlspecialchars($d['type'])) ?>
                </span>

                <div class="lang-tags">
                    <?php if (!empty($d['source_lang_1'])): ?>
                        <span class="lang-tag"><?= htmlspecialchars($d['source_lang_1']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($d['source_lang_2'])): ?>
                        <span class="lang-tag"><?= htmlspecialchars($d['source_lang_2']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($d['source_lang_3'])): ?>
                        <span class="lang-tag"><?= htmlspecialchars($d['source_lang_3']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($d['description'])): ?>
                    <p style="margin:.4rem 0 .3rem; font-size:.9rem">
                        <?= htmlspecialchars($d['description']) ?>
                    </p>
                <?php endif; ?>

                <div class="entry-count">
                    <?= number_format((int)$d['entry_count']) ?> entries
                    <?php if (!empty($d['created_at'])): ?>
                        &middot; Added <?= date('M d, Y', strtotime($d['created_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p style="margin-top:1.5rem">
        <a href="upload_xlsx.php">→ Import a dictionary</a> &nbsp;|&nbsp;
        <a href="search.php">→ Search</a>
    </p>
</div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/footer.php'; ?>