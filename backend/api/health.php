<?php
declare(strict_types=1);

function handleHealth(): never
{
    try {
        db()->query('SELECT 1');
        jsonResponse(200, ['ok' => true]);
    } catch (Throwable $e) {
        error_log('Open Arms health check failed: ' . $e->getMessage());
        jsonResponse(503, ['ok' => false, 'error' => 'Database unavailable.']);
    }
}
