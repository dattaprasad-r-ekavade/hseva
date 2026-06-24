const $ = (id) => document.getElementById(id);
  $('yr').textContent = new Date().getFullYear();

  const API_ECR_GEN = '/api/ecr-sheet/generate';
  const API_ECR_LIST = '/api/ecr-sheet/sheets';
  const API_ECR_CLEAR = '/api/ecr-sheet/clear';
  const API_CONTROL = '/api/control';
  const KEY_CONTROL = 'hr_client_control_v1';

  const KEY_ECR_SHEETS = 'hr_client_ecr_sheets_v1';

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

  function loadLocal(){ try { return JSON.parse(localStorage.getItem(KEY_ECR_SHEETS) || '[]'); } catch(_e){ return []; } }
  function saveLocal(rows){ localStorage.setItem(KEY_ECR_SHEETS, JSON.stringify(rows)); }
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
    $('pfPctEE').textContent = Number(c.pfEmpPct ?? 12);
    $('pfPctER').textContent = Number(c.pfErPct ?? 13);
    const enabled = String(c.pfWageCapEnabled ?? 'Yes').toLowerCase();
    $('pfCapEnabled').textContent = ['yes','true','1'].includes(enabled) ? 'Yes' : 'No';
    $('pfCapAmt').textContent = money(c.pfWageCapAmount ?? 15000);
  }

  async function generateApi(y,m){
    const r = await fetch(API_ECR_GEN, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ month:m, year:y })
    });
    if(!r.ok) throw new Error('generate failed');
    return (await r.json()).sheet;
  }
  async function listApi(){
    const r = await fetch(API_ECR_LIST, { cache:'no-store' });
    if(!r.ok) throw new Error('list failed');
    return (await r.json()).rows || [];
  }
  async function getApi(id){
    const r = await fetch(`${API_ECR_LIST}/${id}`, { cache:'no-store' });
    if(!r.ok) throw new Error('get failed');
    return (await r.json()).sheet;
  }
  async function deleteApi(id){
    const r = await fetch(`${API_ECR_LIST}/${id}`, { method:'DELETE' });
    if(!r.ok) throw new Error('delete failed');
  }
  async function clearApi(){
    const r = await fetch(API_ECR_CLEAR, { method:'POST' });
    if(!r.ok) throw new Error('clear failed');
  }

  let ecrList = [];
  let previewSheet = null;

  function renderPreview(rows, monthText = null){
    const show = (rows || []).slice(0,10);
    $('previewCount').textContent = `Rows: ${(rows || []).length}`;
    $('previewMonth').textContent = `Month: ${monthText || '-'}`;
    $('previewNote').textContent = (rows || []).length ? 'Preview ready.' : 'Click Generate to preview.';
    $('btnDownloadPreview').disabled = !(rows || []).length;
    $('previewBody').innerHTML = show.map(r => `
      <tr>
        <td class='mono'>${r.UAN || '-'}</td>
        <td>${r['MEMBER_ NAME'] || r.MEMBER_NAME || '-'}</td>
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
  }

  function downloadXlsx(item){
    const wb = XLSX.utils.book_new();
    const ordered = (item.rows || []).map(r => ({
      UAN: r.UAN || '',
      'MEMBER_ NAME': r['MEMBER_ NAME'] || r.MEMBER_NAME || '',
      GROSS_WAGES: Number(r.GROSS_WAGES || 0),
      EPF_WAGES: Number(r.EPF_WAGES || 0),
      EPS_WAGES: Number(r.EPS_WAGES || 0),
      EDLI_WAGES: Number(r.EDLI_WAGES || 0),
      EPF_CONTRI_REMITTED: Number(r.EPF_CONTRI_REMITTED || 0),
      EPS_CONTRI_REMITTED: Number(r.EPS_CONTRI_REMITTED || 0),
      EPF_EPS_DIFF_REMITTED: Number(r.EPF_EPS_DIFF_REMITTED || 0),
      NCP_DAYS: Number(r.NCP_DAYS || 0),
      REFUND_OF_ADVANCES: Number(r.REFUND_OF_ADVANCES || 0),
    }));
    const columns = [
      'UAN','MEMBER_ NAME','GROSS_WAGES','EPF_WAGES','EPS_WAGES','EDLI_WAGES',
      'EPF_CONTRI_REMITTED','EPS_CONTRI_REMITTED','EPF_EPS_DIFF_REMITTED','NCP_DAYS','REFUND_OF_ADVANCES'
    ];
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
      ws["!cols"] = Array.from({ length: colCount }, () => ({ wch: 16 }));
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
    XLSX.utils.book_append_sheet(wb, ws, 'ECR_Sheet');
    XLSX.writeFileXLSX(wb, `ecr_sheet_${item.period || monthKey(item.year,item.month)}.xlsx`, { cellStyles: true, bookType: "xlsx" });
  }

  function renderList(){
    $('tagCount').textContent = ecrList.length;
    $('listBody').innerHTML = ecrList.map((s,i)=>`
      <tr>
        <td class='fw-semibold'>${i+1}</td>
        <td class='fw-semibold'>${s.period || monthKey(s.year,s.month)}</td>
        <td class='text-muted-3'>${new Date(s.generatedAt).toLocaleString()}</td>
        <td class='text-end'>${s.rowCount || 0}</td>
        <td class='text-end'>${money(s.totalEPFWages || 0)}</td>
        <td class='text-end'>${money(s.totalEPFContri || 0)}</td>
        <td class='text-end'>${money(s.totalEPSContri || 0)}</td>
        <td class='text-end fw-semibold'>${money((s.totalEPFContri || 0) + (s.totalEPSContri || 0))}</td>
        <td class='text-end'>
          <button class='btn btn-outline-secondary btn-sm me-1' onclick="viewSheet('${s.id}')"><i class='bi bi-eye'></i></button>
          <button class='btn btn-outline-success btn-sm me-1' onclick="downloadSheet('${s.id}')"><i class='bi bi-file-earmark-excel'></i></button>
          <button class='btn btn-outline-danger btn-sm' onclick="deleteSheet('${s.id}')"><i class='bi bi-trash'></i></button>
        </td>
      </tr>
    `).join('');
    $('listCount').textContent = `${ecrList.length} saved`;
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
      if(!(s.rows || []).length) return alert('No rows');
      downloadXlsx(s);
    } catch(_e){ alert('Unable to download'); }
  };

  window.deleteSheet = async (id) => {
    if(!confirm('Delete this ECR sheet?')) return;
    try {
      await deleteApi(id);
      ecrList = await listApi();
      saveLocal(ecrList);
      setStorageMode('API (SQLite)');
    } catch(_e){
      ecrList = loadLocal().filter(x => x.id !== id);
      saveLocal(ecrList);
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
  };

  async function generateAndSave(){
    const y = parseInt($('yearSel').value,10);
    const m = parseInt($('monthSel').value,10);
    $('tagMonth').textContent = `Month: ${monthLabel(y,m)}`;
    const doneProcessing = window.HRCommon?.setProcessingState?.([$('btnGenerate'), $('btnGenerateTop')], {
      busyText: 'Generating...',
      message: 'Please wait, we are preparing the ECR sheet.'
    });
    try {
      const s = await generateApi(y,m);
      previewSheet = s;
      renderPreview(s.rows || [], s.period || monthKey(y,m));
      ecrList = await listApi();
      saveLocal(ecrList);
      setStorageMode('API (SQLite)');
      doneProcessing?.(`ECR sheet ready for ${monthKey(y,m)}.`, false);
      alert('ECR Sheet generated and saved.');
    } catch(_e){
      setStorageMode('Browser localStorage (offline mode)');
      doneProcessing?.(_e?.message || 'ECR generation failed.', true);
      alert('Generate failed. Ensure PF/Payroll exists for this month and backend is running.');
    }
    renderList();
  }

  $('btnGenerate').addEventListener('click', generateAndSave);
  $('btnGenerateTop').addEventListener('click', generateAndSave);
  $('btnDownloadPreview').addEventListener('click', () => {
    if(!previewSheet || !(previewSheet.rows || []).length) return;
    downloadXlsx(previewSheet);
  });

  $('btnDummy').addEventListener('click', async () => {
    const y = parseInt($('yearSel').value,10);
    const m = parseInt($('monthSel').value,10);
    try {
      await fetch('/api/pf-sheet/generate', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ month:m, year:y })
      });
      alert('Dummy basis prepared via PF Sheet generation. Now click Generate.');
    } catch(_e){
      alert('Unable to prepare data basis. Start backend first.');
    }
  });

  $('btnRefresh').addEventListener('click', async () => {
    try {
      ecrList = await listApi();
      saveLocal(ecrList);
      setStorageMode('API (SQLite)');
    } catch(_e){
      ecrList = loadLocal();
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
  });

  $('btnClearAll').addEventListener('click', async () => {
    if(!confirm('Clear ECR history?')) return;
    try {
      await clearApi();
      ecrList = [];
      setStorageMode('API (SQLite)');
    } catch(_e){
      localStorage.removeItem(KEY_ECR_SHEETS);
      ecrList = [];
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
    $('tagMonth').textContent = `Month: ${monthLabel(now.getFullYear(), now.getMonth()+1)}`;

    try {
      const control = await fetchControl();
      renderRules(control);
    } catch(_e){
      const local = safeParse(localStorage.getItem(KEY_CONTROL)) || {};
      renderRules(local);
    }

    try {
      ecrList = await listApi();
      saveLocal(ecrList);
      setStorageMode('API (SQLite)');
    } catch(_e){
      ecrList = loadLocal();
      setStorageMode('Browser localStorage (offline mode)');
    }
    renderList();
    previewSheet = null;
    renderPreview([]);
  })();

