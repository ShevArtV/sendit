<?php

require_once __DIR__ . '/../back/core/components/sendit/services/sanitizer.class.php';

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue(bool $actual, string $message): void
{
    assertSameValue(true, $actual, $message);
}

function assertFalseValue(bool $actual, string $message): void
{
    assertSameValue(false, $actual, $message);
}

assertSameValue("O'Reilly", Sanitizer::process(" O'Reilly "), 'Апостроф должен сохраняться.');
assertSameValue('Select a colour #gift for @friend', Sanitizer::process('Select a colour #gift for @friend'), 'Обычный текст не должен вырезаться.');
assertSameValue('value', Sanitizer::process("\0value\0"), 'Нулевые байты должны удаляться.');
assertSameValue(['name' => "O'Reilly"], Sanitizer::process(['name' => "O'Reilly"]), 'Массив нормализуется рекурсивно.');

assertTrueValue(Sanitizer::isSqlInjection("product' OR '1'='1"), 'Должна блокироваться boolean-based SQLi.');
assertTrueValue(Sanitizer::isSqlInjection('x UNION SELECT password FROM users'), 'Должна блокироваться UNION SQLi.');
assertTrueValue(Sanitizer::isSqlInjection("x'; DROP TABLE users"), 'Должна блокироваться multi-statement SQLi.');
assertTrueValue(Sanitizer::isSqlInjection("x' -- comment"), 'Должна блокироваться SQL-комментарий после кавычки.');
assertTrueValue(Sanitizer::isSqlInjection(['value' => 'SLEEP(5)']), 'Должна блокироваться SQLi во вложенном массиве.');
assertFalseValue(Sanitizer::isSqlInjection('Select a colour #gift for @friend'), 'Обычный текст не должен блокироваться.');
assertFalseValue(Sanitizer::isSqlInjection("O'Reilly"), 'Один апостроф не является SQLi.');

echo "Sanitizer tests passed\n";
