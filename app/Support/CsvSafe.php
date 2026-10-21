<?php

namespace App\Support;

/**
 * Guards CSV cells against spreadsheet formula injection.
 *
 * Excel, LibreOffice and Google Sheets evaluate a cell as a formula when it
 * starts with =, +, - or @ (or a tab / carriage return that some apps strip
 * first). Names and product labels are user controlled, so a value such as
 * `=HYPERLINK("http://evil","click")` would run when a manager opens the
 * export. Prefixing an apostrophe forces the cell to be read as plain text.
 *
 * Only wrap free-text columns; real numbers must stay numbers.
 */
final class CsvSafe
{
    private const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public static function cell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], self::TRIGGERS, true) ? "'".$value : $value;
    }
}
