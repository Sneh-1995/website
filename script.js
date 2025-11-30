// ---------- Basic Course Modal ----------
function openCourse() {
  document.getElementById("courseDetails").style.display = "flex";
  document.body.style.overflow = "hidden";
}
function closeCourse() {
  document.getElementById("courseDetails").style.display = "none";
  document.body.style.overflow = "";
}


// ---------- ₹8000 Modal ----------
function show8000Modal() {
  document.getElementById("modal-8000").style.display = "flex";
  document.body.style.overflow = "hidden";
}
function close8000Modal() {
  document.getElementById("modal-8000").style.display = "none";
  document.body.style.overflow = "";
}


// ---------- Project Modal ----------
function showProjectModal() {
  document.getElementById("project-modal-bg").style.display = "flex";
  document.body.style.overflow = "hidden";
}
function hideProjectModal() {
  document.getElementById("project-modal-bg").style.display = "none";
  document.body.style.overflow = "";
}


// ---------- Close modal when clicking outside ----------
window.addEventListener("click", function (e) {
  document.querySelectorAll(".modal-bg").forEach(bg => {
    if (e.target === bg) {
      bg.style.display = "none";
      document.body.style.overflow = "";
    }
  });
});
// ---------------- Google Tag Coversion Function -------------
function fireConversion() {
  gtag('event', 'conversion', {
    send_to: 'AW-17744245054/HddlCO-WxsUbEL7jjo1C'
  });
  console.log("🔥 Google Ads Conversion Fired");
}

document.addEventListener("DOMContentLoaded", () => {

  // Main CTA button
  const mainBtn = document.querySelector(".aws-btn");
  if (mainBtn) {
    mainBtn.addEventListener("click", fireConversion);
  }

  // All enroll buttons
  const enrollBtns = document.querySelectorAll(".enroll-btn");
  enrollBtns.forEach(btn => {
    btn.addEventListener("click", fireConversion);
  });

});
