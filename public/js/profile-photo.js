/*
 * Cadrage de la photo de profil, et de la photo unique d'une annonce.
 *
 * Par défaut (CSS), la photo s'affiche en entier dans son cadre : un logo
 * allongé n'est jamais coupé. Quand la photo a presque le format du cadre,
 * elle le remplit, au prix d'une coupe d'au plus 20 % sur les bords : un
 * portrait ordinaire n'a ainsi pas de bandes blanches sur les côtés.
 *
 * Avec data-photo-max-upscale="1.5", une petite photo n'est jamais agrandie
 * au-delà de ce facteur : elle reste nette, centrée dans son cadre (classe
 * « is-limited » sur le parent), sur un fond tiré de la photo elle-même.
 */
(function () {
    'use strict';

    var MAX_CROP = 0.2;

    function fit(img) {
        var width = img.naturalWidth;
        var height = img.naturalHeight;
        var maxUpscale = parseFloat(img.getAttribute('data-photo-max-upscale')) || 0;
        // Avec une limite d'agrandissement, le cadre est le parent : la photo peut être plus petite que lui.
        var frame = maxUpscale > 0 && img.parentElement ? img.parentElement : img;
        var box = frame.getBoundingClientRect();
        if (!width || !height || !box.width || !box.height) {
            return;
        }

        var imageRatio = width / height;
        var frameRatio = box.width / box.height;
        var crop = imageRatio > frameRatio
            ? 1 - frameRatio / imageRatio
            : 1 - imageRatio / frameRatio;
        var cover = crop <= MAX_CROP;

        if (maxUpscale > 0) {
            var scale = cover
                ? Math.max(box.width / width, box.height / height)
                : Math.min(box.width / width, box.height / height);
            if (scale > maxUpscale) {
                img.style.width = Math.round(width * maxUpscale) + 'px';
                img.style.height = Math.round(height * maxUpscale) + 'px';
                img.style.objectFit = 'contain';
                frame.classList.add('is-limited');
                frame.style.setProperty('--photo-backdrop', 'url("' + String(img.currentSrc || img.src).replace(/"/g, '%22') + '")');
                return;
            }
            img.style.width = '';
            img.style.height = '';
            frame.classList.remove('is-limited');
        }

        img.style.objectFit = cover ? 'cover' : 'contain';
    }

    var images = Array.prototype.slice.call(document.querySelectorAll('img[data-photo-fit]'));

    images.forEach(function (img) {
        // Une nouvelle photo (changement de source) est recadrée à son tour.
        img.addEventListener('load', function () { fit(img); });
        if (img.complete) {
            fit(img);
        }
    });

    // Le cadre change de taille avec la fenêtre : seules les photos limitées sont recalculées.
    var limited = images.filter(function (img) { return img.hasAttribute('data-photo-max-upscale'); });
    if (limited.length) {
        var pending = null;
        window.addEventListener('resize', function () {
            window.clearTimeout(pending);
            pending = window.setTimeout(function () { limited.forEach(fit); }, 150);
        });
    }
})();
