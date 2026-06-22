const $ = (id) => document.getElementById(id);
  const $$ = $;
  $$('yr2').textContent = new Date().getFullYear();

  const API_CONTROL = '/api/control';
  const API_PF_GEN = '/api/pf-sheet/generate';
  const API_PF_LIST = '/api/pf-sheet/sheets';
  const API_PF_CLEAR = '/api/pf-sheet/clear';
  const API_EMP = '/api/employees';
  const KEY_CONTROL = 'hr_client_control_v1';
  const KEY_PF_HISTORY = 'hr_client_pf_sheets_v1';

  function round2(n){ return Math.round(Number(n||0)); }
  function roundNearest(n){ return Math.round(Number(n || 0)); }
  function money(n){ return round2(n).toLocaleString('en-IN',{maximumFractionDigits:0}); }
  function monthKey(y,m){ return `${y}-${String(m).padStart(2,'0')}`; }
  function monthLabel(y,m){ return new Date(y, m-1, 1).toLocaleDateString(undefined,{month:'short',year:'numeric'}); }
  function safeParse(s){ try { return JSON.parse(s); } catch(_e){ return null; } }

  const htmlEl = document.documentElement;
  function applyTheme(theme){
    htmlEl.setAttribute('data-bs-theme', theme);
    localStorage.setItem('hr_portal_theme', theme);
    const isDark = theme === 'dark';
    $$('themeIcon').className = isDark ? 'bi bi-sun' : 'bi bi-moon';
    if ($('themeText')) $('themeText').textContent = '';
  }
  applyTheme(localStorage.getItem('hr_portal_theme') || 'light');
  $$('themeToggle').addEventListener('click', () => {
    applyTheme((htmlEl.getAttribute('data-bs-theme') || 'light') === 'dark' ? 'light' : 'dark');
  });

  function setStorageMode(t){
    const el = $$('storageMode');
    if(el) el.textContent = t;
  }

  async function fetchControl(){
    try {
      const r = await fetch(API_CONTROL, { cache:'no-store' });
      if(!r.ok) throw new Error('control failed');
      const data = await r.json();
      localStorage.setItem(KEY_CONTROL, JSON.stringify(data));
      return data;
    } catch (_e) {
      const local = safeParse(localStorage.getItem(KEY_CONTROL));
      if(local && typeof local === 'object') return local;
      throw new Error('control failed');
    }
  }
  async function generatePfApi(y,m){
    const r = await fetch(API_PF_GEN, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ month:m, year:y })
    });
    if(!r.ok) throw new Error('generate failed');
    return (await r.json()).sheet;
  }
  async function listPfApi(){
    const r = await fetch(API_PF_LIST, { cache:'no-store' });
    if(!r.ok) throw new Error('list failed');
    return (await r.json()).rows || [];
  }
  async function getPfApi(id){
    const r = await fetch(`${API_PF_LIST}/${id}`, { cache:'no-store' });
    if(!r.ok) throw new Error('get failed');
    return (await r.json()).sheet;
  }
  async function delPfApi(id){
    const r = await fetch(`${API_PF_LIST}/${id}`, { method:'DELETE' });
    if(!r.ok) throw new Error('delete failed');
  }
  async function clearPfApi(){
    const r = await fetch(API_PF_CLEAR, { method:'POST' });
    if(!r.ok) throw new Error('clear failed');
  }

  function loadLocal(){ try { return JSON.parse(localStorage.getItem(KEY_PF_HISTORY) || '[]'); } catch(_e){ return []; } }
  function saveLocal(rows){ localStorage.setItem(KEY_PF_HISTORY, JSON.stringify(rows)); }

  let previewRows = [];
  let previewFileName = '';
  let sheetList = [];
  let empUanMap = new Map();

  async function loadEmpUan(){
    try {
      const r = await fetch(`${API_EMP}?activeOnly=1`, { cache:'no-store' });
      if(!r.ok) throw new Error('emp load failed');
      const data = await r.json();
      const rows = Array.isArray(data.rows) ? data.rows : [];
      empUanMap = new Map(rows.map((e) => [String(e.id || '').toUpperCase(), String(e.uan || '').trim()]));
    } catch(_e) {
      empUanMap = new Map();
    }
  }

  function renderRules(c){
    const hasPfEmp = c && c.pfEmpPct !== undefined && c.pfEmpPct !== null && c.pfEmpPct !== "";
    const hasPfEr = c && c.pfErPct !== undefined && c.pfErPct !== null && c.pfErPct !== "";
    const hasCapEnabled = c && c.pfWageCapEnabled !== undefined && c.pfWageCapEnabled !== null && c.pfWageCapEnabled !== "";
    const hasCapAmt = c && c.pfWageCapAmount !== undefined && c.pfWageCapAmount !== null && c.pfWageCapAmount !== "";
    const hasPfOnEsi = c && c.pfOnEsiPct !== undefined && c.pfOnEsiPct !== null && c.pfOnEsiPct !== "";
    $$('pfPctEE').textContent = hasPfEmp ? String(Number(c.pfEmpPct)) : "-";
    $$('pfPctER').textContent = hasPfEr ? String(Number(c.pfErPct)) : "-";
    if(hasCapEnabled){
      const enabled = String(c.pfWageCapEnabled).toLowerCase();
      $$('pfCapEnabled').textContent = ['yes','true','1'].includes(enabled) ? 'Yes' : 'No';
    } else {
      $$('pfCapEnabled').textContent = "-";
    }
    $$('pfCapAmt').textContent = hasCapAmt ? money(c.pfWageCapAmount) : "-";
    if($$('pfOnEsiPct')) $$('pfOnEsiPct').textContent = hasPfOnEsi ? String(Number(c.pfOnEsiPct)) : "-";
  }

  function renderPreview(rows, monthTxt){
    const num = (v) => Number(v || 0);
    const mapRow = (r) => {
      const empId = String(r.Emp_ID || r.empId || "").toUpperCase();
      const memberName = String(r["MEMBER_ NAME"] || r.MEMBER_NAME || r.Employee_Name || r.employeeName || "");
      const uan = String(r.UAN || r.uan || r.Uan || empUanMap.get(empId) || "").trim();
      const grossWages = roundNearest(r.GROSS_WAGES ?? r.earnedGross ?? r["Earned Wages"] ?? r["Earned Wa"] ?? (num(r.Basic) + num(r.DA)));
      const epfWages = roundNearest(r.EPF_WAGES ?? r.PF_Wages ?? r.PF_WAGES ?? 0);
      const epsWages = roundNearest(r.EPS_WAGES ?? epfWages);
      const edliWages = roundNearest(r.EDLI_WAGES ?? epfWages);
      const epfContri = roundNearest(r.EPF_CONTRI_REMITTED ?? r.PF_EE ?? 0);
      const epsContri = roundNearest(epfWages * 0.0833);
      const diff = roundNearest(r.EPF_EPS_DIFF_REMITTED ?? (epfContri - epsContri));
      const ncpDays = roundNearest(r.NCP_DAYS ?? r.lopDays ?? r.LOP_Days ?? 0);
      const refund = roundNearest(r.REFUND_OF_ADVANCES ?? 0);
      return {
        Month: r.Month || monthTxt || "",
        Emp_ID: empId,
        "MEMBER_ NAME": memberName,
        UAN: uan,
        GROSS_WAGES: grossWages,
        EPF_WAGES: epfWages,
        EPS_WAGES: epsWages,
        EDLI_WAGES: edliWages,
        EPF_CONTRI_REMITTED: epfContri,
        EPS_CONTRI_REMITTED: epsContri,
        EPF_EPS_DIFF_REMITTED: diff,
        NCP_DAYS: ncpDays,
        REFUND_OF_ADVANCES: refund
      };
    };
    previewRows = (rows || []).map(mapRow);
    $$('badgeMonth').textContent = 'Month: ' + (monthTxt || '-');
    $$('previewCount').textContent = String(previewRows.length);
    $$('previewNote').textContent = previewRows.length ? 'Preview ready. It is also saved in list below.' : 'Click Generate to preview.';
    $$('previewBody').innerHTML = previewRows.map((r, idx) => `
      <tr>
        <td class='fw-semibold'>${idx + 1}</td>
        <td>${r.Month}</td>
        <td class='fw-semibold mono'>${r.Emp_ID || r.empId || '-'}</td>
        <td>${r["MEMBER_ NAME"]}</td>
        <td class='mono'>${r.UAN || "-"}</td>
        <td class='text-end'>${money(r.GROSS_WAGES)}</td>
        <td class='text-end'>${money(r.EPF_WAGES)}</td>
        <td class='text-end'>${money(r.EPS_WAGES)}</td>
        <td class='text-end'>${money(r.EDLI_WAGES)}</td>
        <td class='text-end'>${money(r.EPF_CONTRI_REMITTED)}</td>
        <td class='text-end'>${money(r.EPS_CONTRI_REMITTED)}</td>
        <td class='text-end'>${money(r.EPF_EPS_DIFF_REMITTED)}</td>
        <td class='text-end'>${money(r.NCP_DAYS)}</td>
        <td class='text-end'>${money(r.REFUND_OF_ADVANCES)}</td>
      </tr>
    `).join('');
    $$('btnDownloadPreview').disabled = !previewRows.length;
  }

  function renderList(){
    $$('listBody').innerHTML = sheetList.map((x,i)=>`
      <tr>
        <td class='fw-semibold text-center'>${i+1}</td>
        <td class='fw-semibold text-center'>${x.period || monthKey(x.year,x.month)}</td>
        <td class='text-muted-3 text-center'>${new Date(x.generatedAt).toLocaleString()}</td>
        <td class='text-center'>${x.rowCount || 0}</td>
        <td class='text-center'>Rs ${money(x.totalWage || 0)}</td>
        <td class='text-center'>Rs ${money(x.totalEE || 0)}</td>
        <td class='text-center'>Rs ${money(x.totalER || 0)}</td>
        <td class='text-center'>
          <div class='btn-group' role='group' aria-label='PF sheet actions'>
            <button class='btn btn-outline-primary btn-sm' type='button' title='View' aria-label='View' onclick="viewPf('${x.id}')"><i class='bi bi-eye'></i></button>
            <button class='btn btn-outline-secondary btn-sm' type='button' title='Download' aria-label='Download' onclick="downloadPf('${x.id}')"><i class='bi bi-file-earmark-excel'></i></button>
            <button class='btn btn-outline-danger btn-sm' type='button' title='Delete' aria-label='Delete' onclick="deletePf('${x.id}')"><i class='bi bi-trash'></i></button>
          </div>
        </td>
      </tr>
    `).join('');
    $$('listCount').textContent = `${sheetList.length} saved`;
  }

  async function renderLatestPreviewFromList(){
    if(!sheetList.length){
      renderPreview([], '');
      previewFileName = '';
      return;
    }
    try {
      const latest = sheetList[0];
      const s = await getPfApi(latest.id);
      renderPreview(s.rows || [], s.period || monthLabel(s.year, s.month));
      previewFileName = `pf_sheet_${s.period || monthKey(s.year,s.month)}.xlsx`;
    } catch(_e){
      // Keep existing preview if fetch fails (do not blank unexpectedly).
    }
  }

  function downloadRowsXlsx(rows, filename){
    const num = (v) => Number(v || 0);
    const outRows = (rows || []).map((r) => {
      const pfWages = roundNearest(num(r.EPF_WAGES ?? r.PF_Wages ?? r.PF_WAGES ?? 0));
      const epfContri = roundNearest(num(r.EPF_CONTRI_REMITTED ?? r.PF_EE ?? 0));
      const epsContri = roundNearest(pfWages * 0.0833);
      const empId = String(r.Emp_ID || r.empId || '').toUpperCase();
      const uan = String(r.UAN || r.uan || r.Uan || empUanMap.get(empId) || '').trim();
      return {
        "Sr. No": "",
        Month: r.Month || "",
        Emp_ID: empId,
        "MEMBER_ NAME": r["MEMBER_ NAME"] || r.Employee_Name || r.MEMBER_NAME || "",
        UAN: uan,
        GROSS_WAGES: roundNearest(num(r.GROSS_WAGES ?? r.earnedGross ?? r["Earned Wages"] ?? r["Earned Wa"] ?? (num(r.Basic) + num(r.DA)))),
        EPF_WAGES: pfWages,
        EPS_WAGES: roundNearest(num(r.EPS_WAGES ?? pfWages)),
        EDLI_WAGES: roundNearest(num(r.EDLI_WAGES ?? pfWages)),
        EPF_CONTRI_REMITTED: epfContri,
        EPS_CONTRI_REMITTED: epsContri,
        EPF_EPS_DIFF_REMITTED: roundNearest(num(r.EPF_EPS_DIFF_REMITTED ?? (epfContri - epsContri))),
        NCP_DAYS: roundNearest(num(r.NCP_DAYS ?? r.lopDays ?? r.LOP_Days ?? 0)),
        REFUND_OF_ADVANCES: roundNearest(num(r.REFUND_OF_ADVANCES || 0))
      };
    }).map((r, idx) => ({ ...r, "Sr. No": idx + 1 }));

    const ws = XLSX.utils.json_to_sheet(outRows, {
      header: [
        "Sr. No",
        "Month",
        "Employee ID",
        "MEMBER_ NAME",
        "UAN",
        "GROSS_WAGES",
        "EPF_WAGES",
        "EPS_WAGES",
        "EDLI_WAGES",
        "EPF_CONTRI_REMITTED",
        "EPS_CONTRI_REMITTED",
        "EPF_EPS_DIFF_REMITTED",
        "NCP_DAYS",
        "REFUND_OF_ADVANCES"
      ]
    });

    const colCount = 14;
    const rowStart = 1;
    const rowEnd = rowStart + outRows.length;
    const mkCol = (n) => { let s = ""; while(n > 0){ const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; };
    if(colCount > 0 && outRows.length){
      ws["!autofilter"] = { ref: `A${rowStart}:${mkCol(colCount)}${rowEnd}` };
      ws["!cols"] = Array.from({ length: colCount }, () => ({ wch: 15 }));
      for(let r = rowStart; r <= rowEnd; r++){
        for(let c = 1; c <= colCount; c++){
          const ref = `${mkCol(c)}${r}`;
          if(!ws[ref]) ws[ref] = { t: "s", v: "" };
          ws[ref].s = {
            border: { top:{style:"thin", color:{rgb:"FF000000"}}, bottom:{style:"thin", color:{rgb:"FF000000"}}, left:{style:"thin", color:{rgb:"FF000000"}}, right:{style:"thin", color:{rgb:"FF000000"}} },
            fill: r === rowStart ? { patternType: "solid", fgColor: { rgb: "FF0B1F3A" } } : undefined,
            font: r === rowStart ? { bold: true, color: { rgb: "FFFFFFFF" } } : undefined
          };
        }
      }
    }
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'PF_Sheet');
    XLSX.writeFileXLSX(wb, filename, { cellStyles: true, bookType: "xlsx" });
  }

  window.viewPf = async (id) => {
    try {
      const s = await getPfApi(id);
      renderPreview(s.rows || [], s.period || monthLabel(s.year, s.month));
      previewFileName = `pf_sheet_${s.period || monthKey(s.year,s.month)}.xlsx`;
      window.scrollTo({top:0, behavior:'smooth'});
    } catch(_e){ alert('Not found'); }
  };

  window.downloadPf = async (id) => {
    try {
      const s = await getPfApi(id);
      downloadRowsXlsx(s.rows || [], `pf_sheet_${s.period || monthKey(s.year,s.month)}.xlsx`);
    } catch(_e){ alert('Unable to download'); }
  };

  window.deletePf = async (id) => {
    if(!confirm('Delete this PF sheet record?')) return;
    try {
      await delPfApi(id);
      sheetList = await listPfApi();
      setStorageMode('API (SQLite)');
      saveLocal(sheetList);
    } catch(_e){
      sheetList = loadLocal().filter(x => x.id !== id);
      saveLocal(sheetList);
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
    await renderLatestPreviewFromList();
  };

  async function generatePf(){
    const y = Number($$('yearSel').value);
    const m = Number($$('monthSel').value);
    const doneProcessing = window.HRCommon?.setProcessingState?.($$('btnGenerate'), {
      busyText: 'Generating...',
      message: 'Please wait, we are preparing the PF sheet.'
    });
    try {
      const s = await generatePfApi(y,m);
      renderPreview(s.rows || [], s.period || monthLabel(y,m));
      previewFileName = `pf_sheet_${s.period || monthKey(y,m)}.xlsx`;
      sheetList = await listPfApi();
      saveLocal(sheetList);
      setStorageMode('API (SQLite)');
      doneProcessing?.(`PF sheet ready for ${monthKey(y,m)}.`, false);
      alert('PF Sheet generated and saved.');
    } catch(_e){
      setStorageMode('Browser localStorage (offline mode)');
      doneProcessing?.(_e?.message || 'PF generation failed.', true);
      alert('PF generate failed. Start backend and try again.');
    }
    renderList();
  }

  $$('btnGenerate').addEventListener('click', generatePf);
  $$('btnRefreshList').addEventListener('click', async () => {
    try { sheetList = await listPfApi(); saveLocal(sheetList); setStorageMode('API (SQLite)'); }
    catch(_e){ sheetList = loadLocal(); setStorageMode('Browser localStorage (offline mode)'); }
    renderList();
    await renderLatestPreviewFromList();
  });

  $$('btnDownloadPreview').addEventListener('click', () => {
    if(!previewRows.length) return;
    downloadRowsXlsx(previewRows, previewFileName || 'pf_sheet_preview.xlsx');
  });

  $$('btnLoadDummy')?.addEventListener('click', async () => {
    const y = Number($$('yearSel').value);
    const m = Number($$('monthSel').value);
    try {
      await fetch('/api/payroll/generate', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ month:m, year:y, absentMode:'LOP' })
      });
      alert('Demo payroll basis prepared. Now click Generate PF Sheet.');
    } catch(_e){
      alert('Unable to prepare data basis. Start backend and generate payroll once.');
    }
  });

  $$('btnClearAll').addEventListener('click', async () => {
    if(!confirm('Clear PF generated history?')) return;
    try {
      await clearPfApi();
      sheetList = [];
      setStorageMode('API (SQLite)');
    } catch(_e){
      localStorage.removeItem(KEY_PF_HISTORY);
      sheetList = [];
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
    renderPreview([], '');
  });
  (async function init(){
    const now = new Date();
    $$('monthSel').value = String(now.getMonth()+1);
    $$('yearSel').value = String(now.getFullYear());
    await loadEmpUan();

    try {
      renderRules(await fetchControl());
    } catch(_e) {
      renderRules({});
    }

    try {
      sheetList = await listPfApi();
      saveLocal(sheetList);
      setStorageMode('API (SQLite)');
    } catch(_e){
      sheetList = loadLocal();
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
    await renderLatestPreviewFromList();
  })();

