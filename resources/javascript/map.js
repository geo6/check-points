/*global $*/

import 'ol/ol.css';

import Control from 'ol/control';
import Attribution from 'ol/control/attribution';
import ScaleLine from 'ol/control/scaleline';
import GeoJSON from 'ol/format/geojson';
import Point from 'ol/geom/point';
import TileLayer from 'ol/layer/tile';
import VectorLayer from 'ol/layer/vector';
import Map from 'ol/map';
import Overlay from 'ol/overlay';
import Proj from 'ol/proj';
import OSMSource from 'ol/source/osm';
import TileWMSSource from 'ol/source/tilewms';
import VectorSource from 'ol/source/vector';
import XYZSource from 'ol/source/xyz';
import Circle from 'ol/style/circle';
import Fill from 'ol/style/fill';
import Stroke from 'ol/style/stroke';
import Style from 'ol/style/style';
import Text from 'ol/style/text';
import View from 'ol/view';

import {
    fnAPIUpdate,
    fnAPIStatistics
} from './api.js';

import RotateNorthControl from './map.streetview.js';

let fnCreateTooltip = function () {
    if (typeof window.app.tooltipElement !== 'undefined') {
        window.app.tooltipElement.parentNode.removeChild(window.app.tooltipElement);
    }
    window.app.tooltipElement = document.createElement('div');
    window.app.tooltipElement.className = 'ol-tooltip d-none';
    window.app.tooltip = new Overlay({
        element: window.app.tooltipElement,
        offset: [15, 0],
        positioning: 'center-left'
    });
    window.app.map.on('pointermove', function (event) {
        if (event.dragging) {
            return;
        }

        window.app.tooltipElement.innerHTML = 'Click in the map to move the object';
        window.app.tooltip.setPosition(event.coordinate);

        window.app.tooltipElement.classList.remove('d-none');
    });
    window.app.map.getViewport().addEventListener('mouseout', function () {
        window.app.tooltipElement.classList.add('d-none');
    });
};

let fnPreEdit = function (id) {
    window.app.source.forEachFeature(function(feature) {
        feature.set('active', false);
    });
    window.app.source.getFeatureById(id).set('active', true);

    window.app.map.addOverlay(window.app.tooltip);
};

let fnPostEdit = function (id) {
    let tr = $('input[name=edit]:checked').closest('tr');

    window.app.source.forEachFeature(function(feature) {
        feature.set('active', null);
    });
    window.app.source.getFeatureById(id).setProperties({color: 'green'});

    $('input[name=edit]:checked').prop('checked', false);
    $(tr).removeClass('table-warning table-danger').addClass('table-success');
    $(tr).find('td:eq(2)').html('<i class="fas fa-user fa-fw text-success"></i>');

    window.app.map.removeOverlay(window.app.tooltip);
};

export default function initMap() {
    window.app.source = new VectorSource({
        features: (new GeoJSON({
            featureProjection: 'EPSG:3857'
        })).readFeatures(window.app.data.json)
    });

    window.app.baselayers = [];
    if (typeof window.app.data.baselayers !== 'undefined') {
        for (let i = 0; i < window.app.data.baselayers.length; i++) {
            if (window.app.data.baselayers[i].type === 'tms') {
                window.app.baselayers.push(
                    new TileLayer({
                        source: new XYZSource({
                            url: window.app.data.baselayers[i].url,
                            attributions: window.app.data.baselayers[i].attributions
                        })
                    })
                );
            } else if (window.app.data.baselayers[i].type === 'wms') {
                window.app.baselayers.push(
                    new TileLayer({
                        source: new TileWMSSource({
                            url: window.app.data.baselayers[i].url,
                            attributions: window.app.data.baselayers[i].attributions,
                            params: {
                                LAYERS: window.app.data.baselayers[i].layers.join(',')
                            }
                        })
                    })
                );
            }
        }
    }
    window.app.baselayers.unshift(
        new TileLayer({
            source: new OSMSource({
                attributions: [OSMSource.ATTRIBUTION, 'Tiles courtesy of <a href="https://geo6.be/" target="_blank">GEO-6</a>'],
                url: 'https://tile.geo6.be/osmbe/{z}/{x}/{y}.png'
            })
        })
    );

    $('#baselayers').on('change', function () {
        let i = $(this).val();
        window.app.map.getLayers().setAt(0, window.app.baselayers[i]);
    });

    window.app.map = new Map({
        target: 'map',
        controls: Control.defaults({attribution: false}).extend([
            new Attribution({collapsible: false}),
            new ScaleLine(),
            new RotateNorthControl()
        ]),
        layers: [
            window.app.baselayers[0],
            new VectorLayer({
                source: window.app.source,
                style: function(feature) {
                    let fill = new Fill({
                        color: feature.getProperties().color
                    });
                    let stroke = new Stroke({
                        color: '#FFF',
                        width: 2
                    });
                    let image = new Circle({
                        fill: fill,
                        radius: 5,
                        stroke: stroke
                    });
                    if (feature.get('active') === false) {
                        image.setOpacity(0.33);
                    }

                    return new Style({
                        fill: fill,
                        image: image,
                        stroke: stroke,
                        text: new Text({
                            offsetY: -15,
                            stroke: stroke,
                            text: feature.getProperties().label_map
                        }),
                        zIndex: (feature.get('active') ? Infinity : 999)
                    });
                }
            })
        ],
        view: new View({
            center: [0, 0],
            zoom: 2
        })
    });

    let extent = window.app.source.getExtent();
    let min = Math.min.apply(null, extent);
    let max = Math.max.apply(null, extent);
    if (min !== -Infinity && max !== Infinity) {
        window.app.map.getView().fit(extent, {
            maxZoom: 18,
            padding: [10,10,10,10]
        });
    }

    fnCreateTooltip();

    $('#results > tbody > tr').on('click', function() {
        let id = $(this).data('id');
        let geometry = window.app.source.getFeatureById(id).getGeometry();
        let zoom = window.app.map.getView().getZoom();

        if (geometry !== null) {
            window.app.map.getView().animate({
                zoom: (zoom < 18 ? 18 : zoom),
                center: geometry.getCoordinates()
            });
        }
    });

    $('input[name=edit]').on('change', function() {
        let id = $(this).val();
        let geometry = window.app.source.getFeatureById(id).getGeometry();

        fnPreEdit(id);

        window.app.map.once('click', function (event) {
            let lnglat = Proj.toLonLat(event.coordinate);

            fnAPIUpdate(id, {
                longitude: lnglat[0],
                latitude: lnglat[1],
            }).
                then(function () {
                    return fnAPIStatistics(window.app.data.group);
                }).
                then(function (response) {
                    $('.progress-bar.bg-success').css('width', response.green_pct + '%').prop('aria-valuenow', response.green_pct).text(Math.round(response.green_pct) + '%');
                    $('.progress-bar.bg-warning').css('width', response.orange_pct + '%').prop('aria-valuenow', response.orange_pct).text(Math.round(response.orange_pct) + '%');
                    $('.progress-bar.bg-danger').css('width', response.red_pct + '%').prop('aria-valuenow', response.red_pct).text(Math.round(response.red_pct) + '%');
                });

            if (geometry !== null) {
                geometry.setCoordinates(event.coordinate);
            } else {
                window.app.source.getFeatureById(id).setGeometry(
                    new Point(event.coordinate)
                );
            }

            fnPostEdit(id);
        });
    });
}
