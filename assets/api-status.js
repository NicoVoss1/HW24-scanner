/* HW24 JTL connection check: read-only, same-origin, no credential exposure. */
(function(){
 'use strict';
 const btn=document.getElementById('jtlApiCheck');
 const out=document.getElementById('jtlApiState');
 if(!btn||!out)return;
 const next={
  upgrade_php:'Plesk: PHP-Version mindestens 8.4 einstellen',
  enable_pdo_mysql:'Plesk: Erweiterung pdo_mysql aktivieren',
  configure_read_api:'Private Lesekonfiguration db.php fehlt oder ist unvollständig',
  install_mirror_tables:'MariaDB: Tabellen aus HW24_06_JTL_SPIEGEL.sql fehlen',
  check_mirror_schema:'MariaDB: vorhandene Tabellenstruktur ist unvollständig',
  fix_mariadb_access:'MariaDB-Zugriff prüfen',
  configure_and_run_windows_sync:'Tabellen bereit. Windows-Synchronisation einrichten',
  rerun_windows_sync:'JTL-Snapshot älter als 45 Minuten. Windows-Aufgabe und Synchronisation prüfen',
  query_known_ean:'JTL-Spiegel aktuell. Bekannte EAN scannen'
 };
 async function check(){
  const host=String(location.hostname||'').toLowerCase();
  if(host.endsWith('github.io')){
   out.textContent='GitHub Pages kann nicht auf MariaDB zugreifen. Bitte die HAIRWORLD24-Subdomain öffnen.';
   return;
  }
  btn.disabled=true;
  out.textContent='JTL-API wird geprüft …';
  try{
   const response=await fetch('api/jtl-status.php',{credentials:'same-origin',cache:'no-store'});
   if(!response.ok){out.textContent='HTTP '+response.status+' · Bitte Plesk-Bereitstellung/Website-Schutz prüfen.';return}
   const d=await response.json();
   if(!d||d.service!=='hw24-jtl'){out.textContent='Ungültige API-Antwort. Ist das neue GitHub-Deployment aktiv?';return}
   const message=next[d.next]||'Unbekannter API-Status';
   const count=d.latest_rows!=null&&Number.isFinite(Number(d.latest_rows))?' · '+Number(d.latest_rows).toLocaleString('de-DE')+' Artikel':'';
   const age=d.age_minutes!=null&&Number.isFinite(Number(d.age_minutes))?' · vor '+Number(d.age_minutes)+' Min.':'';
   out.textContent=(d.mirror_ready?'✓ ':'⚠ ')+message+count+age;
   out.dataset.state=d.mirror_ready?'ready':'pending';
   out.title=d.latest_snapshot_utc?'Letzter erfolgreicher JTL-Snapshot (UTC): '+d.latest_snapshot_utc:'Noch kein JTL-Snapshot vorhanden';
  }catch(e){
   out.textContent='API aktuell nicht erreichbar. HTTPS, Plesk-Schutz und jtl-status.php prüfen.';
  }finally{btn.disabled=false}
 }
 btn.addEventListener('click',check);
 check(); // Show the last completed sync immediately, without requiring a click.
 setInterval(()=>{if(!document.hidden)check()},5*60*1000); // Refresh dashboard when the page stays open.
})();
