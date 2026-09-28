<?php
function login_attempt_keys($username, $ip) {
    $normalizedUsername = strtolower(trim($username));

    return [
        'account' => hash('sha256', "account\0" . $normalizedUsername . "\0" . $ip),
        'ip' => hash('sha256', "ip\0" . $ip),
    ];
}

function login_lockout_remaining(PDO $pdo, array $keys, $now) {
    $placeholders = implode(', ', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT attempt_key, window_started_at, locked_until FROM login_attempts WHERE attempt_key IN ($placeholders)");
    $stmt->execute(array_values($keys));

    $remaining = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $attempt) {
        if ((int) $attempt['window_started_at'] + 900 > $now) {
            $remaining = max($remaining, (int) $attempt['locked_until'] - $now);
        }
    }

    return max(0, $remaining);
}

function record_login_failure(PDO $pdo, array $keys, $now) {
    $limits = ['account' => 5, 'ip' => 20];
    $pdo->beginTransaction();

    try {
        foreach ($keys as $scope => $key) {
            $stmt = $pdo->prepare(
                'INSERT INTO login_attempts (attempt_key, attempt_count, window_started_at, locked_until, updated_at) '
                . 'VALUES (?, 0, ?, 0, ?) ON DUPLICATE KEY UPDATE attempt_key = VALUES(attempt_key)'
            );
            $stmt->execute([$key, $now, $now]);

            $stmt = $pdo->prepare('SELECT attempt_count, window_started_at FROM login_attempts WHERE attempt_key = ? FOR UPDATE');
            $stmt->execute([$key]);
            $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

            $windowStartedAt = (int) $attempt['window_started_at'];
            $attemptCount = (int) $attempt['attempt_count'];
            if ($windowStartedAt + 900 <= $now) {
                $windowStartedAt = $now;
                $attemptCount = 0;
            }

            $attemptCount++;
            $lockedUntil = $attemptCount >= $limits[$scope] ? $now + 900 : 0;
            $stmt = $pdo->prepare(
                'UPDATE login_attempts SET attempt_count = ?, window_started_at = ?, locked_until = ?, updated_at = ? WHERE attempt_key = ?'
            );
            $stmt->execute([$attemptCount, $windowStartedAt, $lockedUntil, $now, $key]);
        }

        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE updated_at < ? LIMIT 500');
        $stmt->execute([$now - 86400]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    return login_lockout_remaining($pdo, $keys, $now);
}

function clear_login_attempts(PDO $pdo, array $keys) {
    if (!$keys) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempt_key IN ($placeholders)");
    $stmt->execute(array_values($keys));
}