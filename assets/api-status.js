/* HW24 JTL connection check: read-only, same-origin, no credential exposure. */
(function(){
 'use strict';
 const btn=document.getElementById('jtlApiCheck');
 const out=document.getElementById('jtlApiState');
 const label=document.getElementById('jtlApiLabel');
 const detail=document.getElementById('jtlApiDetail');
 const capabilities=document.getElementById('jtlApiCapabilities');
 if(!btn||!out||!label||!detail)return;
 const next={
  upgrade_php:'Plesk: PHP-Version mindestens 8.4 einstellen',
  enable_pdo_mysql:'Plesk: Erweiterung pdo_mysql aktivieren',
  configure_read_api:'Private Lesekonfiguration db.php fehlt oder ist unvollständig',
  install_mirror_tables:'MariaDB: Tabellen aus HW24_06_JTL_SPIEGEL.sql fehlen',
  check_mirror_schema:'MariaDB: vorhandene Tabellenstruktur ist unvollständig',
  fix_mariadb_access:'MariaDB-Zugriff prüfen',
  configure_and_run_windows_sync:'Tabellen bereit. Windows-Synchronisation einrichten',
  rerun_windows_sync:'Windows-Synchronisation prüfen',
  query_known_ean:'JTL-Spiegel aktuell'
 };
 const snapshotFormat=new Intl.DateTimeFormat('de-DE',{
  timeZone:'Europe/Berlin',day:'2-digit',month:'2-digit',
  hour:'2-digit',minute:'2-digit',timeZoneName:'short'
 });
 function show(state,message,context=''){
  out.dataset.state=state;
  label.textContent=message;
  detail.textContent=context;
 }
 async function check(){
  const host=String(location.hostname||'').toLowerCase();
  if(host.endsWith('github.io')){
   show('pending','JTL-Spiegel in der Vorschau nicht verfügbar','Artikelmanager auf hairworld24.de öffnen.');
   return;
  }
  btn.disabled=true;
  show('loading','JTL-Spiegel wird geprüft …');
  if(capabilities)capabilities.textContent='';
  try{
   const response=await fetch('api/jtl-status.php',{credentials:'same-origin',cache:'no-store'});
   if(!response.ok){show('error','JTL-Status nicht verfügbar','HTTP '+response.status);return}
   const d=await response.json();
   if(!d||d.service!=='hw24-jtl'){show('error','Ungültige Antwort der JTL-API');return}
   const wms=d.pipeline_sources||{};
   const missing=[
    wms.wms_lagerplaetze==='not_integrated'?'WMS-Lagerplätze nicht im HW24-Sync':'',
    wms.wms_bewegungen==='not_integrated'?'WMS-Bewegungen nicht im HW24-Sync':'',
    wms.amazon_eigene_angebote==='not_integrated'?'JTL-Amazon-Angebote nicht übertragen':'',
    wms.ebay_angebotsvorlagen==='not_integrated'?'eBay-Vorlagen nicht übertragen':''
   ].filter(Boolean);
   if(capabilities&&missing.length)capabilities.textContent='Datenumfang des versionierten Sync-Skripts: '+missing.join(', ')+'.';
   const raw=String(d.latest_snapshot_utc??'').replace(' ','T');
   const snapshot=Date.parse(/(?:Z|[+-]\d{2}:?\d{2})$/i.test(raw)?raw:raw+'Z');
   const rows=Number.isFinite(d.latest_rows)?d.latest_rows.toLocaleString('de-DE')+' Artikel':'';
   const stamp=Number.isFinite(snapshot)?snapshotFormat.format(snapshot):'';
   const context=[rows,stamp].filter(Boolean).join(' · ');
   const message=next[d.next]||'Unbekannter API-Status';
   if(d.mirror_ready)show('ready','JTL-Spiegel aktuell',context);
   else show('pending',d.next==='rerun_windows_sync'?'JTL-Spiegel nicht aktuell':'JTL-Spiegel noch nicht bereit',[message,context].filter(Boolean).join(' · '));
  }catch(e){
   show('error','JTL-API nicht erreichbar','Plesk-Schutz und JTL-Status prüfen.');
  }finally{btn.disabled=false}
 }
 btn.addEventListener('click',check);
 check();
})();
