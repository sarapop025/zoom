/**
 * ============================================================
 * assets/js/sidebar.js
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const sidebar =
        document.querySelector('.sidebar');

    if (!sidebar) {
        return;
    }


    /*
     * Active menu
     *
     * ถ้าเมนูมี data-menu-path
     * จะตรวจสอบ URL ปัจจุบัน
     */

    const currentPath =
        window.location.pathname;


    const menuLinks =
        sidebar.querySelectorAll(
            '.menu-link'
        );


    menuLinks.forEach(function (link) {

        const href =
            link.getAttribute('href');

        if (!href) {
            return;
        }


        /*
         * ไม่แก้สถานะของเมนูที่ PHP
         * กำหนด active มาแล้ว
         */

        if (
            link.classList.contains('active')
        ) {
            return;
        }


        try {

            const url =
                new URL(
                    href,
                    window.location.href
                );

            if (
                url.pathname === currentPath
            ) {

                link.classList.add('active');

            }

        } catch (error) {

            console.warn(
                'Sidebar URL error:',
                error
            );

        }

    });

});