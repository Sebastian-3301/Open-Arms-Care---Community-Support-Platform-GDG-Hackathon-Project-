<?php
declare(strict_types=1);

const MAX_DOCUMENTS = 3;
const MAX_DOCUMENT_BYTES = 5242880;
const ALLOWED_DOCUMENT_TYPES = [
    'application/pdf' => ['extension' => '.pdf', 'signature' => 'pdf'],
    'image/jpeg' => ['extension' => '.jpg', 'signature' => 'jpg'],
    'image/png' => ['extension' => '.png', 'signature' => 'png'],
];

function textValue(mixed $value, string $label, bool $optional = false, int $max = 400): ?string
{
    $cleaned = trim((string)($value ?? ''));
    if ($cleaned === '' && !$optional) {
        throw new InvalidArgumentException("$label is required.");
    }
    if (mb_strlen($cleaned) > $max) {
        throw new InvalidArgumentException("$label is too long.");
    }
    return $cleaned === '' ? null : $cleaned;
}

function integerValue(mixed $value, string $label, int $min = 0, int $max = 1000000): int
{
    if (is_int($value)) {
        $parsed = $value;
    } elseif (is_string($value) && preg_match('/^\d+$/', trim($value))) {
        $parsed = (int)trim($value);
    } else {
        throw new InvalidArgumentException("$label must be a whole number between $min and $max.");
    }

    if ($parsed < $min || $parsed > $max) {
        throw new InvalidArgumentException("$label must be a whole number between $min and $max.");
    }
    return $parsed;
}

function validateEmailValue(mixed $value): string
{
    $email = textValue($value, 'Email address', false, 254);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    return strtolower($email);
}

function validateDocuments(mixed $value): array
{
    if (!is_array($value) || count($value) === 0) {
        throw new InvalidArgumentException('Add at least one registration document.');
    }
    if (count($value) > MAX_DOCUMENTS) {
        throw new InvalidArgumentException('You can add up to 3 documents.');
    }

    $validated = [];
    foreach (array_values($value) as $index => $document) {
        if (!is_array($document)) {
            throw new InvalidArgumentException('Document ' . ($index + 1) . ' could not be read.');
        }

        $originalName = textValue($document['name'] ?? null, 'Document ' . ($index + 1) . ' name', false, 180);
        $mediaType = textValue($document['type'] ?? null, 'Document ' . ($index + 1) . ' type', false, 80);

        if (!isset(ALLOWED_DOCUMENT_TYPES[$mediaType])) {
            throw new InvalidArgumentException('Only PDF, JPG, and PNG registration documents are accepted.');
        }

        $data = (string)($document['data'] ?? '');
        if (!preg_match('#^data:[^;]+;base64,([A-Za-z0-9+/=]+)$#', $data, $matches)) {
            throw new InvalidArgumentException('Document ' . ($index + 1) . ' could not be read.');
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false || strlen($binary) === 0 || strlen($binary) > MAX_DOCUMENT_BYTES) {
            throw new InvalidArgumentException('Each document must be 5 MB or smaller.');
        }

        $signatureOk = match ($mediaType) {
            'application/pdf' => strncmp($binary, '%PDF-', 5) === 0,
            'image/jpeg' => substr($binary, 0, 3) === "\xFF\xD8\xFF",
            'image/png' => substr($binary, 0, 8) === "\x89PNG\r\n\x1a\n",
            default => false,
        };
        if (!$signatureOk) {
            throw new InvalidArgumentException('Document ' . ($index + 1) . ' does not match its file type.');
        }

        $validated[] = [
            'original_name' => $originalName,
            'media_type' => $mediaType,
            'extension' => ALLOWED_DOCUMENT_TYPES[$mediaType]['extension'],
            'binary' => $binary,
        ];
    }
    return $validated;
}

function handleRegistration(string $path, array $input): never
{
    $pdo = db();

    if ($path === '/api/registrations/individual') {
        $stmt = $pdo->prepare(
            'INSERT INTO individual_registrations (full_name, phone, city, interest) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            textValue($input['fullName'] ?? null, 'Full name'),
            textValue($input['phone'] ?? null, 'Phone number', false, 30),
            textValue($input['city'] ?? null, 'City'),
            textValue($input['interest'] ?? null, 'Interest'),
        ]);
        jsonResponse(201, ['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($path === '/api/registrations/organisation') {
        $stmt = $pdo->prepare(
            'INSERT INTO organisation_registrations (organisation_name, registration_number, location, phone, interest) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            textValue($input['organisationName'] ?? null, 'Organisation name'),
            textValue($input['registrationNumber'] ?? null, 'Registration number', true, 100),
            textValue($input['location'] ?? null, 'City / State'),
            textValue($input['phone'] ?? null, 'Phone number', false, 30),
            textValue($input['interest'] ?? null, 'Interest'),
        ]);
        jsonResponse(201, ['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($path === '/api/registrations/orphanage') {
        $documents = validateDocuments($input['documents'] ?? null);
        $uploadDir = dirname(__DIR__, 2) . '/data/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true)) {
            throw new RuntimeException('Unable to prepare document storage.');
        }

        $savedFiles = [];
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO orphanage_registrations
                (orphanage_name, administrator_name, location, phone, children_total, children_age_0_5, children_age_6_12, children_age_13_18)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                textValue($input['orphanageName'] ?? null, 'Orphanage name'),
                textValue($input['administratorName'] ?? null, "Administrator's name"),
                textValue($input['location'] ?? null, 'Location'),
                textValue($input['phone'] ?? null, 'Phone number', false, 30),
                integerValue($input['childrenTotal'] ?? null, 'Total number of children'),
                integerValue($input['childrenAge0to5'] ?? 0, '0–5 age count'),
                integerValue($input['childrenAge6to12'] ?? 0, '6–12 age count'),
                integerValue($input['childrenAge13to18'] ?? 0, '13–18 age count'),
            ]);
            $registrationId = (int)$pdo->lastInsertId();

            $docStmt = $pdo->prepare(
                'INSERT INTO document_uploads (orphanage_registration_id, original_name, storage_name, media_type, size_bytes)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($documents as $document) {
                $storageName = bin2hex(random_bytes(16)) . $document['extension'];
                $storagePath = $uploadDir . DIRECTORY_SEPARATOR . $storageName;
                if (file_put_contents($storagePath, $document['binary'], LOCK_EX) === false) {
                    throw new RuntimeException('Unable to save an uploaded document.');
                }
                chmod($storagePath, 0640);
                $savedFiles[] = $storagePath;

                $docStmt->execute([
                    $registrationId,
                    $document['original_name'],
                    $storageName,
                    $document['media_type'],
                    strlen($document['binary']),
                ]);
            }

            $pdo->commit();
            jsonResponse(201, ['ok' => true, 'id' => $registrationId]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($savedFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            throw $e;
        }
    }

    jsonResponse(404, ['ok' => false, 'error' => 'Unknown endpoint.']);
}
