'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const server=fs.readFileSync('api/ai-review.php','utf8');
const ui=fs.readFileSync('assets/hw24-ai-cost.js','utf8');
for(const item of ['upstream_http','upstream_code','upstream_type','upstream_param','network_errno']) {
 assert(server.includes("'"+item+"'"),'server should provide '+item);
}
assert(server.includes("safeCode"),'server must sanitize errors');
assert(server.includes("curl_errno"),'network failure must be classified');
assert(!server.includes("'upstream_message'"),'do not return free-form upstream text');
assert(!server.includes("'api_key'=>"),'never echo API keys');
for(const code of [0,400,401,403,404,429,500,502,503]){
 assert(ui.includes('http==='+code),'UI should explain '+code);
}
assert(ui.includes('explainAiFailure(x)'), 'render detailed error reason');
assert(ui.includes('nicht wiederholt auslösen'), 'avoid repeated billing attempts');
console.log('OpenAI safe HTTP diagnostics, network and quota cases: PASS');
