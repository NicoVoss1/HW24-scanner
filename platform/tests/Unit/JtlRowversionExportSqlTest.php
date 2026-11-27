<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression proof for the exact SQL Server conversion confirmed in
 * HeidiSQL by the operator on 2026-11-23.
 */
final class JtlRowversionExportSqlTest extends TestCase
{
    public function test_both_article_selects_cast_rowversion_to_varbinary_before_hex(): void
    {
        $path = dirname(__DIR__, 2) . '/ops/jtl-heidisql-phase6-article-export-readonly.sql';
        $sql = file_get_contents($path);
        $this->assertIsString($sql);

        $correct = 'CONVERT(varchar(18), CONVERT(varbinary(8), a.bRowversion), 1) AS jtl_rowversion_hex';

        $this->assertSame(
            2,
            substr_count($sql, $correct),
            'Both sample SELECT and complete CSV export must preserve hex rowversions.'
        );
        $this->assertStringNotContainsString(
            'CONVERT(varchar(18), a.bRowversion, 1) AS jtl_rowversion_hex',
            $sql,
            'The direct timestamp conversion generated blank values in the operator CSV.'
        );
    }

    public function test_corrected_export_does_not_enable_any_database_writes(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 2) . '/ops/jtl-heidisql-phase8-rowversion-verified-readonly.sql');
        $this->assertIsString($sql);
        $this->assertStringContainsString('DATALENGTH(bRowversion)', $sql);
        $this->assertStringContainsString('MIN_ACTIVE_ROWVERSION()', $sql);

        $normalized = preg_replace('/^--.*$/m', '', $sql);
        $this->assertIsString($normalized);
        $this->assertDoesNotMatchRegularExpression(
            '/\b(?:INSERT|UPDATE|DELETE|ALTER|DROP|TRUNCATE|EXEC(?:UTE)?)\b/i',
            $normalized
        );
    }
}
