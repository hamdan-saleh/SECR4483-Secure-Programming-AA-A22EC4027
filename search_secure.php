<?php

require_once 'pdo_config.php';

// Read the keyword safely and provide an empty default.
$keyword = trim($_GET['keyword'] ?? '');

// Reject an empty search.
if ($keyword === '') {
    exit('Please enter a search keyword.');
}

// Reject malformed UTF-8 and overly long search values.
if (
    !mb_check_encoding($keyword, 'UTF-8') ||
    mb_strlen($keyword, 'UTF-8') > 100
) {
    http_response_code(400);
    exit('Invalid search input.');
}

// Prepare a fixed SQL command before supplying user-controlled data.
$sql = "SELECT id, name, illness_history
        FROM patient_records
        WHERE name LIKE :keyword";

$stmt = $pdo->prepare($sql);

// Supply the entire keyword as data, not as SQL instructions.
$stmt->execute([
    'keyword' => '%' . $keyword . '%'
]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Encode values before placing them inside HTML.
function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

if (count($records) > 0) {
    foreach ($records as $record) {
        echo '<div>';

        echo 'Result found for keyword: '
            . escapeHtml($keyword)
            . '<br>';

        echo 'Patient: '
            . escapeHtml($record['name']);

        echo ' | History: '
            . escapeHtml($record['illness_history']);

        echo '</div><hr>';
    }
} else {
    echo 'No records found for: '
        . escapeHtml($keyword);
}