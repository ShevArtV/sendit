<?php

/**
 * Срок жизни токена SendIt (sitoken).
 *
 * Токен переиспользуется, пока моложе si_token_ttl; старше — выдаётся новый, а предыдущий ещё
 * si_token_grace секунд принимается action.php (гонка фонового запроса с загрузкой другой страницы).
 * Хранится в данных si_sessions: sitoken, sitoken_at, sitoken_prev, sitoken_prev_till.
 * Токен «на всю сессию» намеренно не делается: это антиспам.
 */
class SiTokenTtl
{
    const DEFAULT_TTL = 900;
    const DEFAULT_GRACE = 60;

    /**
     * Токен для страницы: действующий переиспользуется, просроченный или отсутствующий заменяется
     * новым, прежний запоминается на GRACE секунд.
     */
    public static function issue(modX $modx, array $session): array
    {
        $now = time();
        if (!empty($session['sitoken']) && $now - (int)($session['sitoken_at'] ?? 0) < self::ttl($modx)) {
            return [
                'sitoken' => $session['sitoken'],
                'sitoken_at' => (int)$session['sitoken_at'],
                'sitoken_prev' => $session['sitoken_prev'] ?? '',
                'sitoken_prev_till' => (int)($session['sitoken_prev_till'] ?? 0),
            ];
        }
        return [
            'sitoken' => bin2hex(random_bytes(16)),
            'sitoken_at' => $now,
            'sitoken_prev' => $session['sitoken'] ?? '',
            'sitoken_prev_till' => $now + self::grace($modx),
        ];
    }

    public static function isValid(modX $modx, array $session, string $token): bool
    {
        if ($token === '') {
            return false;
        }
        $now = time();
        if (!empty($session['sitoken']) && hash_equals((string)$session['sitoken'], $token)) {
            return $now - (int)($session['sitoken_at'] ?? 0) < self::ttl($modx);
        }
        return !empty($session['sitoken_prev'])
            && hash_equals((string)$session['sitoken_prev'], $token)
            && $now <= (int)($session['sitoken_prev_till'] ?? 0);
    }

    private static function ttl(modX $modx): int
    {
        $ttl = (int)$modx->getOption('si_token_ttl', null, self::DEFAULT_TTL);
        return $ttl > 0 ? $ttl : self::DEFAULT_TTL;
    }

    private static function grace(modX $modx): int
    {
        $grace = (int)$modx->getOption('si_token_grace', null, self::DEFAULT_GRACE);
        return $grace > 0 ? $grace : self::DEFAULT_GRACE;
    }
}
