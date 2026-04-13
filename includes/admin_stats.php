<?php

function getDashboardStats(PDO $conn): array
{
    $stats = [];

    $stats['total_dictionaries'] = $conn
        ->query("SELECT COUNT(*) FROM dictionaries")
        ->fetchColumn();

    $stats['total_words'] = $conn
        ->query("SELECT COUNT(*) FROM dictionary_entries")
        ->fetchColumn();

    $stmt = $conn->query("
        SELECT 
            dictionaries.name,
            COUNT(dictionary_entries.entry_id) AS word_count
        FROM dictionaries
        LEFT JOIN dictionary_entries
            ON dictionaries.dict_id = dictionary_entries.dict_id
        GROUP BY dictionaries.dict_id, dictionaries.name
        ORDER BY word_count DESC
    ");

    $stats['words_per_dictionary'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $stats;
}