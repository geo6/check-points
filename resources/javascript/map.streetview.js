/*global $*/

import ol from 'ol';
import Control from 'ol/control/control';
import Proj from 'ol/proj';

export default function StreetViewControl(opt_options) {
    let options = opt_options || {};

    let button = document.createElement('button');
    button.innerHTML = '<i class="fas fa-street-view"></i>';

    let this_ = this;
    let handleRotateNorth = function() {
        let center = Proj.toLonLat(this_.getMap().getView().getCenter());

        let iframe = document.createElement('iframe');
        $(iframe).attr({
            allowfullscreen: true,
            frameborder: 0,
            src: 'https://www.google.com/maps/embed/v1/streetview?location=' + center[1] + '%2C' + center[0] + '&key=AIzaSyCns3UD9m7n5-004fLXb6CLQJQt1idLJG8',
        }).css({
            border: 0
        }).addClass('embed-responsive-item');
        $('#modal-streetview .modal-body > .embed-responsive').html(iframe);
        $('#modal-streetview').modal('show');
    };

    button.addEventListener('click', handleRotateNorth, false);
    button.addEventListener('touchstart', handleRotateNorth, false);

    let element = document.createElement('div');
    element.className = 'rotate-north ol-unselectable ol-control';
    element.appendChild(button);

    Control.call(this, {
        element: element,
        target: options.target
    });
}
ol.inherits(StreetViewControl, Control);
