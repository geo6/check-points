/*global $*/

require('./fontawesome');

import initEdit from './edit.js';
import initMap from './map.js';
import initModalInfo from './modal.info.js';
import initNominatim from './nominatim.js';

window.app = window.app || {};
window.app.fn = {
    initEdit: initEdit,
    initMap: initMap,
    initModalInfo: initModalInfo,
    initNominatim: initNominatim,
    initHeight: function () {
        let height = $(window).height() - (
            $('body > nav.navbar').height() +
            parseInt($('body > nav.navbar').css('margin-bottom')) +
            $('body > div.container').height() +
            $('body > footer.footer').height() +
            parseInt($('body').css('margin-bottom'))
        );
        $('body > div.container-fluid').height(height);
    }
};
