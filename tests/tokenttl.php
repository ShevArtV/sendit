<?php

// Юнит-проверка SiTokenTtl без MODX: modX подменён заглушкой с getOption.
class modX
{
    private $options;
    public function __construct(array $options) { $this->options = $options; }
    public function getOption($key, $options = null, $default = null) { return $this->options[$key] ?? $default; }
}

require_once __DIR__ . '/../back/core/components/sendit/services/sitokenttl.class.php';

function check(bool $ok, string $msg): void
{
    if (!$ok) {
        fwrite(STDERR, "FAIL: $msg\n");
        exit(1);
    }
}

$modx = new modX(['si_token_ttl' => 900, 'si_token_grace' => 60]);
$now = time();

$t = SiTokenTtl::issue($modx, []);
check(strlen($t['sitoken']) === 32 && $t['sitoken_prev'] === '', 'Пустая сессия получает новый токен.');
check(SiTokenTtl::isValid($modx, $t, $t['sitoken']), 'Свежий токен принимается.');
check(SiTokenTtl::issue($modx, $t)['sitoken'] === $t['sitoken'], 'Токен моложе TTL переиспользуется.');

$old = ['sitoken' => 'aaa', 'sitoken_at' => $now - 901];
check(!SiTokenTtl::isValid($modx, $old, 'aaa'), 'Токен старше TTL отклоняется.');
$n = SiTokenTtl::issue($modx, $old);
check($n['sitoken'] !== 'aaa' && $n['sitoken_prev'] === 'aaa' && $n['sitoken_prev_till'] === $n['sitoken_at'] + 60, 'Просроченный заменяется, прежний запоминается на GRACE.');
check(SiTokenTtl::isValid($modx, $n, 'aaa'), 'Предыдущий принимается в пределах GRACE.');
check(SiTokenTtl::isValid($modx, $n, $n['sitoken']), 'Новый принимается.');
check(!SiTokenTtl::isValid($modx, array_merge($n, ['sitoken_prev_till' => $now - 1]), 'aaa'), 'Предыдущий отклоняется после GRACE.');
check(!SiTokenTtl::isValid($modx, $n, 'deadbeef'), 'Мусорный токен отклоняется.');
check(!SiTokenTtl::isValid($modx, $n, ''), 'Пустой токен отклоняется.');
check(!SiTokenTtl::isValid($modx, ['sitoken_prev' => '', 'sitoken_prev_till' => $now + 60], ''), 'Пустой prev не совпадает с пустым токеном.');

$short = new modX(['si_token_ttl' => 60, 'si_token_grace' => 0]);
$mid = ['sitoken' => 'bbb', 'sitoken_at' => $now - 61];
check(!SiTokenTtl::isValid($short, $mid, 'bbb'), 'TTL берётся из настройки si_token_ttl.');
check(SiTokenTtl::isValid($modx, $mid, 'bbb'), 'С дефолтным TTL тот же токен действителен.');
check(SiTokenTtl::issue($short, $mid)['sitoken_prev_till'] - $now >= 60, 'GRACE <= 0 заменяется дефолтом 60.');
check(!SiTokenTtl::isValid(new modX(['si_token_ttl' => 0]), ['sitoken' => 'c', 'sitoken_at' => $now - 901], 'c'), 'TTL <= 0 заменяется дефолтом 900.');

echo "tokenttl: ok\n";
