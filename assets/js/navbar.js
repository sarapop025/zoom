/**
 * ============================================================
 * assets/js/navbar.js
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const toggle =
        document.querySelector(
            '[data-sidebar-toggle]'
        );

    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', function () {

        document.body.classList.toggle(
            'sidebar-open'
        );

    });

});