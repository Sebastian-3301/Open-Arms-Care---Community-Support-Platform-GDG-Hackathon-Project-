<?php
declare(strict_types=1);

function handleContact(array $input): never
{
    $name = trim((string)($input['name'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $subject = trim((string)($input['subject'] ?? ''));
    $message = trim((string)($input['message'] ?? ''));

    if ($name === '') throw new InvalidArgumentException('Name is required.');
    if (mb_strlen($name) > 400) throw new InvalidArgumentException('Name is too long.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    if (mb_strlen($subject) > 400) throw new InvalidArgumentException('Subject is too long.');
    if ($message === '') throw new InvalidArgumentException('Message is required.');
    if (mb_strlen($message) > 4000) throw new InvalidArgumentException('Message is too long.');

    $stmt = db()->prepare(
        'INSERT INTO contact_messages (sender_name, sender_email, subject, message) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, strtolower($email), $subject === '' ? null : $subject, $message]);

    jsonResponse(201, ['ok' => true, 'id' => (int)db()->lastInsertId()]);
}
