
(function(){
  "use strict";
  const API_BASES=["/api","/backend/api.php?path=/api","/backend/api.php?path=/api"];
  const $=(id)=>document.getElementById(id);
  const isSuper=(location.pathname||"").toLowerCase().includes('/super-admin/');
  const state={clients:[],employees:[],shifts:[],assignments:[],rosterRows:[],reportRows:[],weekLocked:false,calendar:null,rosterMap:{},rosterNotes:{},rosterHalfDay:{},currentCell:null,selectedCellShift:"",monthDates:[],selectedCells:new Set()};

  function esc(v){return String(v??"").replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#39;");}
  function weekDates(start){const d=new Date(start+"T00:00:00");const o=[];for(let i=0;i<7;i++){const x=new Date(d);x.setDate(d.getDate()+i);o.push(x.toISOString().slice(0,10));}return o;}
  function monthDates(year,month){
    const out=[]; const dim=new Date(year,month,0).getDate();
    for(let d=1; d<=dim; d++){ out.push(new Date(Date.UTC(year,month-1,d)).toISOString().slice(0,10)); }
    return out;
  }
  function dayShort(iso){ return new Date(iso+"T00:00:00").toLocaleDateString(undefined,{weekday:"short"}); }
  function parseNote(raw){
    const x=String(raw||"");
    const m=x.match(/\|\s*HalfDay:\s*(First Half|Second Half|No)\s*$/i);
    if(!m) return {note:x.trim(),halfDay:"No"};
    return {note:x.replace(m[0],"").trim(),halfDay:m[1]};
  }
  function composeNote(note,halfDay){
    const n=String(note||"").trim();
    const h=String(halfDay||"No");
    if(h && h!=="No") return n?`${n} | HalfDay: ${h}`:`HalfDay: ${h}`;
    return n;
  }
  function requiresNote(code){
    return ["CL","SL","EL","LOP","LV"].includes(String(code||"").toUpperCase());
  }
  function today(){return new Date().toISOString().slice(0,10);}
  function mondayOf(dt){const d=new Date(dt+"T00:00:00");const day=(d.getDay()+6)%7;d.setDate(d.getDate()-day);return d.toISOString().slice(0,10);}
  function csvCell(v){const s=String(v??""); return /[",\n]/.test(s)?`"${s.replaceAll('"','""')}"`:s;}
  function exportCsv(lines,filename){
    const blob=new Blob([lines.join('\n')],{type:'text/csv'});
    const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=filename; a.click(); URL.revokeObjectURL(a.href);
  }
  function weekBuckets(dates){
    const m=new Map();
    dates.forEach((dt)=>{
      const ws=mondayOf(dt);
      if(!m.has(ws)) m.set(ws,[]);
      m.get(ws).push(dt);
    });
    return [...m.entries()].sort((a,b)=>a[0].localeCompare(b[0]));
  }

  async function api(path,opts={}){
    let lastErr="API unavailable";
    for(const b of API_BASES){
      try{
        const r=await fetch(b+path,{cache:"no-store",...opts});
        if(r.status===404||r.status===405){lastErr=`${r.status} ${path}`;continue;}
        if(!r.ok){let m=`${r.status}`; try{const j=await r.json();m=j.detail||JSON.stringify(j);}catch(_e){} throw new Error(m);} 
        const ct=r.headers.get("content-type")||"";
        if(ct.includes("application/json")) return await r.json();
        return r;
      }catch(e){lastErr=e.message||String(e);}
    }
    throw new Error(lastErr);
  }
  function showMsg(msg,ok){const el=$("shiftMsg"); if(!el) return; el.className=`alert ${ok?"alert-success":"alert-danger"}`; el.textContent=msg; el.classList.remove("d-none"); setTimeout(()=>el.classList.add("d-none"),3500);}
  function getCompanyId(){
    const x=Number($("companyFilter")?.value||0);
    if(Number.isFinite(x)&&x>0) return x;
    if(isSuper && state.clients.length) return Number(state.clients[0].id||0);
    return 0;
  }
  function companyQuery(){const cid=getCompanyId(); return cid>0?`&companyId=${cid}`:"";}
  function multiMode(){ return !!$("multiSelectMode")?.checked; }
  function updateSelectedCount(){ if($("multiSelectedCount")) $("multiSelectedCount").textContent=`${state.selectedCells.size} selected`; }
  function clearSelectedCells(updateDom){
    state.selectedCells.clear();
    if(updateDom){
      document.querySelectorAll('[data-roster-cell].multi-selected').forEach((el)=>el.classList.remove('multi-selected'));
    }
    updateSelectedCount();
  }
  function visibleRosterEmployees(){
    const dep=$("rosterDepartment")?.value||"";
    const des=$("rosterDesignation")?.value||"";
    const empRaw=$("rosterEmpSearch")?.value||"";
    const emp=String(empRaw).split(" - ")[0].trim() || String(empRaw).trim();
    return state.employees.filter(e=>String(e.status||'').toLowerCase()!=='inactive')
      .filter(e=>!dep || String(e.dept||'').toLowerCase()===dep.toLowerCase())
      .filter(e=>!des || String(e.desig||'').toLowerCase()===des.toLowerCase())
      .filter(e=>!emp || String(e.id||"")===String(emp) || String(e.id||'').toLowerCase().includes(String(emp).toLowerCase()) || String(e.name||'').toLowerCase().includes(String(empRaw).toLowerCase()) || String(e.name||'').toLowerCase().includes(String(emp).toLowerCase()));
  }
  function syncRosterHeaderBadges(){
    const mSel = $("rosterMonth");
    const ySel = $("rosterYear");
    const m = Number(mSel?.value || 0);
    const y = Number(ySel?.value || 0);
    const mName = (m >= 1 && m <= 12) ? new Date(2000, m - 1, 1).toLocaleString(undefined, { month: 'short' }) : '-';
    if($("rosterMonthBadge")) $("rosterMonthBadge").textContent = `Month: ${mName}`;
    if($("rosterYearBadge")) $("rosterYearBadge").textContent = `Year: ${y || '-'}`;
  }

  async function loadClients(){
    if(!isSuper) return;
    const d=await api('/clients'); state.clients=Array.isArray(d.rows)?d.rows:[];
    const sel=$("companyFilter"); if(!sel) return;
    sel.innerHTML='<option value="0">All Companies</option>'+state.clients.map(c=>`<option value="${Number(c.id)}">${esc(c.companyName)}</option>`).join("");
    if(state.clients.length && Number(sel.value||0)===0) sel.value=String(Number(state.clients[0].id||0));
  }
  async function loadEmployees(){
    const cid=getCompanyId();
    const q=isSuper?`?companyId=${cid||0}`:"";
    const d=await api('/employees'+q);
    state.employees=Array.isArray(d.rows)?d.rows:[];
    const opts=state.employees.map(e=>`<option value="${esc(e.id)}">${esc(e.id)} - ${esc(e.name)}</option>`).join("");
    if($("assignEmp")) $("assignEmp").innerHTML='<option value="">Select employee</option>'+opts;
    const rosterEmpSel=$("rosterEmpSearch");
    const rosterEmpList=$("rosterEmpSearchList");
    if(rosterEmpSel && rosterEmpList){
      const prev=String(rosterEmpSel.value||"");
      rosterEmpList.innerHTML=state.employees.map(e=>`<option value="${esc(e.id)} - ${esc(e.name)}"></option>`).join("");
      if(prev) rosterEmpSel.value=prev;
    }
  }
  async function loadShifts(){
    const d=await api('/shifts?all='+(isSuper?'1':'0')+companyQuery());
    state.shifts=Array.isArray(d.rows)?d.rows:[];
    const tbody=$("shiftMasterBody"); if(!tbody) return;
    tbody.innerHTML=state.shifts.map(r=>`<tr><td>${esc(r.shiftCode)}</td><td>${esc(r.shiftName)}</td><td><span class="badge text-bg-light border">${esc(r.shiftType)}</span></td><td>${esc(r.startTime||'-')} - ${esc(r.endTime||'-')}</td><td><span class="shift-color-chip" style="background:${esc(r.colorCode)}"></span></td><td>${esc(r.status)}</td><td><button class="btn btn-sm btn-outline-primary" data-edit-shift="${r.id}">Edit</button> <button class="btn btn-sm btn-outline-danger" data-del-shift="${r.id}">Delete</button></td></tr>`).join("")||'<tr><td colspan="7" class="text-center text-muted">No shifts</td></tr>';
    const shiftOpts=state.shifts.filter(s=>s.status==='Active').map(s=>`<option value="${esc(s.shiftCode)}">${esc(s.shiftCode)} - ${esc(s.shiftName)}</option>`).join("");
    if($("assignDefaultShift")) $("assignDefaultShift").innerHTML='<option value="">Select shift</option>'+shiftOpts;
    if($("rosterShiftFilter")) $("rosterShiftFilter").innerHTML='<option value="">All Shifts</option>'+shiftOpts;
    if($("legendRow")) $("legendRow").innerHTML=state.shifts.filter(s=>s.status==='Active').map(s=>`<div class="shift-legend-item"><span class="shift-color-chip" style="background:${esc(s.colorCode)}"></span><span>${esc(s.shiftCode)}</span></div>`).join("");
    if($("multiShiftCode")) $("multiShiftCode").innerHTML='<option value="">Select shift</option>'+shiftOpts;
  }

  async function saveShift(){
    const p={id:Number($("shiftId").value||0),companyId:getCompanyId(),shiftCode:$("shiftCode").value,shiftName:$("shiftName").value,startTime:$("shiftStart").value||null,endTime:$("shiftEnd").value||null,breakMinutes:Number($("shiftBreak").value||0),totalHours:Number($("shiftHours").value||0),shiftType:$("shiftType").value,lateGraceMinutes:Number($("shiftGrace").value||0),halfDayHours:Number($("shiftHalfDay").value||0),otEligible:$("shiftOT").checked,colorCode:$("shiftColor").value,status:$("shiftStatus").value};
    if(p.id>0) await api(`/shifts/${p.id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(p)}); else await api('/shifts',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(p)});
    bootstrap.Modal.getOrCreateInstance($("shiftModal")).hide();
    await loadShifts();
    showMsg('Shift saved',true);
  }

  async function loadAssignments(){
    const d=await api('/shift-assignments?all='+(isSuper?'1':'0')+companyQuery());
    state.assignments=Array.isArray(d.rows)?d.rows:[];
    const tbody=$("assignmentBody"); if(!tbody) return;
    tbody.innerHTML=state.assignments.map(r=>`<tr><td>${esc(r.empId)}</td><td>${esc(r.employeeName)}</td><td>${esc(r.defaultShiftCode)}</td><td>${esc(r.weeklyOffDay)}</td><td>${esc(r.effectiveFrom)}</td><td>${esc(r.status)}</td><td><button class="btn btn-sm btn-outline-primary" data-edit-assn="${r.id}">Edit</button> <button class="btn btn-sm btn-outline-danger" data-del-assn="${r.id}">Delete</button></td></tr>`).join("")||'<tr><td colspan="7" class="text-center text-muted">No assignments</td></tr>';
  }
  async function saveAssignment(){
    const p={id:Number($("assignId").value||0),companyId:getCompanyId(),empId:$("assignEmp").value,defaultShiftCode:$("assignDefaultShift").value,weeklyOffDay:$("assignWeeklyOff").value,effectiveFrom:$("assignEffective").value,status:$("assignStatus").value};
    if(p.id>0) await api(`/shift-assignments/${p.id}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(p)}); else await api('/shift-assignments',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(p)});
    await loadAssignments(); showMsg('Assignment saved',true);
  }
  async function loadRoster(){
    const month=Number($("rosterMonth").value||new Date().getMonth()+1);
    const year=Number($("rosterYear").value||new Date().getFullYear());
    syncRosterHeaderBadges();
    const dates=monthDates(year,month);
    const ws=dates[0], we=dates[dates.length-1];
    state.monthDates = dates;
    const dep=$("rosterDepartment")?.value||""; const des=$("rosterDesignation")?.value||"";
    const d=await api(`/rosters?from=${ws}&to=${we}&all=${isSuper?1:0}${companyQuery()}&department=${encodeURIComponent(dep)}&designation=${encodeURIComponent(des)}&empId=&shiftCode=`);
    state.rosterRows=Array.isArray(d.rows)?d.rows:[];
    const w=await api(`/rosters/week-status?weekStartDate=${ws}&weekEndDate=${we}${companyQuery()}`);
    state.weekLocked=!!w.isLocked;
    if($("weekLockBadge")) $("weekLockBadge").textContent=state.weekLocked?"Locked":"Unlocked";
    const map={}, notes={}, half={};
    state.rosterRows.forEach(r=>{const k=r.empId+'|'+r.rosterDate; map[k]=r.shiftCode; const p=parseNote(r.notes||""); notes[k]=p.note; half[k]=p.halfDay;});
    state.rosterMap=map; state.rosterNotes=notes; state.rosterHalfDay=half;
    const emps=visibleRosterEmployees();

    const head=$("rosterHead");
    head.innerHTML=`<tr><th class="employee-col">Employee</th>${dates.map(dt=>`<th><div class="roster-head-num">${Number(dt.slice(8,10))}</div><div class="roster-head-day">${esc(dayShort(dt))}</div></th>`).join('')}</tr>`;
    const shiftByCode={}; state.shifts.forEach(s=>shiftByCode[String(s.shiftCode)]=s);
    const body=$("rosterBody");
    body.innerHTML=emps.map(e=>{
      const cells=dates.map(dt=>{
        const cellKey=e.id+'|'+dt;
        const code=map[cellKey]||'';
        const sh=shiftByCode[code];
        const bg = sh ? `${sh.colorCode}22` : 'transparent';
        const bd = sh ? `${sh.colorCode}66` : '';
        const selClass=state.selectedCells.has(cellKey)?'multi-selected':'';
        return `<td><button type="button" class="roster-pill ${code?'':'empty'} ${selClass}" data-roster-cell="1" data-emp="${esc(e.id)}" data-date="${dt}" style="background:${bg};border-color:${bd};" ${state.weekLocked?'disabled':''}>${esc(code||'-')}</button></td>`;
      }).join('');
      return `<tr><td class="employee-col"><div class="fw-semibold">${esc(e.name)}</div><div class="small text-muted-3">${esc(e.id)}</div></td>${cells}</tr>`;
    }).join('')||`<tr><td class="text-center text-muted" colspan="${dates.length+1}">No employees</td></tr>`;
    updateSelectedCount();
  }
  function exportWeeklyRoster(fromDate,toDate){
    const allDates=(state.monthDates||[]).slice().sort((a,b)=>a.localeCompare(b));
    if(!allDates.length){ showMsg('No roster data to export.', false); return; }
    const from=String(fromDate||allDates[0]||"");
    const to=String(toDate||allDates[allDates.length-1]||"");
    if(!from || !to){ showMsg('Select From/To date.', false); return; }
    if(from>to){ showMsg('From date cannot be after To date.', false); return; }
    const dates=allDates.filter((d)=>d>=from && d<=to);
    if(!dates.length){ showMsg('No roster dates in selected range.', false); return; }
    const emps=visibleRosterEmployees();
    const weeks=weekBuckets(dates);
    const lines=['Week Start,Week End,Emp_ID,Employee,Date,Day,ShiftCode'];
    weeks.forEach(([ws,wdates])=>{
      const we=wdates[wdates.length-1]||ws;
      emps.forEach((e)=>{
        wdates.forEach((dt)=>{
          const code=state.rosterMap[`${e.id}|${dt}`]||'';
          lines.push([ws,we,e.id,e.name,dt,dayShort(dt),code].map(csvCell).join(','));
        });
      });
    });
    exportCsv(lines,`shift-roster-weekly-${from}-to-${to}.csv`);
  }
  function openWeeklyExportModal(){
    const dates=(state.monthDates||[]).slice().sort((a,b)=>a.localeCompare(b));
    if(!dates.length){ showMsg('No roster data to export.', false); return; }
    const fromEl=$("weeklyExportFrom");
    const toEl=$("weeklyExportTo");
    if(fromEl && !fromEl.value) fromEl.value=dates[0];
    if(toEl && !toEl.value) toEl.value=dates[dates.length-1];
    bootstrap.Modal.getOrCreateInstance($("weeklyExportModal")).show();
  }
  async function applyMultiShift(){
    if(!state.selectedCells.size){ showMsg('Select cells first in Multi Select mode.', false); return; }
    const shiftCode=String($("multiShiftCode")?.value||"").trim();
    if(!shiftCode){ showMsg('Select shift to apply.', false); return; }
    const note=String($("multiShiftNote")?.value||"").trim();
    const half=String($("multiShiftHalfDay")?.value||"No");
    if(requiresNote(shiftCode) && !note){ showMsg('Note is required for CL / SL / EL / LOP', false); return; }
    const rows=[...state.selectedCells].map(k=>{
      const [empId,rosterDate]=k.split('|');
      state.rosterMap[k]=shiftCode;
      state.rosterNotes[k]=note;
      state.rosterHalfDay[k]=half;
      return {empId,rosterDate,shiftCode,status:'Draft',notes:composeNote(note,half)};
    });
    await api('/rosters/bulk-upsert',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId(),rows})});
    clearSelectedCells();
    await loadRoster();
    showMsg('Shift applied to selected cells', true);
  }
  async function clearMultiShift(){
    if(!state.selectedCells.size){ showMsg('Select cells first in Multi Select mode.', false); return; }
    const rows=[...state.selectedCells].map(k=>{
      const [empId,rosterDate]=k.split('|');
      delete state.rosterMap[k];
      delete state.rosterNotes[k];
      delete state.rosterHalfDay[k];
      return {empId,rosterDate};
    });
    await api('/rosters/bulk-delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId(),rows})});
    clearSelectedCells(true);
    await loadRoster();
    showMsg('Shift cleared from selected cells', true);
  }
  async function saveRoster(publish){
    const rows=Object.keys(state.rosterMap).map(k=>{const [empId,rosterDate]=k.split('|'); return {empId,rosterDate,shiftCode:state.rosterMap[k],status:publish?'Published':'Draft',notes:composeNote(state.rosterNotes[k]||"", state.rosterHalfDay[k]||"No")};}).filter(r=>r.shiftCode);
    await api('/rosters/bulk-upsert',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId(),rows})});
    const ws=state.monthDates[0], we=state.monthDates[state.monthDates.length-1];
    await api('/rosters/week-status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId(),weekStartDate:ws,weekEndDate:we,isLocked:state.weekLocked,publishStatus:publish?'Published':'Draft'})});
    await loadRoster(); showMsg(publish?'Roster published':'Roster draft saved',true);
  }
  async function autoFill(){const ws=state.monthDates[0], we=state.monthDates[state.monthDates.length-1]; const empRaw=$("rosterEmpSearch")?.value||""; const empId=String(empRaw).split(" - ")[0].trim()||String(empRaw).trim(); await api('/rosters/auto-fill-week',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId(),weekStartDate:ws,weekEndDate:we,department:$("rosterDepartment")?.value||"",designation:$("rosterDesignation")?.value||"",empId:empId})}); await loadRoster();}
  async function toggleLock(){const ws=state.monthDates[0], we=state.monthDates[state.monthDates.length-1]; state.weekLocked=!state.weekLocked; await api('/rosters/week-status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId(),weekStartDate:ws,weekEndDate:we,isLocked:state.weekLocked,publishStatus:'Draft'})}); await loadRoster();}

  async function loadCalendar(){
    const range=state.calendar.view.currentStart.toISOString().slice(0,10);
    const rangeEnd=new Date(state.calendar.view.currentEnd.getTime()-86400000).toISOString().slice(0,10);
    const d=await api(`/shift-calendar/events?from=${range}&to=${rangeEnd}&all=${isSuper?1:0}${companyQuery()}&department=${encodeURIComponent($("calDepartment").value||'')}&empId=${encodeURIComponent($("calEmployee").value||'')}&shiftCode=${encodeURIComponent($("calShift").value||'')}`);
    state.calendar.removeAllEvents(); (d.events||[]).forEach(e=>state.calendar.addEvent({id:e.id,title:e.title,start:e.start,end:e.end,allDay:e.allDay,color:e.colorCode,extendedProps:e}));
    $("calendarSummary").innerHTML=(d.daySummary||[]).slice(0,12).map(s=>`<div class="small mb-1"><b>${esc(s.date)}</b> <span class="badge bg-primary summary-badge">${s.totalScheduled}</span><span class="badge bg-warning text-dark summary-badge">L ${s.leaveCount}</span><span class="badge bg-secondary summary-badge">O ${s.offCount}</span><span class="badge bg-dark summary-badge">N ${s.nightShiftCount}</span></div>`).join('');
  }
  function initCalendar(){
    state.calendar=new FullCalendar.Calendar($("shiftCalendar"),{initialView:'dayGridMonth',height:650,headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,timeGridWeek,timeGridDay'},eventClick:(info)=>{const e=info.event.extendedProps; alert(`${e.employeeName}\n${e.shiftCode} - ${e.shiftName}\n${e.rosterDate}`);},datesSet:()=>loadCalendar()});
    state.calendar.render();
    setInterval(()=>{if(state.calendar) loadCalendar().catch(()=>{});},30000);
  }

  async function loadReport(){
    const from=$("reportFrom").value||today().slice(0,8)+'01';
    const to=$("reportTo").value||today();
    const d=await api(`/roster-attendance-report?from=${from}&to=${to}&all=${isSuper?1:0}${companyQuery()}`);
    const rows=Array.isArray(d.rows)?d.rows:[];
    state.reportRows=rows;
    $("reportBody").innerHTML=rows.map(r=>`<tr><td>${esc(r.date)}</td><td>${esc(r.company)}</td><td>${esc(r.empId)}</td><td>${esc(r.employeeName)}</td><td>${esc(r.shiftCode)}</td><td>${esc(r.scheduledIn||'-')}</td><td>${esc(r.scheduledOut||'-')}</td><td>${esc(r.status)}</td><td>${r.shiftMismatch?'<span class="badge text-bg-danger">Yes</span>':'No'}</td></tr>`).join('')||'<tr><td colspan="9" class="text-center text-muted">No rows</td></tr>';
  }
  async function deleteAllReportRows(){
    const rows=(state.reportRows||[]).map((r)=>({empId:r.empId,rosterDate:r.date})).filter((r)=>r.empId && r.rosterDate);
    if(!rows.length){ showMsg('No attendance report rows to delete.', false); return; }
    if(!confirm(`Delete all ${rows.length} attendance report rows?`)) return;
    await api('/rosters/bulk-delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId(),rows})});
    state.reportRows=[];
    await loadReport();
    await loadRoster();
    showMsg('All attendance report rows deleted', true);
  }
  function clearReport(){
    if($("reportFrom")) $("reportFrom").value=today().slice(0,8)+'01';
    if($("reportTo")) $("reportTo").value=today();
    state.reportRows=[];
    if($("reportBody")) $("reportBody").innerHTML='<tr><td colspan="9" class="text-center text-muted">No rows</td></tr>';
  }

  async function loadDashboardWidget(){
    if(!$("shiftWidgetCards")) return;
    const d=await api('/shift/dashboard?all=1'+companyQuery());
    const t=d.totals||{};
    const cards=[['Companies Using Shift',t.companiesUsingModule],['Active Shifts',t.activeShifts],['Employees Assigned',t.employeesAssignedInRoster],['Today Shifts',t.todayShifts],['Weekly Off Today',t.weeklyOffToday],['Leave Today',t.leaveToday],['Night Shift Today',t.nightShiftToday],['Missing Roster Alerts',t.upcomingConflictsOrMissingRosters],['Without Default Shift',t.employeesWithoutDefaultShift]];
    $("shiftWidgetCards").innerHTML=cards.map(c=>`<div class="col-12 col-md-6 col-xl-4"><div class="glass p-3"><div class="small text-muted-3">${esc(c[0])}</div><div class="fs-4 fw-semibold">${Number(c[1]||0)}</div></div></div>`).join('');
  }

  async function init(){
    const now=new Date();
    if($("rosterMonth")) $("rosterMonth").innerHTML=Array.from({length:12},(_,i)=>`<option value="${i+1}" ${i===now.getMonth()?'selected':''}>${new Date(2000,i,1).toLocaleString(undefined,{month:'short'})}</option>`).join('');
    if($("rosterYear")) $("rosterYear").innerHTML=Array.from({length:7},(_,i)=>{const y=now.getFullYear()-2+i; return `<option value="${y}" ${y===now.getFullYear()?'selected':''}>${y}</option>`;}).join('');
    syncRosterHeaderBadges();
    if($("assignEffective")) $("assignEffective").value=today();
    if($("reportFrom")) $("reportFrom").value=today().slice(0,8)+'01';
    if($("reportTo")) $("reportTo").value=today();
    updateSelectedCount();
    try{await loadClients(); await loadEmployees(); await loadShifts(); await loadAssignments(); await loadRoster(); await loadReport(); if($("shiftCalendar")) initCalendar(); await loadDashboardWidget();}catch(e){showMsg(e.message||'Failed to load data');}

    $("companyFilter")?.addEventListener('change', async()=>{await loadEmployees(); await loadShifts(); await loadAssignments(); await loadRoster(); await loadReport(); if(state.calendar) await loadCalendar(); await loadDashboardWidget();});
    $("rosterMonth")?.addEventListener('change',()=>loadRoster().catch(e=>showMsg(e.message)));
    $("rosterYear")?.addEventListener('change',()=>loadRoster().catch(e=>showMsg(e.message)));
    let rosterSearchTimer = null;
    $("rosterEmpSearch")?.addEventListener('input',()=>{
      if(rosterSearchTimer) clearTimeout(rosterSearchTimer);
      rosterSearchTimer=setTimeout(()=>loadRoster().catch(e=>showMsg(e.message)),200);
    });
    $("btnSaveShift")?.addEventListener('click',()=>saveShift().catch(e=>showMsg(e.message)));
    $("btnSaveAssignment")?.addEventListener('click',()=>saveAssignment().catch(e=>showMsg(e.message)));
    $("btnRosterRefresh")?.addEventListener('click',()=>loadRoster().catch(e=>showMsg(e.message)));
    $("btnRosterUpdate")?.addEventListener('click',()=>saveRoster(false).catch(e=>showMsg(e.message)));
    $("btnRosterAutofill")?.addEventListener('click',()=>autoFill().catch(e=>showMsg(e.message)));
    $("btnRosterDraft")?.addEventListener('click',()=>saveRoster(false).catch(e=>showMsg(e.message)));
    $("btnRosterPublish")?.addEventListener('click',()=>saveRoster(true).catch(e=>showMsg(e.message)));
    $("btnRosterLock")?.addEventListener('click',()=>toggleLock().catch(e=>showMsg(e.message)));
    $("btnRosterExport")?.addEventListener('click',()=>{
      const lines=['Emp_ID,Date,ShiftCode'];
      Object.keys(state.rosterMap).sort().forEach(k=>{const [empId,date]=k.split('|'); const code=state.rosterMap[k]||''; if(code) lines.push(`${empId},${date},${code}`);});
      exportCsv(lines,'shift-roster.csv');
    });
    $("btnRosterExportWeekly")?.addEventListener('click',()=>openWeeklyExportModal());
    $("btnGenerateWeeklyExport")?.addEventListener('click',()=>{
      const from=$("weeklyExportFrom")?.value||"";
      const to=$("weeklyExportTo")?.value||"";
      exportWeeklyRoster(from,to);
      if(from && to && from<=to) bootstrap.Modal.getOrCreateInstance($("weeklyExportModal")).hide();
    });
    $("btnApplyMultiShift")?.addEventListener('click',()=>applyMultiShift().catch(e=>showMsg(e.message)));
    $("btnClearMultiShift")?.addEventListener('click',()=>clearMultiShift().catch(e=>showMsg(e.message)));
    $("btnClearMultiSelection")?.addEventListener('click',()=>{ clearSelectedCells(true); });
    $("multiSelectMode")?.addEventListener('change',()=>{ if(!multiMode()){ clearSelectedCells(true); } });
    $("btnCalRefresh")?.addEventListener('click',()=>loadCalendar().catch(e=>showMsg(e.message)));
    $("btnReportLoad")?.addEventListener('click',()=>loadReport().catch(e=>showMsg(e.message)));
    $("btnReportCsv")?.addEventListener('click',()=>{const from=$("reportFrom").value; const to=$("reportTo").value; window.open((isSuper?'/api':'/api')+`/roster-attendance-report?from=${from}&to=${to}&all=${isSuper?1:0}${companyQuery()}&format=csv`,'_blank');});
    $("btnReportClear")?.addEventListener('click',()=>clearReport());
    $("btnReportDeleteAll")?.addEventListener('click',()=>deleteAllReportRows().catch(e=>showMsg(e.message)));
    $("btnCellApply")?.addEventListener('click', async ()=>{
      if(!state.currentCell) return;
      const key=state.currentCell.empId+'|'+state.currentCell.date;
      const val=state.selectedCellShift||"";
      const note=$("cellNote").value||"";
      const half=$("cellHalfDay").value||"No";
      if(val && requiresNote(val) && !String(note).trim()){
        showMsg('Note is required for CL / SL / EL / LOP', false);
        return;
      }
      if(val){ state.rosterMap[key]=val; state.rosterNotes[key]=note; state.rosterHalfDay[key]=half; }
      else { delete state.rosterMap[key]; delete state.rosterNotes[key]; delete state.rosterHalfDay[key]; }

      try{
        const row = {
          empId: state.currentCell.empId,
          rosterDate: state.currentCell.date,
          shiftCode: val,
          status: "Draft",
          notes: composeNote(note, half)
        };
        if(val){
          await api('/rosters/bulk-upsert',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({companyId:getCompanyId(),rows:[row]})
          });
          showMsg('Cell saved', true);
        } else {
          await api('/rosters/delete-cell',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({companyId:getCompanyId(),empId:state.currentCell.empId,rosterDate:state.currentCell.date})
          });
          showMsg('Shift removed', true);
        }
        bootstrap.Modal.getOrCreateInstance($("rosterCellModal")).hide();
        await loadRoster();
      } catch(e){
        showMsg(e.message || 'Failed to save cell', false);
      }
    });
    $("btnCellClear")?.addEventListener('click',()=>{ state.selectedCellShift=""; document.querySelectorAll('#cellShiftButtons .status-btn').forEach(b=>b.classList.remove('active')); $("cellNote").value=""; $("cellHalfDay").value="No"; });

    document.addEventListener('click', async(ev)=>{
      const editShift=ev.target.closest('[data-edit-shift]');
      if(editShift){const id=Number(editShift.dataset.editShift); const r=state.shifts.find(x=>Number(x.id)===id); if(!r)return; $("shiftId").value=r.id; $("shiftCode").value=r.shiftCode; $("shiftName").value=r.shiftName; $("shiftStart").value=r.startTime||''; $("shiftEnd").value=r.endTime||''; $("shiftBreak").value=r.breakMinutes; $("shiftHours").value=r.totalHours; $("shiftType").value=r.shiftType; $("shiftGrace").value=r.lateGraceMinutes; $("shiftHalfDay").value=r.halfDayHours; $("shiftOT").checked=!!r.otEligible; $("shiftColor").value=r.colorCode||'#0d6efd'; $("shiftStatus").value=r.status; bootstrap.Modal.getOrCreateInstance($("shiftModal")).show();}
      const delShift=ev.target.closest('[data-del-shift]');
      if(delShift && confirm('Delete shift?')){await api(`/shifts/${Number(delShift.dataset.delShift)}`,{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId()})}); await loadShifts();}
      const editAssn=ev.target.closest('[data-edit-assn]');
      if(editAssn){const r=state.assignments.find(x=>Number(x.id)===Number(editAssn.dataset.editAssn)); if(!r)return; $("assignId").value=r.id; $("assignEmp").value=r.empId; $("assignDefaultShift").value=r.defaultShiftCode; $("assignWeeklyOff").value=r.weeklyOffDay; $("assignEffective").value=r.effectiveFrom; $("assignStatus").value=r.status;}
      const delAssn=ev.target.closest('[data-del-assn]');
      if(delAssn && confirm('Delete assignment?')){await api(`/shift-assignments/${Number(delAssn.dataset.delAssn)}`,{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({companyId:getCompanyId()})}); await loadAssignments();}
      const rc=ev.target.closest('[data-roster-cell]');
      if(rc){
        if(multiMode()){
          const key=rc.dataset.emp+'|'+rc.dataset.date;
          if(state.selectedCells.has(key)) state.selectedCells.delete(key);
          else state.selectedCells.add(key);
          rc.classList.toggle('multi-selected', state.selectedCells.has(key));
          updateSelectedCount();
          return;
        }
        state.currentCell={empId:rc.dataset.emp,date:rc.dataset.date};
        const emp=state.employees.find(e=>String(e.id)===String(rc.dataset.emp));
        $("cellEmpLabel").textContent=`Employee: ${(emp?.name||rc.dataset.emp)} (${rc.dataset.emp})`;
        $("cellDateLabel").textContent=`Date: ${rc.dataset.date} (${dayShort(rc.dataset.date)})`;
        const key=rc.dataset.emp+'|'+rc.dataset.date;
        const current=state.rosterMap[key]||'';
        state.selectedCellShift=current;
        const btns=state.shifts.filter(s=>s.status==='Active').map(s=>`<button type="button" class="status-btn ${current===s.shiftCode?'active':''}" data-cell-shift="${esc(s.shiftCode)}" style="border-color:${esc(s.colorCode)};color:${esc(s.colorCode)}">${esc(s.shiftCode)} - ${esc(s.shiftName)}</button>`).join('');
        $("cellShiftButtons").innerHTML=btns;
        $("cellNote").value=state.rosterNotes[key]||"";
        $("cellHalfDay").value=state.rosterHalfDay[key]||"No";
        bootstrap.Modal.getOrCreateInstance($("rosterCellModal")).show();
      }
      const cs=ev.target.closest('[data-cell-shift]');
      if(cs){
        state.selectedCellShift=cs.dataset.cellShift||"";
        document.querySelectorAll('#cellShiftButtons .status-btn').forEach(b=>b.classList.remove('active'));
        cs.classList.add('active');
      }
    });
  }
  document.addEventListener('DOMContentLoaded',init);
})();
