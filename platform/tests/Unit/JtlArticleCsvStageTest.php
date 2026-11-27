<?php
declare(strict_types=1);

namespace Tests\Unit;

use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class JtlArticleCsvStageTest extends TestCase
{
    private string $dir;
    private string $input;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/hw24_stage_' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0700);
        $this->input = $this->dir . '/articles.csv';
        $this->writeFixture();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->dir);
        parent::tearDown();
    }

    private function writeFixture(bool $duplicateArticleNumber = false): void
    {
        $header = [
            'jtl_article_id', 'jtl_article_number', 'jtl_barcode',
            'jtl_active_raw', 'jtl_deleted_raw', 'jtl_sales_net_raw',
            'jtl_uvp_raw', 'jtl_amazon_price_field_raw', 'jtl_ebay_price_field_raw',
            'jtl_tax_class_id', 'jtl_tax_class_label', 'jtl_delivery_status_id',
            'jtl_delivery_label_language_1', 'jtl_unit_id', 'jtl_unit_symbol',
            'jtl_unit_quantity_raw', 'jtl_article_stock_raw',
            'jtl_last_modified_raw', 'jtl_rowversion_hex',
        ];
        $rows = [
            [
                '3', '0000030160002', '0000030160002',
                'Y', '0', '6.71428571', '0', '8.99', '5.99',
                '1', 'Normaler Steuersatz', '1', '2-4 Werktage',
                '16', 'ml', '90', '0', '2026-11-22 12:00:00', '',
            ],
            [
                '4', $duplicateArticleNumber ? '0000030160002' : 'ART-4',
                '\\N', 'Y', '\\N', '10.125', '0', '0', '0',
                '1', 'Normaler Steuersatz', '0', '\\N',
                '0', '\\N', '0.06', '0', '2026-11-22 12:30:00', '',
            ],
        ];
        $f = fopen($this->input, 'wb');
        fputcsv($f, $header, ';', '"', '');
        foreach ($rows as $row) {
            fputcsv($f, $row, ';', '"', '');
        }
        fclose($f);
    }

    private function invoke(array $args): Process
    {
        $command = array_merge(
            [PHP_BINARY, __DIR__ . '/../../ops/jtl-article-csv-stage.php'],
            $args
        );
        $process = new Process($command);
        $process->run();
        return $process;
    }

    public function test_default_validation_does_not_create_a_database(): void
    {
        $process = $this->invoke(['--input=' . $this->input]);
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $data = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('validate_only', $data['mode']);
        $this->assertSame(2, $data['article_count']);
        $this->assertSame(1, $data['quality']['leading_zero_barcodes']);
        $this->assertSame(1, $data['quality']['missing_barcodes']);
        $this->assertSame(2, $data['quality']['blank_rowversions']);
        $this->assertFalse($data['automatic_sync_enabled']);
        $this->assertFalse($data['rowversion_watermark_available']);
        $this->assertFileDoesNotExist($this->dir . '/stage.sqlite');
    }

    public function test_isolated_apply_needs_exact_checksum_and_never_overwrites(): void
    {
        $db = $this->dir . '/stage.sqlite';
        $bad = $this->invoke([
            '--input=' . $this->input, '--apply', '--db=' . $db,
            '--expected-count=2', '--sha256=' . str_repeat('0', 64),
        ]);
        $this->assertFalse($bad->isSuccessful());
        $this->assertFileDoesNotExist($db);

        $okay = $this->invoke([
            '--input=' . $this->input, '--apply', '--db=' . $db,
            '--expected-count=2', '--sha256=' . hash_file('sha256', $this->input),
        ]);
        $this->assertTrue($okay->isSuccessful(), $okay->getErrorOutput());
        $this->assertFileExists($db);
        $sqlite = new PDO('sqlite:' . $db);
        $this->assertSame(2, (int) $sqlite->query('SELECT COUNT(*) FROM jtl_article_stage')->fetchColumn());
        $row = $sqlite->query('SELECT jtl_article_number,jtl_barcode,jtl_unit_quantity_raw FROM jtl_article_stage WHERE jtl_article_id=3')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('0000030160002', $row['jtl_article_number']);
        $this->assertSame('0000030160002', $row['jtl_barcode']);
        $this->assertSame('90', $row['jtl_unit_quantity_raw']);
        $this->assertNull($sqlite->query('SELECT jtl_barcode FROM jtl_article_stage WHERE jtl_article_id=4')->fetchColumn() ?: null);
        $this->assertSame(0, (int) $sqlite->query('SELECT is_consistent_cross_table_snapshot FROM stage_manifest WHERE id=1')->fetchColumn());
        unset($sqlite);

        $repeat = $this->invoke([
            '--input=' . $this->input, '--apply', '--db=' . $db,
            '--expected-count=2', '--sha256=' . hash_file('sha256', $this->input),
        ]);
        $this->assertFalse($repeat->isSuccessful());
    }

    public function test_duplicate_article_number_fails_before_writing(): void
    {
        $this->writeFixture(true);
        $bad = $this->invoke(['--input=' . $this->input]);
        $this->assertFalse($bad->isSuccessful());
        $this->assertStringContainsString('duplicate JTL article', $bad->getErrorOutput());
        $this->assertFileDoesNotExist($this->dir . '/stage.sqlite');
    }

    public function test_cannot_write_into_application_tree_or_public_project(): void
    {
        $process = $this->invoke([
            '--input=' . $this->input, '--apply',
            '--db=' . realpath(__DIR__ . '/../../') . '/private-stage.sqlite',
            '--expected-count=2',
            '--sha256=' . hash_file('sha256', $this->input),
        ]);
        $this->assertFalse($process->isSuccessful());
    }
}
