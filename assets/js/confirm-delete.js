/**
 * ============================================================
 * assets/js/confirm-delete.js
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const deleteLinks =
        document.querySelectorAll(
            '[data-confirm-delete]'
        );


    deleteLinks.forEach(function (link) {

        link.addEventListener(
            'click',
            function (event) {

                const message =
                    link.getAttribute(
                        'data-confirm-delete'
                    ) ||
                    'ยืนยันการลบข้อมูลนี้หรือไม่?';


                const confirmed =
                    window.confirm(
                        message
                    );


                if (!confirmed) {

                    event.preventDefault();

                    return false;

                }

            }
        );

    });

});