/*
 * Cadrage de la photo de profil, et de la photo unique d'une annonce.
 *
 * Par défaut (CSS), la photo s'affiche en entier dans son cadre : un logo
 * allongé n'est jamais coupé. Quand la photo a presque le format du cadre,
 * elle le remplit, au prix d'une coupe d'au plus 20 % sur les bords : un
 * portrait ordinaire n'a ainsi pas de bandes blanches sur les côtés.
 */
(function () {
    'use strict';

    var MAX_CROP = 0.2;

    function fit(img) {
        var width = img.naturalWidth;
        var height = img.naturalHeight;
        var box = img.getBoundingClientRect();
        if (!width || !height || !box.width || !box.height) {
            return;
        }

        var imageRatio = width / height;
        var frameRatio = box.width / box.height;
        var crop = imageRatio > frameRatio
            ? 1 - frameRatio / imageRatio
            : 1 - imageRatio / frameRatio;

        img.style.objectFit = crop <= MAX_CROP ? 'cover' : 'contain';
    }

    document.querySelectorAll('img[data-photo-fit]').forEach(function (img) {
        // Une nouvelle photo (changement de source) est recadrée à son tour.
        img.addEventListener('load', function () { fit(img); });
        if (img.complete) {
            fit(img);
        }
    });
})();
