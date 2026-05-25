<?php

require __DIR__ . '/../vendor/autoload.php';
use OrpheusNET\Logchecker\Logchecker;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Please use POST.']);
    exit;
}

if (!isset($_FILES['log']) || $_FILES['log']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No log file uploaded or upload error.']);
    exit;
}

$file = $_FILES['log']['tmp_name'];

try {
    $logchecker = new Logchecker();
    $logchecker->newFile($file);
    $logchecker->parse();

    $response = [
        "ripper"   => $logchecker->getRipper(),
        "version"  => $logchecker->getRipperVersion(),
        "language" => $logchecker->getLanguage(),
        "combined" => $logchecker->isCombinedLog(),
        "score"    => $logchecker->getScore(),
        "checksum" => $logchecker->getChecksumState(),
        "details"  => $logchecker->getDetails(),
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
