const $ = (id) => document.getElementById(id);
  $('yr').textContent = new Date().getFullYear();

  const API_ESIC_GEN = '/api/esic-sheet/generate';
  const API_ESIC_LIST = '/api/esic-sheet/sheets';
  const API_ESIC_CLEAR = '/api/esic-sheet/clear';
  const API_CONTROL = '/api/control';
  const KEY_CONTROL = 'hr_client_control_v1';

  const KEY_ESIC_SHEETS = 'hr_client_esic_sheets_v1';

  function round2(n){ return Math.round(Number(n||0)); }
  function money(n){ return round2(n).toLocaleString('en-IN',{maximumFractionDigits:0}); }
  function monthKey(y,m){ return `${y}-${String(m).padStart(2,'0')}`; }
  function monthLabel(y,m){ return new Date(y, m-1, 1).toLocaleDateString(undefined,{month:'short',year:'numeric'}); }
  function safeParse(s){ try { return JSON.parse(s); } catch(_e){ return null; } }

  const htmlEl = document.documentElement;
  function applyTheme(theme){
    htmlEl.setAttribute('data-bs-theme', theme);
    localStorage.setItem('hr_portal_theme', theme);
    const isDark = theme === 'dark';
    $('themeIcon').className = isDark ? 'bi bi-sun' : 'bi bi-moon';
    if ($('themeText')) $('themeText').textContent = '';
  }
  applyTheme(localStorage.getItem('hr_portal_theme') || 'light');
  $('themeToggle').addEventListener('click', () => {
    const cur = htmlEl.getAttribute('data-bs-theme') || 'light';
    applyTheme(cur === 'dark' ? 'light' : 'dark');
  });

  function setStorageMode(t){
    const el = $('storageMode');
    if(el) el.textContent = t;
  }

  function loadLocal(){ try { return JSON.parse(localStorage.getItem(KEY_ESIC_SHEETS) || '[]'); } catch(_e){ return []; } }
  function saveLocal(rows){ localStorage.setItem(KEY_ESIC_SHEETS, JSON.stringify(rows)); }
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
  function renderRules(c){
    $('esiPctEE').textContent = Number(c.esiEmpPct ?? 0.75);
    $('esiPctER').textContent = Number(c.esiErPct ?? 3.25);
    $('esiWageLimitLabel').textContent = money(c.esiWageLimit ?? 21000);
  }

  async function generateApi(y,m){
    const r = await fetch(API_ESIC_GEN, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ month:m, year:y })
    });
    if(!r.ok) throw new Error('generate failed');
    return (await r.json()).sheet;
  }
  async function listApi(){
    const r = await fetch(API_ESIC_LIST, { cache:'no-store' });
    if(!r.ok) throw new Error('list failed');
    return (await r.json()).rows || [];
  }
  async function getApi(id){
    const r = await fetch(`${API_ESIC_LIST}/${id}`, { cache:'no-store' });
    if(!r.ok) throw new Error('get failed');
    return (await r.json()).sheet;
  }
  async function deleteApi(id){
    const r = await fetch(`${API_ESIC_LIST}/${id}`, { method:'DELETE' });
    if(!r.ok) throw new Error('delete failed');
  }
  async function clearApi(){
    const r = await fetch(API_ESIC_CLEAR, { method:'POST' });
    if(!r.ok) throw new Error('clear failed');
  }

  let sheetList = [];
  let previewSheet = null;

  function filterEsicApplicableRows(rows){
    return (rows || []).filter((r) => {
      const wages = Number(r.Total_Monthly_Wages ?? r['Total Monthly Wages'] ?? r.ESI_Wages ?? 0);
      const ee = Number(r.ESI_EE ?? r.EE_Contribution ?? 0);
      return wages > 0 && ee > 0;
    });
  }

  function renderPreview(rows, monthText = null){
    const filteredRows = filterEsicApplicableRows(rows);
    const show = filteredRows.slice(0,10);
    $('previewCount').textContent = `Rows: ${filteredRows.length}`;
    $('previewMonth').textContent = `Month: ${monthText || '-'}`;
    $('previewNote').textContent = filteredRows.length ? 'Preview ready.' : 'Click Generate to preview.';
    $('btnDownloadPreview').disabled = !filteredRows.length;
    $('previewBody').innerHTML = show.map((r, idx) => `
      <tr>
        <td class='fw-semibold'>${idx + 1}</td>
        <td>${r.Month || '-'}</td>
        <td class='mono'>${r.IP_Number || r.IP_No || r.ESI_No || '-'}</td>
        <td>${r.IP_Name || r['IP Name'] || r.Employee_Name || '-'}</td>
        <td class='text-end'>${r.No_of_Days_Paid ?? r['No of Days for which wages paid/payable during the month'] ?? 0}</td>
        <td class='text-end'>Rs ${money(r.Total_Monthly_Wages ?? r['Total Monthly Wages'] ?? r.ESI_Wages ?? 0)}</td>
        <td class='text-end'>${r.Reason_Code_Zero_Working_Days ?? r['Reason Code for Zero workings days'] ?? 0}</td>
        <td>${r.Last_Working_Day || r['Last Working Day'] || '-'}</td>
      </tr>
    `).join('');
  }

  async function renderLatestPreviewFromList(){
    if(!sheetList.length){
      previewSheet = null;
      renderPreview([]);
      return;
    }
    try {
      const latest = sheetList[0];
      const s = await getApi(latest.id);
      previewSheet = s;
      renderPreview(s.rows || [], s.period || monthKey(s.year, s.month));
    } catch(_e){
      // keep existing preview if fetch fails
    }
  }

  function downloadXlsx(item){
    const wb = XLSX.utils.book_new();
    const ordered = filterEsicApplicableRows(item.rows || []).map((r) => ({
      Month: r.Month || '',
      'IP Number': r.IP_Number || r.IP_No || r.ESI_No || '',
      'IP Name': r.IP_Name || r['IP Name'] || r.Employee_Name || '',
      'No of Days for which wages paid/payable during the month': Number(r.No_of_Days_Paid ?? r['No of Days for which wages paid/payable during the month'] ?? 0),
      'Total Monthly Wages': Number(r.Total_Monthly_Wages ?? r['Total Monthly Wages'] ?? r.ESI_Wages ?? 0),
      'Reason Code for Zero workings days': Number(r.Reason_Code_Zero_Working_Days ?? r['Reason Code for Zero workings days'] ?? 0),
      'Last Working Day': r.Last_Working_Day || r['Last Working Day'] || '',
    }));
    const columns = ['Month','IP Number','IP Name','No of Days for which wages paid/payable during the month','Total Monthly Wages','Reason Code for Zero workings days','Last Working Day'];
    const profile = safeParse(localStorage.getItem("hr_client_profile_v1")) || {};
    const info = [
      ["Company Name *", profile.companyName || ""],
      ["CIN / LLPIN / Reg. No", profile.regNo || profile.companyRegNo || ""],
      ["Company Address *", profile.companyAddress || ""],
      ["PAN", profile.pan || profile.companyPAN || ""],
      ["TAN", profile.tan || profile.companyTAN || ""],
      ["GSTIN", profile.gstin || profile.companyGSTIN || ""],
      ["PF Establishment ID", profile.pfEstId || ""],
      ["ESIC Employer Code", profile.esicCode || ""],
      []
    ];
    const ws = XLSX.utils.aoa_to_sheet(info);
    XLSX.utils.sheet_add_json(ws, ordered, { origin: "A10", skipHeader: false, header: columns });
    const colCount = columns.length;
    const rowStart = 10;
    const rowEnd = rowStart + ordered.length;
    const mkCol = (n) => { let s = ""; while(n > 0){ const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; };
    if(colCount > 0 && ordered.length){
      ws["!autofilter"] = { ref: `A${rowStart}:${mkCol(colCount)}${rowEnd}` };
      ws["!cols"] = Array.from({ length: colCount }, () => ({ wch: 18 }));
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
    XLSX.utils.book_append_sheet(wb, ws, 'ESIC_Sheet');
    XLSX.writeFileXLSX(wb, `esic_sheet_${item.period || monthKey(item.year,item.month)}.xlsx`, { cellStyles: true, bookType: "xlsx" });
  }

  function renderList(){
    $('sheetListBody').innerHTML = sheetList.map((s,i)=>`
      <tr>
        <td class='fw-semibold'>${i+1}</td>
        <td class='fw-semibold'>${s.period || monthKey(s.year,s.month)}</td>
        <td class='text-muted-3'>${new Date(s.generatedAt).toLocaleString()}</td>
        <td class='text-end'>${s.rowCount || 0}</td>
        <td class='text-end'>Rs ${money(s.totalWage || 0)}</td>
        <td class='text-end'>Rs ${money(s.totalEE || 0)}</td>
        <td class='text-end'>Rs ${money(s.totalER || 0)}</td>
        <td class='text-end fw-semibold'>Rs ${money(s.totalESI || 0)}</td>
        <td class='text-center'>
          <div class='btn-group'>
            <button class='btn btn-outline-primary btn-sm' title='View' aria-label='View' onclick="viewSheet('${s.id}')"><i class='bi bi-eye'></i></button>
            <button class='btn btn-outline-secondary btn-sm' title='Download' aria-label='Download' onclick="downloadSheet('${s.id}')"><i class='bi bi-file-earmark-excel'></i></button>
            <button class='btn btn-outline-danger btn-sm' title='Delete' aria-label='Delete' onclick="deleteSheet('${s.id}')"><i class='bi bi-trash'></i></button>
          </div>
        </td>
      </tr>
    `).join('');
    $('sheetListCount').textContent = `${sheetList.length} saved`;
  }

  window.viewSheet = async (id) => {
    try {
      const s = await getApi(id);
      previewSheet = s;
      renderPreview(s.rows || [], s.period || monthKey(s.year, s.month));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch(_e){ alert('Not found'); }
  };

  window.downloadSheet = async (id) => {
    try {
      const s = await getApi(id);
      if(!filterEsicApplicableRows(s.rows || []).length) return alert('No rows');
      downloadXlsx(s);
    } catch(_e){ alert('Unable to download'); }
  };

  window.deleteSheet = async (id) => {
    if(!confirm('Delete this ESIC sheet record?')) return;
    try {
      await deleteApi(id);
      sheetList = await listApi();
      saveLocal(sheetList);
      setStorageMode('API (SQLite)');
    } catch(_e){
      sheetList = loadLocal().filter(x => x.id !== id);
      saveLocal(sheetList);
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
    await renderLatestPreviewFromList();
  };

  async function generateAndSave(){
    const y = parseInt($('yearSel').value,10);
    const m = parseInt($('monthSel').value,10);
    const doneProcessing = window.HRCommon?.setProcessingState?.($('btnGenerate'), {
      busyText: 'Generating...',
      message: 'Please wait, we are preparing the ESIC sheet.'
    });
    try {
      const s = await generateApi(y,m);
      previewSheet = s;
      renderPreview(s.rows || [], s.period || monthKey(y,m));
      sheetList = await listApi();
      saveLocal(sheetList);
      setStorageMode('API (SQLite)');
      doneProcessing?.(`ESIC sheet ready for ${monthKey(y,m)}.`, false);
      alert('ESIC Sheet generated and saved.');
    } catch(_e){
      setStorageMode('Browser localStorage (offline mode)');
      doneProcessing?.(_e?.message || 'ESIC generation failed.', true);
      alert('Generate failed. Ensure backend is running and payroll exists for this month.');
    }
    renderList();
  }

  $('btnGenerate').addEventListener('click', generateAndSave);
  $('btnDownloadPreview').addEventListener('click', () => {
    if(!previewSheet || !filterEsicApplicableRows(previewSheet.rows || []).length) return;
    downloadXlsx(previewSheet);
  });

  $('btnLoadDummy')?.addEventListener('click', async () => {
    const y = parseInt($('yearSel').value,10);
    const m = parseInt($('monthSel').value,10);
    try {
      await fetch('/api/payroll/generate', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ month:m, year:y, absentMode:'LOP' })
      });
      alert('Dummy basis prepared via payroll generation. Now click Generate.');
    } catch(_e){
      alert('Unable to prepare data basis. Start backend first.');
    }
  });

  $('btnRefresh').addEventListener('click', async () => {
    try {
      sheetList = await listApi();
      saveLocal(sheetList);
      setStorageMode('API (SQLite)');
    } catch(_e){
      sheetList = loadLocal();
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
    await renderLatestPreviewFromList();
  });

  $('btnClearAll').addEventListener('click', async () => {
    if(!confirm('Clear ESIC sheet history?')) return;
    try {
      await clearApi();
      sheetList = [];
      setStorageMode('API (SQLite)');
    } catch(_e){
      localStorage.removeItem(KEY_ESIC_SHEETS);
      sheetList = [];
      setStorageMode('Browser localStorage (offline mode)');
    }
    previewSheet = null;
    renderPreview([]);
    renderList();
  });

  (async function init(){
    const now = new Date();
    $('monthSel').value = String(now.getMonth()+1);
    $('yearSel').value = String(now.getFullYear());
    try {
      const control = await fetchControl();
      renderRules(control);
    } catch(_e){
      const local = safeParse(localStorage.getItem(KEY_CONTROL)) || {};
      renderRules(local);
    }

    try {
      sheetList = await listApi();
      saveLocal(sheetList);
      setStorageMode('API (SQLite)');
    } catch(_e){
      sheetList = loadLocal();
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
    await renderLatestPreviewFromList();
  })();

