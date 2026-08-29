/**
 * ============================================================
 * assets/js/app.js
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    /*
     * Auto hide Bootstrap alerts
     */
    const alerts = document.querySelectorAll(
        '.alert.alert-dismissible'
    );

    alerts.forEach(function (alert) {

        setTimeout(function () {

            if (
                typeof bootstrap !== 'undefined'
            ) {

                const instance =
                    bootstrap.Alert.getOrCreateInstance(
                        alert
                    );

                instance.close();

            } else {

                alert.style.display = 'none';

            }

        }, 5000);

    });


    /*
     * Prevent double submit
     */
    const forms = document.querySelectorAll(
        'form[data-prevent-double-submit]'
    );

    forms.forEach(function (form) {

        form.addEventListener('submit', function () {

            const button =
                form.querySelector(
                    'button[type="submit"]'
                );

            if (!button) {
                return;
            }

            if (button.dataset.submitted === '1') {
                return;
            }

            button.dataset.submitted = '1';

            button.disabled = true;

        });

    });

});
