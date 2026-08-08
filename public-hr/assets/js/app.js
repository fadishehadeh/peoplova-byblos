document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-auto-focus="true"]').forEach(function (element) {
        element.focus();
    });

    document.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
        var label = null;
        var parent = field.parentElement;

        if (field.id) {
            label = document.querySelector('label[for="' + field.id.replace(/"/g, '\\"') + '"]');
        }

        if (!label && parent) {
            label = Array.prototype.find.call(parent.children, function (child) {
                return child.matches && child.matches('label.form-label');
            });
        }

        if (label) {
            label.classList.add('required-label');
            label.childNodes.forEach(function (node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = node.textContent.replace(/\s*\*\s*$/, '');
                }
            });
            label.querySelectorAll('span').forEach(function (span) {
                if (span.textContent.trim() === '*') {
                    span.remove();
                }
            });
        }
    });

    // Mobile sidebar toggle
    var sidebar   = document.getElementById('appSidebar');
    var overlay   = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarToggle');
    var closeBtn  = document.getElementById('sidebarClose');

    function openSidebar(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        if (sidebar) sidebar.classList.add('sidebar-open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        if (sidebar) sidebar.classList.remove('sidebar-open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', openSidebar);
        toggleBtn.addEventListener('touchend', openSidebar);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
        closeBtn.addEventListener('touchend', closeSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close mobile sidebar when any nav link is clicked
    if (sidebar) {
        sidebar.querySelectorAll('.sidebar-link, .sidebar-sublink').forEach(function (link) {
            link.addEventListener('click', function () {
                if (sidebar.classList.contains('sidebar-open')) {
                    sidebar.classList.remove('sidebar-open');
                    if (overlay) overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    }

    // Enforce single-open accordion — Bootstrap data-bs-parent can be unreliable
    document.querySelectorAll('.sidebar-group-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var targetId = this.getAttribute('href');
            if (!targetId) return;
            document.querySelectorAll('#sidebarNavAccordion .collapse.show').forEach(function (openPane) {
                if ('#' + openPane.id !== targetId) {
                    var instance = bootstrap.Collapse.getInstance(openPane);
                    if (instance) {
                        instance.hide();
                    } else {
                        openPane.classList.remove('show');
                    }
                }
            });
        });
    });
});
