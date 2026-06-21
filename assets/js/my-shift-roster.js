(function(){
  const API_BASES=["/api","/backend/api.php?path=/api","/backend/api.php?path=/api"];
  const $=(id)=>document.getElementById(id);
  const qs=new URLSearchParams(location.search);
  function esc(v){return String(v??"").replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;");}
  async function api(path){for(const b of API_BASES){try{const r=await fetch(b+path,{cache:'no-store'}); if(r.status===404||r.status===405) continue; const j=await r.json(); if(!r.ok) throw new Error(j.detail||'Error'); return j;}catch(e){}} throw new Error('API unavailable');}
  async function load(){
    const from=$("fromDate").value; const to=$("toDate").value; const empId=qs.get('empId')||$("empId").value;
    const d=await api(`/my-shifts?from=${from}&to=${to}&empId=${encodeURIComponent(empId)}`);
    if($("empId")) $("empId").value=d.empId||empId;
    const t=d.todayShift; $("todayCard").innerHTML=t?`<div class="fw-semibold">${esc(t.shiftCode)} - ${esc(t.shiftName)}</div><div class="small text-muted-3">${esc(t.rosterDate)} | ${esc(t.startTime||'-')} - ${esc(t.endTime||'-')}</div>`:'<div class="text-muted">No shift for today</div>';
    $("myShiftBody").innerHTML=(d.rows||[]).map(r=>`<tr><td>${esc(r.rosterDate)}</td><td>${new Date(r.rosterDate+'T00:00:00').toLocaleDateString(undefined,{weekday:'short'})}</td><td>${esc(r.shiftCode)}</td><td>${esc(r.shiftName)}</td><td>${esc(r.startTime||'-')}</td><td>${esc(r.endTime||'-')}</td><td>${esc(r.shiftType)}</td></tr>`).join('')||'<tr><td colspan="7" class="text-center text-muted">No records</td></tr>';
  }
  document.addEventListener('DOMContentLoaded',()=>{const t=new Date(); const from=new Date(t); from.setDate(t.getDate()-3); const to=new Date(t); to.setDate(t.getDate()+14); $("fromDate").value=from.toISOString().slice(0,10); $("toDate").value=to.toISOString().slice(0,10); $("btnLoad").addEventListener('click',()=>load().catch(e=>alert(e.message))); load().catch(()=>{});});
})();
