<?php
declare(strict_types=1);
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;

final class HubSharedWorkflowContractTest extends TestCase
{
    public function test_both_article_paths_reuse_catalog_facts_and_decision_table():void
    {
        $root=dirname(__DIR__,2);
        foreach(['HubBatchPreparation','HubExistingArticleExport'] as $name){
            $code=file_get_contents($root.'/app/Services/'.$name.'.php');
            self::assertStringContainsString('HubCatalogFacts::productFields(', $code);
        }
        foreach(['article-lookup','scan-workbench','reference-table-workbench'] as $name){
            $code=file_get_contents($root.'/resources/views/filament/pages/'.$name.'.blade.php');
            self::assertStringContainsString('filament.partials.hub-field-decisions',$code);
        }
        foreach(['article-lookup','scan-workbench'] as $name){
            $code=file_get_contents($root.'/resources/views/filament/pages/'.$name.'.blade.php');
            self::assertStringContainsString('Recherchewerte aufbereiten',$code);
        }
    }
}
