/*global $*/

import Proj from 'ol/proj';

export default function initNominatim () {
    $('#nominatim-query').on('change', function() {
        $('#nominatim-result').empty();
    });
    $('#nominatim > form').on('submit', function(event) {
        event.preventDefault();

        if ($.trim($('#nominatim-query').val()).length > 0) {
            fetch('//nominatim.openstreetmap.org/search?' +
                $.param({
                    'countrycodes': 'BE',
                    'format': 'json',
                    'q': $('#nominatim-query').val()
                })
            ).
                then(function (response) {
                    return response.json();
                }).
                then(function (json) {
                    if (json.length === 0) {
                        $('#nominatim-result').html('No result.');
                    } else {
                        var ul = document.createElement('ul');

                        json.forEach(function (item) {
                            var li = document.createElement('li');

                            if (typeof item.icon !== 'undefined') {
                                $(li).css('background-image', 'url(' + item.icon + ')');
                            }

                            $(li).
                                attr('title', item.class + ' : ' + item.type).
                                append(item.display_name).
                                data({
                                    'lat': parseFloat(item.lat),
                                    'lng': parseFloat(item.lon)
                                });

                            $(li).on('click', function (event) {
                                event.preventDefault();

                                var data = $(this).data();

                                if (item.class !== 'boundary') {
                                    window.app.map.getView().
                                        animate({
                                            'center': Proj.fromLonLat([data.lng, data.lat]),
                                            'duration': 0,
                                            'zoom': 18
                                        });
                                } else {
                                    window.app.map.getView().
                                        fit(Proj.transformExtent([
                                            parseFloat(item.boundingbox[2]),
                                            parseFloat(item.boundingbox[0]),
                                            parseFloat(item.boundingbox[3]),
                                            parseFloat(item.boundingbox[1])
                                        ], 'EPSG:4326', window.app.map.getView().getProjection()), {
                                            'minResolution': 18
                                        });
                                }
                            });

                            $(ul).append(li);
                        });

                        $('#nominatim-result').html(ul);
                    }
                });
        }
    });
}
