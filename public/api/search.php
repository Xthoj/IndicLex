<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q    = trim($_GET['q']    ?? '');
$dict = $_GET['dict']      ?? 'all';
$mode = $_GET['mode']      ?? 'substring';

// Validate required parameter
if ($q === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameter: q'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate mode
$valid_modes = ['exact', 'prefix', 'suffix', 'substring'];
if (!in_array($mode, $valid_modes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid mode. Use: exact, prefix, suffix, or substring'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Build pattern
$escaped = str_replace(['%', '_'], ['\%', '\_'], $q);
switch ($mode) {
    case 'exact':     $pattern = $escaped;             break;
    case 'prefix':    $pattern = $escaped . '%';       break;
    case 'suffix':    $pattern = '%' . $escaped;       break;
    case 'substring': $pattern = '%' . $escaped . '%'; break;
}

try {
    $where_parts = [
        "(e.lang_1 LIKE :pattern OR e.lang_2 LIKE :pattern OR e.lang_3 LIKE :pattern)",
        "e.is_active = 1"
    ];
    $params = [':pattern' => $pattern];

    if ($dict !== 'all' && is_numeric($dict)) {
        $where_parts[] = "e.dict_id = :dict_id";
        $params[':dict_id'] = (int)$dict;
    }

    $where = "WHERE " . implode(" AND ", $where_parts);

    $stmt = $conn->prepare("
        SELECT e.entry_id, e.lang_1, e.lang_2, e.lang_3,
               e.pronunciation, e.part_of_speech, e.example,
               d.name AS dict_name, d.type AS dict_type
        FROM dictionary_entries e
        JOIN dictionaries d ON e.dict_id = d.dict_id
        $where
        ORDER BY e.lang_1
        LIMIT 50
    ");
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        http_response_code(404);
        echo json_encode(['error' => 'No results found', 'results' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'count'   => count($results),
        'query'   => $q,
        'mode'    => $mode,
        'results' => $results,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
