/* HW24 scanlist: durable AI review markers from authenticated MariaDB archive.
 * Status derives from hw24_ai_reviews, never browser localStorage.
 * Neither selection nor rendering creates paid OpenAI requests.
 */
(function(root){
'use strict';
const known=new Map();
let eans=[],fetching=false;
function decorate(button,ean){
 if(!button||!ean)return;
 button.dataset.ean=ean;
 button.querySelector('.hw24-ai-marker')?.remove();
 const hit=known.get(ean);
 if(!hit)return;
 const marker=document.createElement('span');
 marker.className='hw24-ai-marker';
 marker.textContent='🤖 KI geprüft'+(hit.count>1?' · '+hit.count+'×':'');
 marker.style.cssText='display:inline-block;margin:4px 4px 0;padding:3px 7px;border-radius:12px;background:#e0f2e8;color:#15553c;font-size:11px;font-weight:650';
 marker.title='Gespeicherter KI-Bericht #'+hit.review_id+' · '+hit.at_utc+
   ' · '+hit.count+' Prüfung(en). Keine neue OpenAI-Abfrage nötig.';
 button.append(marker);
}
function decorateVisible(){
 document.querySelectorAll('#items .scanRow button.item').forEach(b=>decorate(b,b.dataset.ean));
}
async function refresh(){
 if(fetching)return;
 const input=Array.from(new Set(eans.filter(x=>/^\d{8,14}$/.test(x)))).slice(0,150);
 if(!input.length){known.clear();decorateVisible();return;}
 fetching=true;
 try{
  const url='api/ai-reviews.php?summary=1&eans='+encodeURIComponent(input.join(','));
  const response=await fetch(url,{credentials:'same-origin',cache:'no-store'});
  const data=await response.json();
  if(!response.ok||!data.ok||!data.summary)throw Error(data.error||'archiv_status_unavailable');
  known.clear();
  for(const [ean,result] of Object.entries(data.summaries||{})){
   if(/^\d{8,14}$/.test(ean)&&Number.isSafeInteger(result.review_id))
    known.set(ean,result);
  }
  decorateVisible();
  document.dispatchEvent(new CustomEvent('hw24:ai-status-updated',{detail:{count:known.size}}));
 }catch(error){
  // Fail closed: never claim a review does or does not exist if lookup fails.
  known.clear();decorateVisible();
  document.dispatchEvent(new CustomEvent('hw24:ai-status-unavailable'));
 }finally{fetching=false}
}
document.addEventListener('hw24:scan-items-loaded',event=>{
 eans=(event.detail?.items||[]).map(x=>x.ean).filter(Boolean);
 refresh();
});
document.addEventListener('hw24:scan-stored',event=>{
 const ean=event.detail?.ean;
 if(ean&&!eans.includes(ean))eans.push(ean);
 refresh();
});
root.HW24AiMarkers=Object.freeze({decorate,refresh,latest:ean=>known.get(String(ean))||null});
})(window);
