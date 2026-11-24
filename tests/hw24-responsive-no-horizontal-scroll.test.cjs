'use strict';

/**
 * HW24 admin UI: regression gate for desktop/sidebar/mobile layouts.
 * Static source contract only, not a replacement for logged-in browser tests.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const pages=[
  'platform/resources/views/filament/pages/warehouse-price-workbench.blade.php',
  'platform/resources/views/filament/pages/scan-workbench.blade.php',
  'platform/resources/views/filament/pages/article-lookup.blade.php',
  'platform/resources/views/filament/pages/intelligence-workbench.blade.php',
  'platform/resources/views/filament/pages/customer-service-workbench.blade.php',
  'platform/resources/views/filament/pages/legacy-workspace.blade.php',
  'platform/resources/views/filament/widgets/hw24-system-overview.blade.php',
];
for (const file of pages){
  const html=fs.readFileSync(file,'utf8');
  assert(html.includes('max-width')||html.includes('minmax('),file+' needs responsive width containment');
  assert(!/overflow-x\s*:\s*(auto|scroll)/i.test(html),file+' forces horizontal scrolling');
  assert(!/min-width\s*:\s*(?:[5-9]\d{2}|1\d{3})px/i.test(html),
    file+' uses a fixed wide min-width');
  const tables=[...html.matchAll(/<table\b[^>]*>([\s\S]*?)<\/table>/g)];
  for(const [,inner] of tables){
    assert(inner.includes('<thead'),file+' table must retain accessible header');
    for (const [,attrs] of inner.matchAll(/<td\b([^>]*)>/g)){
      assert(/\bdata-label\s*=|\bcolspan\s*=/.test(attrs),
        file+' missing responsive card label on a table cell');
    }
  }
  if(tables.length){
    assert(html.includes('@container'),file+' needs container-width responsive card rules');
    assert(html.includes('overflow-wrap:anywhere'),
      file+' must wrap unbroken source identifiers without overflow');
    assert(html.includes('content:attr(data-label)'),
      file+' responsive card label missing');
  }
}
const wms=fs.readFileSync(pages[0],'utf8');
assert(wms.includes('<section class="panel full" aria-labelledby="wms-history">'),
  'Movement history must use full panel width, never half-width grid');
assert(wms.includes('data-label="Von Lagerplatz"')&&wms.includes('data-label="Nach Lagerplatz"'),
  'Movement history must display both bin labels in responsive view');
assert(wms.includes('data-label="Fachstatus"')&&wms.includes('data-label="Weitere Quellangaben"'),
  'WMS bin information must remain available after responsive layout change');
const scan=fs.readFileSync(pages[1],'utf8');
assert(scan.includes('data-label="KI-Archiv"')&&scan.includes('data-label="Aktion"'),
  'Scanlist must preserve review and action buttons in responsive cards');
const article=fs.readFileSync(pages[2],'utf8');
assert(article.includes('data-label="Originalwert"')&&article.includes('data-label="VK netto JTL"'),
  'Article values must not be lost in responsive cards');
console.log('HW24 responsive admin: no forced horizontal scrolling in 7 page templates, 6 labeled tables PASS');
