<?php
/**
 * Нейтральная нормализация входных значений и защита от известных SQLi-последовательностей.
 * SQL-безопасность потребляющего кода обеспечивают только prepared statements.
 */
class Sanitizer
{
    private const SQL_INJECTION_PATTERNS = [
        '/[\'"`)\]]\s*(?:OR|AND)\s*(?:[\'"`][^\'"`]*[\'"`]|\d+|TRUE|FALSE|NULL)\s*(?:=|!=|<>|<=|>=|LIKE|REGEXP)\s*(?:[\'"`][^\'"`]*[\'"`]?|\d+|TRUE|FALSE|NULL)/i',
        '/\bUNION\s+(?:ALL\s+)?SELECT\b/i',
        '/;\s*(?:SELECT|INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|TRUNCATE|EXEC(?:UTE)?|CALL)\b/i',
        '/[\'"`]\s*(?:--|#|\/\*)/',
        '/\b(?:SLEEP|BENCHMARK|LOAD_FILE|UPDATEXML|EXTRACTVALUE)\s*\(/i',
        '/\b(?:INTO\s+(?:OUTFILE|DUMPFILE)|INFORMATION_SCHEMA)\b/i'
    ];

    /**
     * Нормализует значение, не меняя его SQL-экранированием.
     */
    public static function process($input)
    {
        if ($input === null || $input === '') {
            return $input;
        }
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::process($value);
            }
            return $input;
        }

        // Нулевые байты не должны доходить до хранилищ и файловых API.
        $input = str_replace("\0", '', $input);

        return trim($input);
    }

    /**
     * Отсекает известные SQLi-последовательности до вызова snippet-а.
     * Это defence in depth, а не замена параметризованных запросов.
     */
    public static function isSqlInjection($input): bool
    {
        if (is_array($input)) {
            foreach ($input as $value) {
                if (self::isSqlInjection($value)) {
                    return true;
                }
            }
            return false;
        }

        if (!is_string($input) && !is_numeric($input)) {
            return false;
        }

        foreach (self::SQL_INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, (string)$input)) {
                return true;
            }
        }

        return false;
    }
}
