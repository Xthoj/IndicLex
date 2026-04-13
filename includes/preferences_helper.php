<<<<<<< HEAD
<?php
/**
 * includes/preferences_helper.php
 *
 * Preference Resolution Chain (Iteration 5)
 * -----------------------------------------
 * 1. Check cookie  → use if present and valid
 * 2. Fall back to  → system_default from the `preferences` table
 * 3. Last resort   → hardcoded safety value
 *
 * Call load_preferences() once per page (after DB is available).
 * It returns an associative array and also sets $GLOBALS['prefs'].
 *
 * Schema assumed:
 *   preferences(pref_key VARCHAR, system_default VARCHAR, ...)
 *
 * Cookie names mirror pref_key values: theme, results_per_page, default_dict
 */

function load_preferences(PDO $conn): array
{
    // ── 1. Fetch system defaults from DB ────────────────────────────────────
    $defaults = [];
    try {
        $rows = $conn->query(
            "SELECT pref_key, system_default FROM preferences"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $defaults[$r['pref_key']] = $r['system_default'];
        }
    } catch (PDOException $e) {
        // Table may not exist yet; use hardcoded safety values below
    }

    // ── 2. Hardcoded safety values (last resort) ─────────────────────────
    $safety = [
        'theme'            => 'light',
        'results_per_page' => '10',
        'default_dict'     => 'all',
    ];

    // ── 3. Merge: safety → db_default → cookie ───────────────────────────
    $prefs = [];

    foreach ($safety as $key => $safeVal) {
        // Start with safety value
        $resolved = $defaults[$key] ?? $safeVal;

        // Cookie overrides DB default
        $cookieVal = $_COOKIE[$key] ?? null;
        if ($cookieVal !== null && $cookieVal !== '') {
            $resolved = $cookieVal;
        }

        $prefs[$key] = $resolved;
    }

    // ── 4. Validate / sanitize each pref ─────────────────────────────────
    // theme
    if (!in_array($prefs['theme'], ['light', 'dark'], true)) {
        $prefs['theme'] = 'light';
    }

    // results_per_page: must be one of the allowed values
    $allowed_rpp = [5, 10, 20, 50];
    $rpp = (int)$prefs['results_per_page'];
    if (!in_array($rpp, $allowed_rpp, true)) {
        $rpp = 10;
    }
    $prefs['results_per_page'] = $rpp;

    // default_dict: 'all' or a positive integer
    if ($prefs['default_dict'] !== 'all' && !ctype_digit((string)$prefs['default_dict'])) {
        $prefs['default_dict'] = 'all';
    }

    $GLOBALS['prefs'] = $prefs;
    return $prefs;
}

/**
 * Save a single preference:
 *   - Always writes/refreshes the cookie (365-day expiry, SameSite=Lax)
 */
function save_preference(string $key, string $value): void
{
    $expiry = time() + 365 * 24 * 3600;
    setcookie($key, $value, [
        'expires'  => $expiry,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$key] = $value; // make it visible in the same request
=======
<?php
/**
 * includes/preferences_helper.php
 *
 * Preference Resolution Chain (Iteration 5)
 * -----------------------------------------
 * 1. Check cookie  → use if present and valid
 * 2. Fall back to  → system_default from the `preferences` table
 * 3. Last resort   → hardcoded safety value
 *
 * Call load_preferences() once per page (after DB is available).
 * It returns an associative array and also sets $GLOBALS['prefs'].
 *
 * Schema assumed:
 *   preferences(pref_key VARCHAR, system_default VARCHAR, ...)
 *
 * Cookie names mirror pref_key values: theme, results_per_page, default_dict
 */

function load_preferences(PDO $conn): array
{
    // ── 1. Fetch system defaults from DB ────────────────────────────────────
    $defaults = [];
    try {
        $rows = $conn->query(
            "SELECT pref_key, system_default FROM preferences"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $defaults[$r['pref_key']] = $r['system_default'];
        }
    } catch (PDOException $e) {
        // Table may not exist yet; use hardcoded safety values below
    }

    // ── 2. Hardcoded safety values (last resort) ─────────────────────────
    $safety = [
        'theme'            => 'light',
        'results_per_page' => '10',
        'default_dict'     => 'all',
    ];

    // ── 3. Merge: safety → db_default → cookie ───────────────────────────
    $prefs = [];

    foreach ($safety as $key => $safeVal) {
        // Start with safety value
        $resolved = $defaults[$key] ?? $safeVal;

        // Cookie overrides DB default
        $cookieVal = $_COOKIE[$key] ?? null;
        if ($cookieVal !== null && $cookieVal !== '') {
            $resolved = $cookieVal;
        }

        $prefs[$key] = $resolved;
    }

    // ── 4. Validate / sanitize each pref ─────────────────────────────────
    // theme
    if (!in_array($prefs['theme'], ['light', 'dark'], true)) {
        $prefs['theme'] = 'light';
    }

    // results_per_page: must be one of the allowed values
    $allowed_rpp = [5, 10, 20, 50];
    $rpp = (int)$prefs['results_per_page'];
    if (!in_array($rpp, $allowed_rpp, true)) {
        $rpp = 10;
    }
    $prefs['results_per_page'] = $rpp;

    // default_dict: 'all' or a positive integer
    if ($prefs['default_dict'] !== 'all' && !ctype_digit((string)$prefs['default_dict'])) {
        $prefs['default_dict'] = 'all';
    }

    $GLOBALS['prefs'] = $prefs;
    return $prefs;
}

/**
 * Save a single preference:
 *   - Always writes/refreshes the cookie (365-day expiry, SameSite=Lax)
 */
function save_preference(string $key, string $value): void
{
    $expiry = time() + 365 * 24 * 3600;
    setcookie($key, $value, [
        'expires'  => $expiry,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$key] = $value; // make it visible in the same request
>>>>>>> 50c55f8a008be9bcda28bc86fc01a2fe49e49c16
}