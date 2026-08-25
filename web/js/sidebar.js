/*!
 * Sidebar collapse toggle + scroll-to-top button, re-implemented in vanilla JS
 * (the original SB Admin 2 theme's js/sb-admin-2.js relies on jQuery's Bootstrap 4
 * `.collapse()` plugin, which doesn't exist in Bootstrap 5 — this app doesn't use
 * the collapsible sidebar sub-menu feature anyway, so only the two behaviors
 * actually needed here are reimplemented, matching this app's existing convention
 * of hand-written vanilla JS files (color-mode.js, report-form.js, dynamic-rows.js)).
 */
document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('#sidebarToggle, #sidebarToggleTop');
    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-toggled');
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('toggled');
            }
        });
    });

    window.addEventListener('resize', function () {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) {
            return;
        }
        if (window.innerWidth < 480 && !sidebar.classList.contains('toggled')) {
            document.body.classList.add('sidebar-toggled');
            sidebar.classList.add('toggled');
        }
    });

    var scrollToTopBtn = document.querySelector('.scroll-to-top');
    if (scrollToTopBtn) {
        window.addEventListener('scroll', function () {
            scrollToTopBtn.style.display = window.scrollY > 100 ? 'block' : 'none';
        });
        scrollToTopBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
