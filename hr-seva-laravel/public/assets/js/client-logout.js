(function () {
  "use strict";
  try {
    localStorage.removeItem("hr_auth_session_v1");
    sessionStorage.removeItem("hr_auth_session_v1");
    localStorage.removeItem("hr_superadmin_selected_client_id_v1");
    localStorage.removeItem("hr_superadmin_selected_client_label_v1");
  } catch (_e) {}
  var path = (window.location.pathname || "").toLowerCase();
  var isSuperAdmin = path.indexOf("/super-admin/") >= 0;
  try {
    window.history.replaceState(null, "", isSuperAdmin ? "super-admin-login.html" : "client-login.html");
  } catch (_e2) {}
  window.location.replace(isSuperAdmin ? "super-admin-login.html" : "client-login.html");
})();
