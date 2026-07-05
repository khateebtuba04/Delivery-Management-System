// assets/js/main.js
document.addEventListener("DOMContentLoaded", function () {
    // Sidebar collapse/expand toggle
    const sidebarCollapseBtn = document.getElementById("sidebarCollapse");
    const sidebar = document.getElementById("sidebar");
    
    if (sidebarCollapseBtn && sidebar) {
        sidebarCollapseBtn.addEventListener("click", function () {
            sidebar.classList.toggle("active");
        });
    }

    // Bootstrap Form Validation Enforcer
    const forms = document.querySelectorAll(".needs-validation");
    Array.from(forms).forEach((form) => {
        form.addEventListener(
            "submit",
            function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add("was-validated");
            },
            false
        );
    });

    // Custom helper for confirming deletions or critical actions
    const confirmActions = document.querySelectorAll(".confirm-action");
    confirmActions.forEach((btn) => {
        btn.addEventListener("click", function (e) {
            const message = btn.getAttribute("data-confirm-message") || "Are you sure you want to perform this action?";
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
