/**
 * ============================================================
 * assets/js/projects.js
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    /*
     * Project card
     */

    const cards =
        document.querySelectorAll(
            '.project-card'
        );


    cards.forEach(function (card) {

        /*
         * Image fallback
         */

        const image =
            card.querySelector(
                '.cover'
            );

        const emptyCover =
            card.querySelector(
                '.cover-empty'
            );


        if (image && emptyCover) {

            image.addEventListener(
                'error',
                function () {

                    image.style.display =
                        'none';

                    emptyCover.style.display =
                        'flex';

                }
            );

        }

    });


    /*
     * Search form
     */

    const searchForm =
        document.querySelector(
            'form.project-filter-form'
        );


    if (searchForm) {

        searchForm.addEventListener(
            'submit',
            function () {

                const input =
                    searchForm.querySelector(
                        'input[name="search"]'
                    );

                if (!input) {
                    return;
                }

                input.value =
                    input.value.trim();

            }
        );

    }

});