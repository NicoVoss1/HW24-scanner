'use strict';
/**
 * Guard the SINGLE shared product/research data model.
 * Pure source contracts only: NOT an integration/API/role authorization test.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
const read=p=>fs.readFileSync(path.join(__dirname,'..',p),'utf8');
const ai=read('api/ai-reviews.php');
const review=read('api/ai-review.php');
const amazon=read('api/amazon.php');
const queue=read('api/purchase-enrichment.php');
const schema=read('platform/ops/hw24-purchase-checklist-v1-manual-migration.sql');
const purchase=read('api/purchase-intake.php');
const bridge=read('platform/app/Services/LegacyReadBridge.php');
const ensure=(value,msg)=>assert(value,msg);
ensure(ai.includes("'scope'=>'shared_workspace'"),'AI archive must declare shared workspace');
ensure(ai.includes('FROM hw24_ai_reviews WHERE 1=1'),'AI archive must not split by actor');
ensure(!/FROM hw24_ai_reviews WHERE actor\s*=\s*\?/i.test(ai),'AI archive cannot filter by actor');
ensure(review.includes('FROM hw24_ai_reviews WHERE ean=?'),
 'New chargeable AI review must reuse stored EAN research across users');
ensure(!/FROM hw24_ai_reviews WHERE actor\s*=\s*\?\s+AND\s+ean\s*=\s*\?/i.test(review),
 'AI reuse must not be per-user');
ensure(amazon.includes('hw24_amazon_catalog_snapshots'),'Amazon must use shared durable catalog snapshots');
ensure(amazon.includes('GET_LOCK('),'Concurrent Amazon requests must be coalesced');
ensure(amazon.includes('workspace_cache_recheck'),'Concurrent request must reread cache after lock');
ensure(schema.includes('UNIQUE KEY uq_purchase_amazon_workspace_ean (ean)'),
 'Shared Amazon backlog must be deduplicated per EAN');
ensure(schema.includes('PRIMARY KEY(scope_key)'),'One workspace quota token, not one per user');
ensure(queue.includes("['shared_workspace']"),'Amazon queue must throttle all workers together');
ensure(queue.includes('WHERE ean=? FOR UPDATE'),'Concurrent same-EAN enqueues must share job');
ensure(queue.includes('event_actor'),'Original actor must remain in admin audit');
ensure(queue.includes('SELECT e.event_type,e.source_kind'),
 'Worker-facing product history may show status without staff identity');
ensure(!queue.includes('SELECT e.event_type,e.event_actor,e.source_kind'),
 'Staff names should not leak to general product data endpoint');
ensure(purchase.includes("'scope'=>'shared_workspace_same_ean_historical'"),
 'Purchase-level AI evidence uses shared EAN history');
ensure(!purchase.includes('WHERE r.actor=?'),'Purchase AI-cost archive must not be actor-split');
ensure(bridge.includes("Http::withBasicAuth($user, $password)"),
 'Legacy bridge uses one technical actor; do not mislabel it as person');
ensure(purchase.includes("WHERE p.id=? AND p.created_by=?"),
 'Purchase transaction isolation must remain until tested team RBAC is installed');
console.log('Shared HW24 product archive and user-audit separation: contract passed');

/* V2 contract is opt-in; this is source verification, not a provider call. */
ensure(review.includes("'review_versions'=>[1,2]"),'Advertise supported versions before charging');
ensure(review.includes("$body['review_version']??1"),'Legacy client defaults to V1');
ensure(review.includes("v2_verified_single_candidate_required"),'V2 requires one verified single candidate');
ensure(review.includes("'product_facts'=>$productFacts"),'Archive extended source facts with input');
ensure(review.includes("'contentVersion'")&&review.includes("'metaDescription'")&&review.includes("'shopifyAttributes'")&&review.includes("'ebayAttributes'"),'Extended strict output contract');
ensure(review.indexOf('review_duplicate_check_unavailable')<review.indexOf('curl_init(\'https://api.openai.com/v1/responses\')'),'Check duplicate archive before provider call');
ensure(review.includes('Keine nicht belegten Wirkungs-'),'No unsupported product benefit claims');
ensure(review.includes("'max_output_tokens'=>$reviewVersion===2?2500:1250"),'Bound output by version');
console.log('HW24 optional V2 AI copy contract: passed');
