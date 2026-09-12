<?php
declare(strict_types=1);

function handleAdminSummary(): never
{
    $tables = [
        'individual_registrations',
        'organisation_registrations',
        'orphanage_registrations',
        'money_donation_intents',
        'goods_donation_intents',
        'contact_messages',
        'document_uploads',
    ];

    $pdo = db();
    $result = [];
    foreach ($tables as $table) {
        $result[$table] = (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    }

    jsonResponse(200, ['ok' => true, 'tables' => $result]);
}
