<?php
header('Content-Type: application/json');

$mode = $_POST['mode'] ?? 'text';

function cleanCode($code) {
    $code = preg_replace('!/\*.*?\*/!s', '', $code); 
    $code = preg_replace('!//.*!', '', $code);       
    $code = preg_replace('/\s+/', ' ', $code);       
    return strtolower(trim($code));
}

function calculateSimilarity($code1, $code2) {
    $clean1 = cleanCode($code1);
    $clean2 = cleanCode($code2);

    $tokens1 = array_unique(array_filter(explode(' ', $clean1)));
    $tokens2 = array_unique(array_filter(explode(' ', $clean2)));

    $intersection = count(array_intersect($tokens1, $tokens2));
    $union = count(array_unique(array_merge($tokens1, $tokens2)));

    return ($union > 0) ? round(($intersection / $union) * 100, 2) : 0;
}

if ($mode === 'batch') {
    if (!isset($_FILES['files']) || count($_FILES['files']['name']) < 2) {
        echo json_encode(['error' => 'Select at least 2 files']);
        exit;
    }

    $files = $_FILES['files'];
    $fileData = [];
    $filenames = [];

    for ($i = 0; $i < count($files['name']); $i++) {
        $name = $files['name'][$i];
        $content = file_get_contents($files['tmp_name'][$i]);
        $fileData[$name] = $content;
        $filenames[] = $name;
    }

    $matrix = [];
    foreach ($filenames as $f1) {
        foreach ($filenames as $f2) {
            if ($f1 === $f2) {
                $matrix[$f1][$f2] = 100;
            } else {
                $matrix[$f1][$f2] = calculateSimilarity($fileData[$f1], $fileData[$f2]);
            }
        }
    }

    echo json_encode([
        'type' => 'batch',
        'filenames' => $filenames,
        'matrix' => $matrix
    ]);
    exit;
} else {
    $code1 = $_POST['code1'] ?? '';
    $code2 = $_POST['code2'] ?? '';

    $similarity = calculateSimilarity($code1, $code2);
    $status = ($similarity > 70) ? "High Risk" : (($similarity > 35) ? "Moderate Match" : "Passed");
    $color = ($similarity > 70) ? "#430e0c" : (($similarity > 35) ? "#9f633de2" : "#13542c");

    echo json_encode([
        'type' => 'single',
        'similarity' => $similarity,
        'status' => $status,
        'color' => $color
    ]);
    exit;
}
?>