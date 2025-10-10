
function openCourse() {
    document.getElementById('courseDetails').style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeCourse() {
    document.getElementById('courseDetails').style.display = 'none';
    document.body.style.overflow = '';
}
window.addEventListener('click', function (e) {
    const modal = document.getElementById('courseDetails');
    if (e.target === modal) { closeCourse(); }
});

function openCourse() {
    document.getElementById('courseDetails').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeCourse() {
    document.getElementById('courseDetails').style.display = 'none';
    document.body.style.overflow = '';
}

window.addEventListener('click', function (e) {
    const modal = document.getElementById('courseDetails');
    if (e.target === modal) {
        closeCourse();
    }
});

// ✅ Project Modal
function showProjectModal() {
    document.getElementById('project-modal-bg').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function hideProjectModal() {
    document.getElementById('project-modal-bg').style.display = 'none';
    document.body.style.overflow = '';
}

// Optional: Close modal when clicking outside content
window.addEventListener('click', function (e) {
    const projectModal = document.getElementById('project-modal-bg');
    const modalContent = document.querySelector('.modal-content');
    if (e.target === projectModal && !modalContent.contains(e.target)) {
        hideProjectModal();
    }
});
