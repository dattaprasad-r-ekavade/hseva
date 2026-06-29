(function () {
  "use strict";

  const page = document.body.getAttribute("data-face-page") || "";
  if (!page) return;

  const $ = (id) => document.getElementById(id);
  const auth = JSON.parse(sessionStorage.getItem("hr_auth_session_v1") || "null");
  const authRole = String(auth?.user?.role || "").toLowerCase();
  const authEmpId = String(auth?.user?.empId || "").toUpperCase();
  const today = new Date();
  const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  const MODEL_URL_FALLBACK = "https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights";
  let mediaStream = null;
  let autoScanTimer = null;
  let modelsLoaded = false;
  let settingsCache = null;
  let lastRecognizedEmployeeId = "";
  let attendanceEditModal = null;
  let selectedScanMode = "IN";

  function fmtDate(value) {
    const raw = String(value || "").trim();
    if (!raw) return "-";
    const dt = new Date(raw);
    if (Number.isNaN(dt.getTime())) return raw;
    return dt.toLocaleString(undefined, { year: "numeric", month: "short", day: "2-digit", hour: "2-digit", minute: "2-digit" });
  }

  function setStatus(text, ok = true) {
    const el = $("pageStatus");
    if (!el) return;
    el.className = `alert ${ok ? "alert-success" : "alert-danger"} mb-0`;
    el.textContent = text;
  }

  function setInlineMessage(title, line, ok = true) {
    const wrap = $("scanMessage");
    if (!wrap) return;
    wrap.className = `scan-message p-3 ${ok ? "" : "error"}`;
    const titleEl = $("scanMessageTitle");
    const lineEl = $("scanMessageLine");
    if (titleEl) titleEl.textContent = title;
    if (lineEl) lineEl.textContent = line;
  }

  function scanModeMessage(mode, distance) {
    const cm = Number(distance || 45);
    if (String(mode || "").toUpperCase() === "OUT") {
      return {
        title: "OUT Scan ready",
        line: `Stand about ${cm} cm from the camera. OUT Scan is selected and will mark employee exit attendance.`
      };
    }
    return {
      title: "IN Scan ready",
      line: `Stand about ${cm} cm from the camera. IN Scan is selected and will mark employee entry attendance.`
    };
  }

  function renderRecognizedEmployee(row) {
    if (!$("recognizedEmployeeBadge")) return;
    if (!row || !row.employeeId) {
      $("recognizedEmployeeBadge").textContent = "Employee: -";
      return;
    }
    $("recognizedEmployeeBadge").textContent = `Employee: ${row.employeeName || "-"} (${row.employeeId || "-"})`;
  }

  async function readJson(res) {
    const text = await res.text();
    if (!text) return {};
    try { return JSON.parse(text); } catch (_e) { return {}; }
  }

  async function apiFetch(url, init) {
    const res = await fetch(url, init || {});
    const data = await readJson(res);
    if (!res.ok) throw new Error(data?.detail || "Request failed");
    return data;
  }

  function fmtDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
  }

  function monthKey(date) {
    return { month: date.getMonth() + 1, year: date.getFullYear() };
  }

  async function loadSettings() {
    if (settingsCache) return settingsCache;
    const data = await apiFetch("/api/face-attendance/settings");
    settingsCache = data.row || {};
    return settingsCache;
  }

  async function loadModels() {
    if (modelsLoaded) return;
    const settings = await loadSettings().catch(() => ({}));
    const modelUrl = String(settings?.modelUrl || MODEL_URL_FALLBACK);
    const opts = [modelUrl, MODEL_URL_FALLBACK];
    let loaded = false;
    for (const base of opts) {
      try {
        await Promise.all([
          faceapi.nets.tinyFaceDetector.loadFromUri(base),
          faceapi.nets.faceLandmark68Net.loadFromUri(base),
          faceapi.nets.faceRecognitionNet.loadFromUri(base)
        ]);
        loaded = true;
        break;
      } catch (_e) {}
    }
    if (!loaded) throw new Error("Face recognition models could not be loaded.");
    modelsLoaded = true;
  }

  async function startCamera() {
    const video = $("cameraVideo");
    if (!video) return;
    if (mediaStream) return;
    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "user", width: { ideal: 640 }, height: { ideal: 480 } },
      audio: false
    });
    video.srcObject = mediaStream;
    await video.play();
  }

  function stopCamera() {
    if (!mediaStream) return;
    mediaStream.getTracks().forEach((track) => track.stop());
    mediaStream = null;
  }

  function stopAutoScan() {
    if (autoScanTimer) {
      clearInterval(autoScanTimer);
      autoScanTimer = null;
    }
  }

  async function captureFace() {
    const video = $("cameraVideo");
    if (!video) throw new Error("Camera preview not found");
    await loadModels();
    const detection = await faceapi
      .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
      .withFaceLandmarks()
      .withFaceDescriptor();
    if (!detection) throw new Error("Face not detected. Please look at the camera and try again.");
    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    return {
      descriptor: Array.from(detection.descriptor || []),
      image: canvas.toDataURL("image/jpeg", 0.85)
    };
  }

  function employeeOptionHtml(employee) {
    return `<option value="${employee.id}">${employee.name} (${employee.id})</option>`;
  }

  async function loadEmployeesIntoSelect(selectId, includeBlank) {
    const select = $(selectId);
    if (!select) return [];
    const data = await apiFetch("/api/employees?activeOnly=1");
    const rows = Array.isArray(data.rows) ? data.rows : [];
    select.innerHTML = includeBlank ? '<option value="">Select employee</option>' : "";
    select.insertAdjacentHTML("beforeend", rows.map(employeeOptionHtml).join(""));
    if (authEmpId && authRole === "employee") {
      select.value = authEmpId;
      select.disabled = true;
    }
    return rows;
  }

  async function refreshRegistrations() {
    const body = $("registrationTableBody");
    if (!body) return;
    const data = await apiFetch("/api/face-attendance/registrations");
    const rows = Array.isArray(data.rows) ? data.rows : [];
    const table = body.closest("table");
    const headTexts = Array.from(table?.querySelectorAll("thead th") || []).map((th) => String(th.textContent || "").trim().toLowerCase());
    const hasLegacyShape = headTexts.includes("sr. no") || headTexts.includes("date & time");
    const hasAction = headTexts.includes("action");
    const colSpan = Math.max(headTexts.length || 0, hasLegacyShape ? (hasAction ? 9 : 8) : (hasAction ? 7 : 6));
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">No face registrations found.</td></tr>`;
      return;
    }
    body.innerHTML = rows.map((row, index) => {
      if (hasLegacyShape) {
        return `
          <tr>
            <td>${index + 1}</td>
            <td>${row.employeeId || "-"}</td>
            <td>${fmtDate(row.__updatedAt)}</td>
            <td>${row.employeeName || "-"}</td>
            <td>${row.department || "-"}</td>
            <td>${row.designation || "-"}</td>
            <td>${row.faceImage ? `<img class="face-thumb" src="${row.faceImage}" alt="Face">` : "-"}</td>
            <td>${fmtDate(row.__updatedAt)}</td>
            ${hasAction ? `<td><div class="d-flex gap-2 justify-content-center"><button class="btn btn-sm btn-outline-primary" data-face-reg-edit="${row.employeeId}"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" data-face-reg-delete="${row.employeeId}"><i class="bi bi-trash"></i></button></div></td>` : ""}
          </tr>
        `;
      }
      return `
        <tr>
          <td>${row.employeeId || "-"}</td>
          <td>${row.employeeName || "-"}</td>
          <td>${row.department || "-"}</td>
          <td>${row.designation || "-"}</td>
          <td>${row.faceImage ? `<img class="face-thumb" src="${row.faceImage}" alt="Face">` : "-"}</td>
          <td>${fmtDate(row.__updatedAt)}</td>
          ${hasAction ? `<td><div class="d-flex gap-2 justify-content-center"><button class="btn btn-sm btn-outline-primary" data-face-reg-edit="${row.employeeId}"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" data-face-reg-delete="${row.employeeId}"><i class="bi bi-trash"></i></button></div></td>` : ""}
        </tr>
      `;
    }).join("");
  }

  async function initRegistrationPage() {
    if (authRole === "employee") {
      setStatus("Employee login cannot register faces. Please use admin/HR login.", false);
      return;
    }
    await loadEmployeesIntoSelect("employeeId", true);
    await loadModels();
    await startCamera();
    await refreshRegistrations();

    $("btnRegisterFace")?.addEventListener("click", async () => {
      const employeeId = String($("employeeId")?.value || "").toUpperCase();
      if (!employeeId) return setStatus("Please select an employee first.", false);
      try {
        setStatus("Capturing face and saving registration...", true);
        const face = await captureFace();
        await apiFetch("/api/face-attendance/register", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ employeeId, faceDescriptor: face.descriptor, faceImage: face.image })
        });
        setStatus("Employee face registered successfully.", true);
        await refreshRegistrations();
      } catch (err) {
        setStatus(err.message || "Unable to register face.", false);
      }
    });

    $("registrationTableBody")?.addEventListener("click", async (event) => {
      const editBtn = event.target.closest("[data-face-reg-edit]");
      const delBtn = event.target.closest("[data-face-reg-delete]");
      try {
        if (editBtn) {
          const employeeId = String(editBtn.getAttribute("data-face-reg-edit") || "").toUpperCase();
          if ($("employeeId")) $("employeeId").value = employeeId;
          setStatus(`Selected ${employeeId}. Position face in camera and click "Capture & Register Face" to update.`, true);
          window.scrollTo({ top: 0, behavior: "smooth" });
          return;
        }
        if (delBtn) {
          const employeeId = String(delBtn.getAttribute("data-face-reg-delete") || "").toUpperCase();
          if (!window.confirm(`Delete face registration for ${employeeId}?`)) return;
          await apiFetch(`/api/face-attendance/registrations/${encodeURIComponent(employeeId)}`, { method: "DELETE" });
          setStatus(`Face registration deleted for ${employeeId}.`, true);
          await refreshRegistrations();
        }
      } catch (err) {
        setStatus(err.message || "Unable to complete action.", false);
      }
    });
  }

  async function initSettingsPage() {
    if (authRole === "employee") {
      setStatus("Employee login cannot update scan settings.", false);
      return;
    }
    const row = await loadSettings();
    ["inAllowedFrom", "inAllowedTill", "lateMarkAfter", "outAllowedFrom", "outAllowedTill", "timezone", "modelUrl"].forEach((key) => {
      if ($(key)) $(key).value = row[key] || "";
    });
    if ($("graceTime")) $("graceTime").value = row.graceTime ?? 10;
    if ($("faceMatchThreshold")) $("faceMatchThreshold").value = row.faceMatchThreshold ?? 0.48;
    if ($("autoCaptureSeconds")) $("autoCaptureSeconds").value = row.autoCaptureSeconds ?? 2;
    if ($("scanDistanceCm")) $("scanDistanceCm").value = row.scanDistanceCm ?? 45;

    $("settingsForm")?.addEventListener("submit", async (event) => {
      event.preventDefault();
      try {
        const payload = {
          inAllowedFrom: $("inAllowedFrom").value,
          inAllowedTill: $("inAllowedTill").value,
          lateMarkAfter: $("lateMarkAfter").value,
          outAllowedFrom: $("outAllowedFrom").value,
          outAllowedTill: $("outAllowedTill").value,
          graceTime: Number($("graceTime").value || 0),
          faceMatchThreshold: Number($("faceMatchThreshold").value || 0.48),
          timezone: $("timezone").value,
          modelUrl: $("modelUrl").value,
          autoCaptureSeconds: Number($("autoCaptureSeconds").value || 2),
          scanDistanceCm: Number($("scanDistanceCm")?.value || 45)
        };
        const data = await apiFetch("/api/face-attendance/settings", {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });
        settingsCache = data.row || payload;
        setStatus("Scan attendance settings saved successfully.", true);
      } catch (err) {
        setStatus(err.message || "Unable to save settings.", false);
      }
    });
  }

  function renderRows(bodyId, rows) {
    const body = $(bodyId);
    if (!body) return;
    const table = body.closest("table");
    const headTexts = Array.from(table?.querySelectorAll("thead th") || []).map((th) => String(th.textContent || "").trim().toLowerCase());
    const hasLegacyShape = headTexts.includes("sr. no") || headTexts.includes("date & time");
    const hasAction = headTexts.includes("action");
    const makeDateTime = (row) => {
      const date = row.attendanceDate || "-";
      const time = row.inTime || row.outTime || "-";
      return `${date} ${time}`.trim();
    };

    if (!rows.length) {
      const colSpan = Math.max(headTexts.length || 0, hasLegacyShape ? 13 : 12);
      body.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">No attendance rows found.</td></tr>`;
      return;
    }

    body.innerHTML = rows.map((row, index) => {
      if (hasLegacyShape) {
        return `
          <tr>
            <td>${index + 1}</td>
            <td>${row.employeeId || "-"}</td>
            <td>${makeDateTime(row)}</td>
            <td>${row.employeeName || "-"}</td>
            <td>${row.department || "-"}</td>
            <td>${row.designation || "-"}</td>
            <td>${row.attendanceDate || "-"}</td>
            <td>${row.inTime || "-"}</td>
            <td>${row.outTime || "-"}</td>
            <td>${row.totalWorkingHours ?? 0}</td>
            <td>${row.attendanceStatus || "-"}</td>
            <td>${row.inStatus || "-"}</td>
            <td>${row.outStatus || "-"}</td>
            <td>${row.remarks || "-"}</td>
            ${hasAction ? `<td><div class="d-flex gap-2 justify-content-center"><button class="btn btn-sm btn-outline-primary" data-face-edit="${row.id}"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" data-face-delete="${row.id}"><i class="bi bi-trash"></i></button></div></td>` : ""}
          </tr>
        `;
      }
      return `
        <tr>
          <td>${row.employeeId || "-"}</td>
          <td>${row.employeeName || "-"}</td>
          <td>${row.department || "-"}</td>
          <td>${row.designation || "-"}</td>
          <td>${row.attendanceDate || "-"}</td>
          <td>${row.inTime || "-"}</td>
          <td>${row.outTime || "-"}</td>
          <td>${row.totalWorkingHours ?? 0}</td>
          <td>${row.attendanceStatus || "-"}</td>
          <td>${row.inStatus || "-"}</td>
          <td>${row.outStatus || "-"}</td>
          <td>${row.remarks || "-"}</td>
          ${hasAction ? `<td><div class="d-flex gap-2 justify-content-center"><button class="btn btn-sm btn-outline-primary" data-face-edit="${row.id}"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" data-face-delete="${row.id}"><i class="bi bi-trash"></i></button></div></td>` : ""}
        </tr>
      `;
    }).join("");
  }

  function fillAttendanceEditForm(row) {
    if (!$("editAttendanceId")) return;
    $("editAttendanceId").value = row.id || "";
    $("editAttendanceDate").value = row.attendanceDate || "";
    $("editInTime").value = row.inTime || "";
    $("editOutTime").value = row.outTime || "";
    $("editHours").value = row.totalWorkingHours ?? 0;
    $("editAttendanceStatus").value = row.attendanceStatus || "";
    $("editInStatus").value = row.inStatus || "";
    $("editOutStatus").value = row.outStatus || "";
    $("editRemarks").value = row.remarks || "";
  }

  async function fetchAttendanceRecord(id) {
    const data = await apiFetch(`/api/face-attendance/attendance/${id}`);
    return data.row || null;
  }

  async function saveAttendanceEdit() {
    const id = Number($("editAttendanceId")?.value || 0);
    if (!id) return;
    const payload = {
      attendanceDate: $("editAttendanceDate").value,
      inTime: $("editInTime").value,
      outTime: $("editOutTime").value,
      totalWorkingHours: Number($("editHours").value || 0),
      attendanceStatus: $("editAttendanceStatus").value,
      inStatus: $("editInStatus").value,
      outStatus: $("editOutStatus").value,
      remarks: $("editRemarks").value
    };
    await apiFetch(`/api/face-attendance/attendance/${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    if (attendanceEditModal) attendanceEditModal.hide();
    $("btnLoadSheet")?.click();
  }

  async function deleteAttendanceRecord(id) {
    if (!window.confirm("Delete this attendance record?")) return;
    await apiFetch(`/api/face-attendance/attendance/${id}`, { method: "DELETE" });
    $("btnLoadSheet")?.click();
  }

  async function initSheetPage() {
    if (authRole !== "employee") await loadEmployeesIntoSelect("employeeIdFilter", true);
    const dateInput = $("attendanceDate");
    if (dateInput) dateInput.value = fmtDate(today);
    if ($("attendanceEditModal") && window.bootstrap) {
      attendanceEditModal = new bootstrap.Modal(document.getElementById("attendanceEditModal"));
    }

    async function loadSheet() {
      try {
        const params = new URLSearchParams();
        const date = dateInput?.value || "";
        const employeeId = String($("employeeIdFilter")?.value || "").toUpperCase();
        if (date) params.set("date", date);
        if (employeeId) params.set("employeeId", employeeId);
        const data = await apiFetch(`/api/face-attendance/sheet?${params.toString()}`);
        renderRows("attendanceSheetBody", Array.isArray(data.rows) ? data.rows : []);
        setStatus("Attendance sheet loaded.", true);
      } catch (err) {
        setStatus(err.message || "Unable to load attendance sheet.", false);
      }
    }

    $("btnLoadSheet")?.addEventListener("click", loadSheet);
    $("attendanceSheetBody")?.addEventListener("click", async (event) => {
      const editBtn = event.target.closest("[data-face-edit]");
      const delBtn = event.target.closest("[data-face-delete]");
      try {
        if (editBtn) {
          const row = await fetchAttendanceRecord(Number(editBtn.getAttribute("data-face-edit")));
          if (!row) return;
          fillAttendanceEditForm(row);
          attendanceEditModal?.show();
          return;
        }
        if (delBtn) {
          await deleteAttendanceRecord(Number(delBtn.getAttribute("data-face-delete")));
        }
      } catch (err) {
        alert(err.message || "Unable to complete attendance action.");
      }
    });
    $("btnSaveAttendanceEdit")?.addEventListener("click", async () => {
      try {
        await saveAttendanceEdit();
      } catch (err) {
        alert(err.message || "Unable to save attendance changes.");
      }
    });
    await loadSheet();
  }

  function renderReportRows(rows) {
    const body = $("attendanceReportBody");
    if (!body) return;
    const table = body.closest("table");
    const headTexts = Array.from(table?.querySelectorAll("thead th") || []).map((th) => String(th.textContent || "").trim().toLowerCase());
    const hasLegacyShape = headTexts.includes("sr. no") || headTexts.includes("date & time");
    const colSpan = Math.max(headTexts.length || 0, hasLegacyShape ? 11 : 9);
    if (!rows.length) {
      body.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">No monthly attendance report rows found.</td></tr>`;
      return;
    }
    body.innerHTML = rows.map((row, index) => {
      if (hasLegacyShape) {
        return `
          <tr>
            <td>${index + 1}</td>
            <td>${row.employeeId || "-"}</td>
            <td>-</td>
            <td>${row.employeeName || "-"}</td>
            <td>${row.department || "-"}</td>
            <td>${row.designation || "-"}</td>
            <td>${row.presentDays ?? 0}</td>
            <td>${row.lateDays ?? 0}</td>
            <td>${row.earlyOutDays ?? 0}</td>
            <td>${row.missingOutDays ?? 0}</td>
            <td>${row.totalWorkingHours ?? 0}</td>
          </tr>
        `;
      }
      return `
        <tr>
          <td>${row.employeeId || "-"}</td>
          <td>${row.employeeName || "-"}</td>
          <td>${row.department || "-"}</td>
          <td>${row.designation || "-"}</td>
          <td>${row.presentDays ?? 0}</td>
          <td>${row.lateDays ?? 0}</td>
          <td>${row.earlyOutDays ?? 0}</td>
          <td>${row.missingOutDays ?? 0}</td>
          <td>${row.totalWorkingHours ?? 0}</td>
        </tr>
      `;
    }).join("");
  }

  async function initReportPage() {
    if (authRole === "employee") {
      setStatus("Employee login can view My Attendance from the employee page.", false);
      return;
    }
    await loadEmployeesIntoSelect("employeeIdReport", true);
    if ($("reportMonth")) $("reportMonth").value = String(today.getMonth() + 1);
    if ($("reportYear")) $("reportYear").value = String(today.getFullYear());
    let reportRows = [];

    async function loadReport() {
      try {
        const params = new URLSearchParams();
        params.set("month", $("reportMonth").value);
        params.set("year", $("reportYear").value);
        if ($("employeeIdReport").value) params.set("employeeId", $("employeeIdReport").value);
        const data = await apiFetch(`/api/face-attendance/report?${params.toString()}`);
        reportRows = Array.isArray(data.rows) ? data.rows : [];
        renderReportRows(reportRows);
        setStatus("Monthly attendance report loaded.", true);
      } catch (err) {
        setStatus(err.message || "Unable to load report.", false);
      }
    }

    $("btnLoadReport")?.addEventListener("click", loadReport);
    $("btnExportReport")?.addEventListener("click", () => {
      if (!reportRows.length || !window.XLSX) return setStatus("No report rows available for export.", false);
      const aoa = [
        ["Monthly Face Attendance Report"],
        [`Month: ${monthNames[Number($("reportMonth").value) - 1] || "-"}`],
        [`Year: ${$("reportYear").value}`],
        [],
        ["Employee ID", "Employee Name", "Department", "Designation", "Present Days", "Late Days", "Early Out Days", "Missing OUT Days", "Total Working Hours"]
      ];
      reportRows.forEach((row) => {
        aoa.push([row.employeeId, row.employeeName, row.department, row.designation, row.presentDays, row.lateDays, row.earlyOutDays, row.missingOutDays, row.totalWorkingHours]);
      });
      const ws = XLSX.utils.aoa_to_sheet(aoa);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Face_Attendance_Report");
      XLSX.writeFileXLSX(wb, `face_attendance_report_${$("reportYear").value}_${String($("reportMonth").value).padStart(2, "0")}.xlsx`, { cellStyles: true, bookType: "xlsx" });
    });

    await loadReport();
  }

  async function loadOwnAttendance() {
    const target = page === "scan" ? "myAttendanceBody" : "myOwnAttendanceBody";
    const keys = monthKey(today);
    if (authRole === "employee") {
      const data = await apiFetch(`/api/face-attendance/my-attendance?month=${keys.month}&year=${keys.year}`);
      renderRows(target, Array.isArray(data.rows) ? data.rows : []);
      return;
    }
    const employeeId = String(lastRecognizedEmployeeId || "").toUpperCase();
    if (!employeeId) {
      renderRows(target, []);
      return;
    }
    const data = await apiFetch(`/api/face-attendance/sheet?month=${keys.month}&year=${keys.year}&employeeId=${encodeURIComponent(employeeId)}`);
    renderRows(target, Array.isArray(data.rows) ? data.rows : []);
  }

  async function runScan() {
    try {
      if (selectedScanMode === "OUT") {
        setInlineMessage("Scanning for OUT attendance...", "Please stay in front of the camera to mark employee exit.", true);
      } else {
        setInlineMessage("Scanning for IN attendance...", "Please stay in front of the camera to mark employee entry.", true);
      }
      const face = await captureFace();
      const payload = { faceDescriptor: face.descriptor, faceImage: face.image, scanMode: selectedScanMode || "IN" };
      if (authRole !== "employee" && $("employeeIdForScan")?.value) payload.employeeId = $("employeeIdForScan").value;
      const data = await apiFetch("/api/face-attendance/scan", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      lastRecognizedEmployeeId = String(data?.row?.employeeId || "").toUpperCase();
      setInlineMessage(data.messageTitle || "Face Verified Successfully", data.messageLine || "Attendance saved successfully.", true);
      if ($("lastActionBadge")) $("lastActionBadge").textContent = `Last action: ${data.action || "-"}`;
      if ($("lastTimeBadge")) $("lastTimeBadge").textContent = `Current time: ${(data.row && (data.action === "OUT" ? data.row.outTime : data.row.inTime)) || "-"}`;
      renderRecognizedEmployee(data?.row || null);
      await loadOwnAttendance();
      if (data.action === "COMPLETED") stopAutoScan();
    } catch (err) {
      setInlineMessage("Face not verified", err.message || "Please try again or contact admin.", false);
    }
  }

  async function initScanPage() {
    await loadModels();
    await startCamera();
    await loadOwnAttendance().catch(() => {});
    const settings = await loadSettings().catch(() => ({ autoCaptureSeconds: 2 }));
    const seconds = Number(settings.autoCaptureSeconds || 2);
    const distance = Number(settings.scanDistanceCm || 45);
    stopAutoScan();
    autoScanTimer = setInterval(() => { runScan().catch(() => {}); }, Math.max(1, seconds) * 1000);
    function markMode(mode) {
      selectedScanMode = mode;
      document.querySelectorAll("[data-scan-mode]").forEach((btn) => btn.classList.remove("active"));
      const activeBtn = document.querySelector(`[data-scan-mode="${mode}"]`);
      if (activeBtn) activeBtn.classList.add("active");
      const modeMsg = scanModeMessage(mode, distance);
      setInlineMessage(modeMsg.title, modeMsg.line, true);
    }
    $("btnScanNow")?.addEventListener("click", () => runScan());
    document.querySelectorAll("[data-scan-mode]").forEach((btn) => {
      btn.addEventListener("click", () => {
        markMode(String(btn.getAttribute("data-scan-mode") || "IN").toUpperCase());
      });
    });
    markMode("IN");
  }

  async function initMyAttendancePage() {
    await loadOwnAttendance();
  }

  window.addEventListener("beforeunload", () => {
    stopAutoScan();
    stopCamera();
  });

  (async function init() {
    try {
      if (page === "register") await initRegistrationPage();
      if (page === "settings") await initSettingsPage();
      if (page === "sheet") await initSheetPage();
      if (page === "report") await initReportPage();
      if (page === "scan") await initScanPage();
      if (page === "my-attendance") await initMyAttendancePage();
    } catch (err) {
      setStatus(err.message || "Unable to load face attendance page.", false);
    }
  })();
})();
