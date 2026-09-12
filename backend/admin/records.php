<?php
declare(strict_types=1);

const ADMIN_TABLES = [
    'individual_registrations' => 'individual_registrations',
    'organisation_registrations' => 'organisation_registrations',
    'orphanage_registrations' => 'orphanage_registrations',
    'money_donation_intents' => 'money_donation_intents',
    'goods_donation_intents' => 'goods_donation_intents',
    'contact_messages' => 'contact_messages',
    'document_uploads' => 'document_uploads',
];

const EDITABLE_STATUS_TABLES = [
    'individual_registrations',
    'organisation_registrations',
    'orphanage_registrations',
    'money_donation_intents',
    'goods_donation_intents',
    'contact_messages',
];

function handleAdminRecords(): never
{
    $table = $_GET['table'] ?? '';
    if (!isset(ADMIN_TABLES[$table])) {
        jsonResponse(400, ['ok' => false, 'error' => 'Choose a valid table.']);
    }

    $limit = filter_var($_GET['limit'] ?? 100, FILTER_VALIDATE_INT);
    $limit = $limit === false ? 100 : max(1, min($limit, 250));
    $pdo = db();

    if ($table === 'orphanage_registrations') {
        $stmt = $pdo->prepare(
            'SELECT r.*, GROUP_CONCAT(d.original_name ORDER BY d.id SEPARATOR ", ") AS documents
             FROM orphanage_registrations r
             LEFT JOIN document_uploads d ON d.orphanage_registration_id = r.id
             GROUP BY r.id
             ORDER BY r.id DESC
             LIMIT ' . $limit
        );
    } elseif ($table === 'document_uploads') {
        $stmt = $pdo->prepare(
            'SELECT id, orphanage_registration_id, original_name, media_type, size_bytes, created_at
             FROM document_uploads ORDER BY id DESC LIMIT ' . $limit
        );
    } else {
        $safeTable = ADMIN_TABLES[$table];
        $stmt = $pdo->prepare("SELECT * FROM `$safeTable` ORDER BY id DESC LIMIT $limit");
    }

    $stmt->execute();
    jsonResponse(200, ['ok' => true, 'table' => $table, 'records' => $stmt->fetchAll()]);
}

function handleAdminRecordPatch(array $input): never
{
    $table = (string)($input['table'] ?? '');
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string)($input['status'] ?? '');

    if (!in_array($table, EDITABLE_STATUS_TABLES, true)) {
        throw new InvalidArgumentException('Choose a valid record type.');
    }
    if ($id === false || $id < 1) {
        throw new InvalidArgumentException('Record id must be a positive whole number.');
    }
    if (!in_array($status, ['new', 'reviewed', 'archived'], true)) {
        throw new InvalidArgumentException('Choose a valid status.');
    }

    $stmt = db()->prepare("UPDATE `" . ADMIN_TABLES[$table] . "` SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    if ($stmt->rowCount() === 0) {
        $check = db()->prepare("SELECT id FROM `" . ADMIN_TABLES[$table] . "` WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            throw new InvalidArgumentException('Record not found.');
        }
    }

    jsonResponse(200, ['ok' => true]);
}
