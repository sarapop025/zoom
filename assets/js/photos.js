/**
 * ============================================================
 * assets/js/photos.js
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    /*
     * Photo image fallback
     */

    const images =
        document.querySelectorAll(
            '.photo-card img'
        );


    images.forEach(function (image) {

        image.addEventListener(
            'error',
            function () {

                image.style.display =
                    'none';


                const parent =
                    image.closest(
                        '.photo-cover'
                    );


                if (!parent) {
                    return;
                }


                const empty =
                    parent.querySelector(
                        '.photo-empty'
                    );


                if (empty) {

                    empty.style.display =
                        'flex';

                }

            }
        );

    });


    /*
     * Search form
     */

    const searchForm =
        document.querySelector(
            'form.photo-filter-form'
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