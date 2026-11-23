'use strict';
// Safety contract: one HW24 product evidence archive and Amazon work queue;
// real per-user authors stay in trusted storage, not normal-user public JSON.
// Static tests supplement but cannot replace MariaDB two-actor integration tests.
const fs=require('node:fs'),assert=require('node:assert/strict'),path=require('node:path');
const read=p=>fs.readFileSync(path.join(__dirname,'..',p),'utf8');
const aiRead=read('api/ai-reviews.php'),
      aiPost=read('api/ai-review.php'),
      purchase=read('api/purchase-intake.php'),
      amazon=read('api/purchase-enrichment.php'),
      schema=read('platform/ops/hw24-purchase-checklist-v1-manual-migration.sql'),
      ui=read('ankauf.html'),
      filament=read('platform/app/Filament/Pages/IntelligenceWorkbench.php');

// All authorized users should receive identical product facts regardless
// of which account made the original OpenAI request.
assert(aiRead.includes("'scope'=>'shared_workspace'"),'AI GET must declare shared workspace');
assert(aiRead.includes("FROM hw24_ai_reviews WHERE ean IN ($marks)"),
  'bulk summary must not filter original author');
assert(aiRead.includes("FROM hw24_ai_reviews WHERE 1=1"),
  'individual AI archive must include all workspace authors');
assert(!aiRead.includes('WHERE actor=?'), 'AI GET accidentally partitions product evidence by user');
assert(aiRead.includes("authentication_required")&&aiRead.includes("https_required"),
  'AI data remains available only behind HTTPS and authentication');
assert(!aiRead.includes("'actor'=>$row"),'normal archive response must not leak original user');

// A paid request checks the workspace archive FIRST; actor is still
// recorded on truly new, immutable API analysis records.
assert(aiPost.includes('FROM hw24_ai_reviews WHERE ean=? ORDER BY id DESC LIMIT 1'),
  'paid OpenAI path must reuse a prior review made by any HW24 user');
assert(!aiPost.includes('FROM hw24_ai_reviews WHERE actor=?'),
  'paid OpenAI lookup must not regenerate the same product per employee');
assert(aiPost.includes('INSERT INTO hw24_ai_reviews'), 'new AI calls still persist original actor');
assert(aiPost.includes("'basis'=>'shared_workspace_archived_review'"),
  'reuse should report zero new call rather than duplicate provider usage');
assert(aiPost.includes("usd_call'=>0"), 'reused report has zero new OpenAI usage');
assert(aiPost.includes("review_recheck_conflict"),
  'explicit recheck must guard against changed global report');

// Purchase-specific costs are shared evidence, but cannot be invoiced twice
// as if economically created by that particular purchase.
const costStart=purchase.indexOf("if($op==='ai_costs'");
const costEnd=purchase.indexOf("if($op==='article_search'",costStart);
assert(costStart>0 && costEnd>costStart,'missing shared purchase AI costs');
const cost=purchase.slice(costStart,costEnd);
assert(cost.includes("'scope'=>'shared_workspace_same_ean_historical'"));
assert(!cost.includes('WHERE r.actor=?'),
  'purchase cost viewer must not hide product evidence created by colleagues');

// One EAN for the entire workspace, one DB-enforced rate limiter for the
// entire workspace; original requester and actual worker remain in audit.
assert(schema.includes('UNIQUE KEY uq_purchase_amazon_workspace_ean (ean)'),
  'Amazon EAN requests must deduplicate across users');
assert(!schema.includes('UNIQUE KEY uq_purchase_amazon_actor_ean'),
  'per-actor EAN queues would duplicate Amazon calls');
assert(schema.includes("event_type ENUM('queued','linked'"),
  'reusing someone else\'s request is still audited');
assert(schema.includes("scope_key VARCHAR(32) NOT NULL"));
assert(amazon.includes("WHERE ean=? FOR UPDATE"),'global work is locked by EAN');
assert(amazon.includes("WHERE scope_key=? FOR UPDATE"),'rate limit must be global');
assert(amazon.includes("['shared_workspace']"), 'global throttle key must be constant');
assert(amazon.includes("lease_token"),'only verified lease owner may ACK');
assert(amazon.includes("leased_by=? AND lease_token=?"), 'no one can acknowledge someone else\'s lease');
assert(amazon.includes("catalog_snapshot_not_verified"), 'never claim success without actual snapshot');
assert(amazon.includes("event_actor"),'work actor must be preserved for admin auditing');
assert(amazon.includes("enqueueEvent($db,$jobId,$actor,$ean,"),
  'reusing stored result must record who requested it');

// General product UI must not reveal full staff identities.
const queueStart=amazon.indexOf("if($op==='queue'");
const queueEnd=amazon.indexOf("if($op==='enqueue'",queueStart);
const publicGet=amazon.slice(queueStart,queueEnd);
assert(!publicGet.includes('SELECT q.id,q.ean,q.status,q.requested_by'),
  'general queue must not expose the creator account');
assert(!publicGet.includes('SELECT e.event_type,e.event_actor'),
  'general history must not expose staff account identifiers');
assert(!ui.includes('job.requested_by')&&!ui.includes('ev.event_actor'),
  'normal UI must not expose staff actors');
assert(filament.includes("?'shared_workspace':'legacy_authenticated_actor'"),
  'Filament must show whether current deployed API is still legacy-scoped');

console.log('HW24 shared product evidence and cross-actor API dedup contracts: passed');
