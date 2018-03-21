/*global $*/

import Point from 'ol/geom/point';
import Proj from 'ol/proj';

import {
    fnAPIUpdate,
    fnAPIStatistics
} from './api.js';

let fnEnableEdit = function () {
    let id = window.app.edit.id;

    window.app.map.once('click', fnMapOnClick);

    window.app.source.forEachFeature(function(feature) {
        feature.set('active', false);
    });
    window.app.source.getFeatureById(id).set('active', true);

    $('#results tr[data-id=' + id + ']').addClass('active');

    window.app.map.addOverlay(window.app.tooltip);
};

let fnDisableEdit = function () {
    let id = window.app.edit.id;

    $('#results tr[data-id=' + id + '] input[name=edit]').prop('checked', false);

    window.app.map.un('click', fnMapOnClick);

    window.app.source.forEachFeature(function(feature) {
        feature.set('active', null);
    });

    $('#results tr.active').removeClass('active');

    window.app.map.removeOverlay(window.app.tooltip);

    window.app.edit = null;
};

let fnMapOnClick = function (event) {
    let id = window.app.edit.id;
    let lnglat = Proj.toLonLat(event.coordinate);

    fnAPIUpdate(id, {
        longitude: lnglat[0],
        latitude: lnglat[1],
    }).
        then(function () {
            let id = window.app.edit.id;
            let geometry = window.app.source.getFeatureById(id).getGeometry();

            fnDisableEdit();

            window.app.source.getFeatureById(id).setProperties({color: 'green'});

            if (geometry !== null) {
                geometry.setCoordinates(event.coordinate);
            } else {
                window.app.source.getFeatureById(id).setGeometry(
                    new Point(event.coordinate)
                );
            }

            $('#results tr[data-id=' + id + ']').removeClass('table-warning table-danger').addClass('table-success');
            $('#results tr[data-id=' + id + ']').find('td:eq(2)').html('<i class="fas fa-user fa-fw text-success"></i>');

            return fnAPIStatistics(window.app.data.group);
        }).
        then(function (response) {
            $('.progress-bar.bg-success').css('width', response.green_pct + '%').prop('aria-valuenow', response.green_pct).text(Math.round(response.green_pct) + '%');
            $('.progress-bar.bg-warning').css('width', response.orange_pct + '%').prop('aria-valuenow', response.orange_pct).text(Math.round(response.orange_pct) + '%');
            $('.progress-bar.bg-danger').css('width', response.red_pct + '%').prop('aria-valuenow', response.red_pct).text(Math.round(response.red_pct) + '%');
        });
};

export default function initEdit () {
    $('#results > tbody > tr').on('click', function() {
        let id = $(this).data('id');
        let geometry = window.app.source.getFeatureById(id).getGeometry();
        let zoom = window.app.map.getView().getZoom();

        if (typeof window.app.edit !== 'undefined' && window.app.edit !== null) {
            fnDisableEdit();
        }

        if (geometry !== null) {
            window.app.map.getView().animate({
                zoom: (zoom < 18 ? 18 : zoom),
                center: geometry.getCoordinates()
            });
        }
    });

    $('input[name=edit]').on('change', function() {
        if (typeof window.app.edit !== 'undefined' && window.app.edit !== null) {
            fnDisableEdit();
        }

        window.app.edit = {
            id: $(this).val()
        };

        fnEnableEdit();
    });

}
