document.getElementById("toggleSidebarBtn").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("sidebar-collapsed");
    document.getElementById("mainContent").classList.toggle("content-collapsed");
});

// Automatically hide success message after 3 seconds
setTimeout(() => {
    const alert = document.getElementById('success-alert');
    if (alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500); // Remove element after fade-out
    }
}, 3000); // 3 seconds delay