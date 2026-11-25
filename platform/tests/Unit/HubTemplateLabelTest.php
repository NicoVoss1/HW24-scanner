<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubArticleMirror;
use PHPUnit\Framework\TestCase;

final class HubTemplateLabelTest extends TestCase
{
    public function test_only_exact_operator_confirmed_label(): void
    {
        foreach(['Vorlagen zur Anlage',' VORLAGEN ZUR ANLAGE ','Neu; Vorlagen zur Anlage','Vorlagen zur Anlage, Pflege','A|Vorlagen zur Anlage'] as $s)
            self::assertTrue(HubArticleMirror::hasTemplateLabel($s),$s);
        foreach(['Vorlage','Vorlagen','Vorlage zur Anlage','Keine Vorlagen zur Anlage','Vorlagenarchiv',''] as $s)
            self::assertFalse(HubArticleMirror::hasTemplateLabel($s),$s);
    }
    public function test_inconsistent_template_volume_is_excluded(): void
    {
        self::assertTrue(HubArticleMirror::consistentVolume('Haarfarbe (60 ml)','60.0000','ml'));
        self::assertFalse(HubArticleMirror::consistentVolume('Haarfarbe (60 ml)','0.06','ml'));
        self::assertFalse(HubArticleMirror::consistentVolume('Haarfarbe (60 ml)','50','ml'));
    }
}
