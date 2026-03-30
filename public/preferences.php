<?php
/**
 * public/preferences.php  —  Iteration 5: Preferences & Cookie Management
 *
 * Handles:
 *   GET  -> display current preferences (resolved: cookie -> DB -> safety)
 *   POST -> save posted values as cookies, redirect back (PRG pattern)
 */
require_once '../config/database.php';
require_once '../includes/preferences_helper.php';

// Load dictionaries for the "Default Dictionary" dropdown
$dictionaries = [];
try {
    $dict_stmt = $conn->query(
        "SELECT dict_id, name FROM dictionaries WHERE is_active = 1 ORDER BY name"
    );
    $dictionaries = $dict_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* table missing in dev -- skip */ }

// POST: save preferences and redirect (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $theme = in_array($_POST['theme'] ?? '', ['light', 'dark'], true)
           ? $_POST['theme'] : 'light';
    save_preference('theme', $theme);

    $allowed_rpp = ['5', '10', '20', '50'];
    $rpp = in_array($_POST['results_per_page'] ?? '', $allowed_rpp, true)
         ? $_POST['results_per_page'] : '10';
    save_preference('results_per_page', $rpp);

    $dd = $_POST['default_dict'] ?? 'all';
    if ($dd !== 'all' && !ctype_digit($dd)) $dd = 'all';
    save_preference('default_dict', $dd);

    header('Location: preferences.php?saved=1');
    exit;
}

// GET: resolve and display
$prefs = load_preferences($conn);
$saved = isset($_GET['saved']);

include '../includes/header.php';
?>
<style>
.pref-wrap {
  max-width: 640px;
  margin: 6rem auto 4rem;
  padding: 0 1.5rem;
}
.pref-wrap h2 { font-size: 1.6rem; margin-bottom: .4rem; }
.pref-wrap .subtitle { color: #6b7280; font-size: .9rem; margin-bottom: 1.8rem; }
body.dark .pref-wrap .subtitle { color: #9ca3af; }

.pref-card {
  background: var(--bg);
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 1.8rem 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
body.dark .pref-card { border-color: #374151; box-shadow: 0 2px 8px rgba(0,0,0,.4); }

.pref-field { margin-bottom: 1.6rem; }
.pref-field:last-of-type { margin-bottom: 0; }
.pref-field label.field-label { display: block; font-weight: 600; font-size: .95rem; margin-bottom: .3rem; }
.pref-field .field-hint { font-size: .8rem; color: #6b7280; margin-bottom: .5rem; }
body.dark .pref-field .field-hint { color: #9ca3af; }

.pref-field select {
  width: 100%; padding: .5rem .75rem; font-size: .95rem;
  border: 1px solid #d1d5db; border-radius: 6px;
  background: var(--bg); color: var(--text); cursor: pointer;
}
body.dark .pref-field select { border-color: #4b5563; }

.theme-tiles { display: flex; gap: .75rem; }
.theme-tile { flex: 1; position: relative; }
.theme-tile input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.theme-tile label {
  display: flex; flex-direction: column; align-items: center; gap: .4rem;
  padding: .9rem 1rem; border: 2px solid #d1d5db; border-radius: 8px;
  cursor: pointer; font-size: .88rem; transition: border-color .2s, box-shadow .2s;
  background: var(--bg); color: var(--text); user-select: none;
}
.tile-swatch { width: 44px; height: 28px; border-radius: 5px; border: 1px solid #e5e7eb; }
.tile-swatch.light-swatch { background: #f9fafb; }
.tile-swatch.dark-swatch  { background: #1e1e1e; }
.theme-tile input:checked + label { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
body.dark .theme-tile label { border-color: #4b5563; }
body.dark .theme-tile input:checked + label { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,.2); }

.pref-divider { border: none; border-top: 1px solid #e5e7eb; margin: 1.4rem 0; }
body.dark .pref-divider { border-color: #374151; }

.btn-save {
  display: inline-block; margin-top: 1.4rem; padding: .55rem 1.4rem;
  font-size: .95rem; font-weight: 600; background: #2563eb; color: #fff;
  border: none; border-radius: 6px; cursor: pointer; transition: background .2s;
}
.btn-save:hover { background: #1d4ed8; }

.pref-saved-banner {
  display: flex; align-items: center; gap: .5rem;
  background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;
  border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.4rem;
  font-size: .9rem; font-weight: 500;
}
body.dark .pref-saved-banner { background: #14532d; color: #bbf7d0; border-color: #166534; }

.pref-reset {
  display: inline-block; margin-top: .8rem; font-size: .82rem;
  color: #6b7280; cursor: pointer; text-decoration: underline;
  background: none; border: none; padding: 0;
}
.pref-reset:hover { color: #ef4444; }
</style>

<div class="pref-wrap">
  <h2>&#9881;&#65039; Preferences</h2>
  <p class="subtitle">Your settings are saved in browser cookies and applied on every page load.</p>

  <?php if ($saved): ?>
    <div class="pref-saved-banner">&#9989; Preferences saved successfully!</div>
  <?php endif; ?>

  <div class="pref-card">
    <form method="POST" action="preferences.php">

      <!-- Theme -->
      <div class="pref-field">
        <label class="field-label">&#127912; Theme</label>
        <p class="field-hint">Choose how IndicLex looks on your device.</p>
        <div class="theme-tiles">
          <div class="theme-tile">
            <input type="radio" name="theme" id="theme_light" value="light"
              <?= $prefs['theme'] === 'light' ? 'checked' : '' ?>>
            <label for="theme_light">
              <span class="tile-swatch light-swatch"></span>Light
            </label>
          </div>
          <div class="theme-tile">
            <input type="radio" name="theme" id="theme_dark" value="dark"
              <?= $prefs['theme'] === 'dark' ? 'checked' : '' ?>>
            <label for="theme_dark">
              <span class="tile-swatch dark-swatch"></span>Dark
            </label>
          </div>
        </div>
      </div>

      <hr class="pref-divider">

      <!-- Results per page -->
      <div class="pref-field">
        <label class="field-label" for="results_per_page">&#128196; Results per page</label>
        <p class="field-hint">How many search results to display per page.</p>
        <select name="results_per_page" id="results_per_page">
          <?php foreach ([5, 10, 20, 50] as $n): ?>
            <option value="<?= $n ?>"
              <?= $prefs['results_per_page'] === $n ? 'selected' : '' ?>>
              <?= $n ?> results
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <hr class="pref-divider">

      <!-- Default dictionary -->
      <div class="pref-field">
        <label class="field-label" for="default_dict">&#128218; Default dictionary</label>
        <p class="field-hint">Pre-select a dictionary when you open Search.</p>
        <select name="default_dict" id="default_dict">
          <option value="all"
            <?= $prefs['default_dict'] === 'all' ? 'selected' : '' ?>>
            All Dictionaries
          </option>
          <?php foreach ($dictionaries as $d): ?>
            <option value="<?= htmlspecialchars($d['dict_id']) ?>"
              <?= (string)$prefs['default_dict'] === (string)$d['dict_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn-save">Save Preferences</button>
    </form>

    <br>
    <button class="pref-reset" onclick="resetPreferences()">Reset to defaults</button>
  </div>
</div>

<script>
function resetPreferences() {
  if (!confirm('Reset all preferences to system defaults?')) return;
  ['theme', 'results_per_page', 'default_dict'].forEach(function(key) {
    document.cookie = key + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax';
  });
  window.location.href = 'preferences.php';
}

// Live-preview theme when tiles are toggled (before saving)
document.querySelectorAll('input[name="theme"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    if (this.value === 'dark') {
      document.body.classList.add('dark');
    } else {
      document.body.classList.remove('dark');
    }
    var toggle = document.getElementById('theme-toggle');
    if (toggle) toggle.checked = (this.value === 'dark');
  });
});
</script>

<?php include '../includes/footer.php'; ?>
