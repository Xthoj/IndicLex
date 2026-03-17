<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class DictionaryImporter {

    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function parse(string $filepath): array {

        $results = [];

        try {
            $spreadsheet = IOFactory::load($filepath);
        } catch (\Exception $e) {
            return [[
                'sheet_name'   => 'file',
                'dict_id'      => null,
                'dict_created' => false,
                'valid_rows'   => [],
                'duplicates'   => [],
                'errors'       => [['row' => 0, 'message' => 'Could not read file: ' . $e->getMessage()]],
            ]];
        }

        foreach ($spreadsheet->getSheetNames() as $sheet_name) {

            $sheet  = $spreadsheet->getSheetByName($sheet_name);
            $rows   = $sheet->toArray(null, true, true, false);

            $sheet_result = [
                'sheet_name'   => $sheet_name,
                'dict_id'      => null,
                'dict_created' => false,
                'valid_rows'   => [],
                'errors'       => [],
            ];

            if (empty($rows)) {
                $sheet_result['errors'][] = ['row' => 0, 'message' => 'Sheet is empty.'];
                $results[] = $sheet_result;
                continue;
            }

            // Detect column count from first non-empty row 
            $col_count = 0;
            foreach ($rows as $row) {
                $filled = array_filter($row, fn($v) => trim((string)$v) !== '');
                if (count($filled) > $col_count) $col_count = count($filled);
            }

            // Parse languages from sheet name 
            $langs = $this->parseLangsFromSheetName($sheet_name);

            $is_trilingual = count($langs) >= 3;

            $lang_1_name = $langs[0] ?? 'Language 1';
            $lang_2_name = $langs[1] ?? 'Language 2';
            $lang_3_name = $langs[2] ?? null;

            //Find or create dictionary
            [$dict_id, $dict_created] = $this->findOrCreateDictionary(
                $sheet_name . ' Dictionary',
                $lang_1_name,
                $lang_2_name,
                $is_trilingual ? $lang_3_name : null,
                $is_trilingual
            );

            $sheet_result['dict_id']      = $dict_id;
            $sheet_result['dict_created'] = $dict_created;

            //Process rows
            $seen_in_file = []; // track lang_1 values seen within this sheet
            foreach ($rows as $i => $row) {
                $row_number = $i + 1;

                $lang_1 = trim((string)($row[0] ?? ''));
                $lang_2 = trim((string)($row[1] ?? ''));
                $lang_3 = $is_trilingual ? trim((string)($row[2] ?? '')) : null;
                $lang_3 = ($lang_3 === '') ? null : $lang_3;

                // If lang_2 is empty but lang_3 has content, promote lang_3 to lang_2
                if ($lang_2 === '' && $lang_3 !== null) {
                    $lang_2 = $lang_3;
                    $lang_3 = null;
                }

                // Skip rows with no usable data
                if ($lang_1 === '' || $lang_2 === '') continue;

                // Normalise lang_1 for duplicate checking (trim whitespace)
                $lang_1_key = mb_strtolower(trim($lang_1));

                // Skip duplicates (both in DB and within this file)
                if (isset($seen_in_file[$lang_1_key]) || $this->isDuplicate($dict_id, $lang_1)) continue;
                $seen_in_file[$lang_1_key] = true;

                $sheet_result['valid_rows'][] = [
                    'row'            => $row_number,
                    'dict_id'        => $dict_id,
                    'lang_1'         => $lang_1,
                    'lang_2'         => $lang_2,
                    'lang_3'         => $lang_3,
                    'pronunciation'  => null,
                    'part_of_speech' => null,
                    'example'        => null,
                    'notes'          => null,
                ];
            }

            $results[] = $sheet_result;
        }

        return $results;
    }

    // Accepts the full results array from parse() and inserts all valid rows.
    // Returns ['inserted' => N, 'failed' => [...]]
    public function insert(array $all_sheet_results): array {
        $sql = "INSERT INTO dictionary_entries
                    (dict_id, lang_1, lang_2, lang_3, pronunciation, part_of_speech, example, notes)
                VALUES
                    (:dict_id, :lang_1, :lang_2, :lang_3, :pronunciation, :part_of_speech, :example, :notes)";

        $stmt     = $this->conn->prepare($sql);
        $inserted = 0;
        $failed   = [];

        foreach ($all_sheet_results as $sheet) {
            foreach ($sheet['valid_rows'] as $row) {
                try {
                    $stmt->execute([
                        ':dict_id'        => $row['dict_id'],
                        ':lang_1'         => $row['lang_1'],
                        ':lang_2'         => $row['lang_2'],
                        ':lang_3'         => $row['lang_3'],
                        ':pronunciation'  => $row['pronunciation'],
                        ':part_of_speech' => $row['part_of_speech'],
                        ':example'        => $row['example'],
                        ':notes'          => $row['notes'],
                    ]);
                    $inserted++;
                } catch (\PDOException $e) {
                    $failed[] = [
                        'row'     => $row['row'],
                        'lang_1'  => $row['lang_1'],
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        return ['inserted' => $inserted, 'failed' => $failed];
    }


    private function parseLangsFromSheetName(string $name): array {
        // Normalise various dash types and extra spaces around dashes
        $normalised = preg_replace('/\s*[–—-]+\s*/', '-', $name);
        $parts      = array_map('trim', explode('-', $normalised));
        $parts      = array_filter($parts, fn($p) => $p !== '');
        return array_values($parts);
    }

    // findOrCreateDictionary()
    // Looks up the dictionary by name. Creates it if it doesn't exist.
    // Returns [dict_id, was_created]
    private function findOrCreateDictionary(
        string $name,
        string $lang_1,
        string $lang_2,
        ?string $lang_3,
        bool $is_trilingual
    ): array {
 
        // Create a slug-style identifier first
        $identifier = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $identifier = trim($identifier, '-');
 
        // Also try slug without the '-dictionary' suffix (matches pre-existing DB entries)
        $identifier_short = preg_replace('/-dictionary$/', '', $identifier);
 
        // Try to find existing by slug identifier (check both with and without -dictionary suffix)
        $stmt = $this->conn->prepare(
            "SELECT dict_id FROM dictionaries WHERE dict_identifier IN (:id1, :id2) LIMIT 1"
        );
        $stmt->execute([':id1' => $identifier, ':id2' => $identifier_short]);
        $existing = $stmt->fetchColumn();
 
        if ($existing) {
            return [(int)$existing, false];
        }
 
        // Make identifier unique if needed
        $base = $identifier;
        $n    = 1;
        while ($this->identifierExists($identifier)) {
            $identifier = $base . '-' . $n++;
        }
 
        $type = $is_trilingual ? 'trilingual' : 'bilingual';
 
        $stmt = $this->conn->prepare(
            "INSERT INTO dictionaries
                (dict_identifier, name, type, source_lang_1, source_lang_2, source_lang_3, created_by)
             VALUES
                (:identifier, :name, :type, :lang_1, :lang_2, :lang_3, 1)"
        );

        $stmt->execute([
            ':identifier' => $identifier,
            ':name'       => $name,
            ':type'       => $type,
            ':lang_1'     => $lang_1,
            ':lang_2'     => $lang_2,
            ':lang_3'     => $lang_3,
        ]);

        return [(int)$this->conn->lastInsertId(), true];
    }

    // Helpers
    private function identifierExists(string $identifier): bool {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM dictionaries WHERE dict_identifier = :id LIMIT 1"
        );
        $stmt->execute([':id' => $identifier]);
        return (bool)$stmt->fetchColumn();
    }

    private function isDuplicate(int $dict_id, string $lang_1): bool {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM dictionary_entries WHERE dict_id = :dict_id AND lang_1 = :lang_1 LIMIT 1"
        );
        $stmt->execute([':dict_id' => $dict_id, ':lang_1' => $lang_1]);
        return (bool)$stmt->fetchColumn();
    }
}