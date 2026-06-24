(function () {
  "use strict";
  var target = document.body ? document.body.getAttribute("data-redirect-to") : "";
  if (target) {
    window.location.replace(target);
  }
})();
