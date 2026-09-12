<?php
declare(strict_types=1);

function handleAdminDocument(int $id): never
{
    if ($id < 1) {
        jsonResponse(400, ['ok' => false, 'error' => 'Invalid document id.']);
    }

    $stmt = db()->prepare(
        'SELECT original_name, storage_name, media_type FROM document_uploads WHERE id = ?'
    );
    $stmt->execute([$id]);
    $document = $stmt->fetch();

    if (!$document) {
        jsonResponse(404, ['ok' => false, 'error' => 'Document not found.']);
    }

    $uploadDir = realpath(dirname(__DIR__, 2) . '/data/uploads');
    $file = $uploadDir ? realpath($uploadDir . DIRECTORY_SEPARATOR . $document['storage_name']) : false;

    if (!$uploadDir || !$file || !str_starts_with($file, $uploadDir . DIRECTORY_SEPARATOR) || !is_file($file)) {
        jsonResponse(404, ['ok' => false, 'error' => 'Document file not found.']);
    }

    $safeName = preg_replace('/[\\\\\/\r\n"]/', '_', (string)$document['original_name']);
    header('Content-Type: ' . $document['media_type']);
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}
