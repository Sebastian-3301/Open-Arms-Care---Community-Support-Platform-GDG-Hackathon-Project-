<?php
declare(strict_types=1);

function donationText(mixed $value, string $label, bool $optional = false, int $max = 400): ?string
{
    $clean = trim((string)($value ?? ''));
    if ($clean === '' && !$optional) {
        throw new InvalidArgumentException("$label is required.");
    }
    if (mb_strlen($clean) > $max) {
        throw new InvalidArgumentException("$label is too long.");
    }
    return $clean === '' ? null : $clean;
}

function donationInteger(mixed $value, string $label, int $min, int $max): int
{
    if (is_int($value)) {
        $n = $value;
    } elseif (is_string($value) && preg_match('/^\d+$/', trim($value))) {
        $n = (int)trim($value);
    } else {
        throw new InvalidArgumentException("$label must be a whole number between $min and $max.");
    }
    if ($n < $min || $n > $max) {
        throw new InvalidArgumentException("$label must be a whole number between $min and $max.");
    }
    return $n;
}

function donationEmail(mixed $value): string
{
    $email = donationText($value, 'Email address', false, 254);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    return strtolower($email);
}

function handleDonation(string $path, array $input): never
{
    $pdo = db();

    if ($path === '/api/donations/money') {
        $frequency = donationText($input['frequency'] ?? null, 'Donation frequency', false, 20);
        if (!in_array($frequency, ['once', 'monthly'], true)) {
            throw new InvalidArgumentException('Choose a valid donation frequency.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO money_donation_intents (frequency, amount, donor_name, donor_email, preferred_orphanage)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $frequency,
            donationInteger($input['amount'] ?? null, 'Donation amount', 1, 10000000),
            donationText($input['donorName'] ?? null, 'Full name'),
            donationEmail($input['donorEmail'] ?? null),
            donationText($input['preferredOrphanage'] ?? null, 'Preferred orphanage', true),
        ]);
        jsonResponse(201, ['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($path === '/api/donations/goods') {
        $categories = $input['categories'] ?? [];
        if (!is_array($categories) || count($categories) === 0) {
            throw new InvalidArgumentException('Choose at least one donation category.');
        }
        if (count($categories) > 20) {
            throw new InvalidArgumentException('Too many donation categories.');
        }

        $cleanCategories = [];
        foreach ($categories as $category) {
            $clean = donationText($category, 'Donation category', false, 40);
            $cleanCategories[] = $clean;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO goods_donation_intents
             (categories, description, quantity, item_condition, fulfilment_mode, phone)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            json_encode($cleanCategories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            donationText($input['description'] ?? null, 'Description', false, 2000),
            donationText($input['quantity'] ?? null, 'Quantity', true, 100),
            donationText($input['condition'] ?? null, 'Condition', true, 40),
            donationText($input['fulfilmentMode'] ?? null, 'Preference', true, 80),
            donationText($input['phone'] ?? null, 'Phone number', false, 30),
        ]);
        jsonResponse(201, ['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    jsonResponse(404, ['ok' => false, 'error' => 'Unknown endpoint.']);
}
