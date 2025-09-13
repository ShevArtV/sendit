<?php
/**
 * Дополнительная санитизация строки перед использованием в SQL
 * ВАЖНО: Это НЕ заменяет подготовленные выражения!
 */
class Sanitizer
{
    private const EMAIL_REGEXP = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    private const DANGEROUS_PATTERNS = [
        '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|EXEC|EXECUTE|TRUNCATE|ALTER|CREATE|SHOW|DESCRIBE|GRANT|REVOKE|COMMIT|ROLLBACK|MERGE|CALL)\b/i',
        '/;.*--/',
        '/\/\*.*\*\//',
        '/--\s+/',
        '/#.*$/',
        '/WAITFOR\s+DELAY/i',
        '/XP_/i',
        '/sp_/i',
        '/@@/',
        '/@\w+/',
        '/CHAR\(\d+\)/',
        '/0x[0-9A-Fa-f]+/',
        '/BENCHMARK\(/i',
        '/SLEEP\(/i',
        '/LOAD_FILE\(/i',
        '/INTO\s+(OUTFILE|DUMPFILE)/i',
        '/CONCAT_WS\(/i',
        '/GROUP_CONCAT\(/i',
        '/INFORMATION_SCHEMA/i',
        '/sys\./i',
        '/pg_/i'
    ];

    /**
     * Дополнительная санитизация строки
     */
    public static function process($input)
    {
        if ($input === null || $input === '') {
            return $input;
        }
        if(is_array($input)){
            foreach ($input as $key => $value) {
                $input[$key] = self::process($value);
            }
            return $input;
        }

        if(preg_match(self::EMAIL_REGEXP, $input)){
            return $input;
        }

        // Удаляем нулевые байты
        $input = str_replace("\0", '', $input);

        // Удаляем опасные SQL паттерны
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        // Экранируем специальные символы (дополнительная мера)
        $input = addslashes($input);

        // Удаляем избыточные экранирования
        $input = str_replace(['\\\\', "\\'", '\\"'], ['\\', "'", '"'], $input);

        // Обрезаем пробелы
        return trim($input);
    }
}
