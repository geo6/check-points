/*global $*/

require('./fontawesome');

import initMap from './map.js';
import initNominatim from './nominatim.js';

import {
    fnAPIGet,
    fnAPIUpdate
} from './api.js';

window.app = window.app || {};
window.app.fn = {
    initMap: initMap,
    initNominatim: initNominatim
};

$(document).ready(function () {
    let height = $(window).height() - (
        $('body > nav.navbar').height() +
        parseInt($('body > nav.navbar').css('margin-bottom')) +
        $('body > div.container').height() +
        $('body > footer.footer').height() +
        parseInt($('body').css('margin-bottom'))
    );
    $('body > div.container-fluid').height(height);

    window.app.fn.initMap();
    window.app.fn.initNominatim();
});

$(window).on('resize', function () {
    let height = $(window).height() - (
        $('body > nav.navbar').height() +
        parseInt($('body > nav.navbar').css('margin-bottom')) +
        $('body > div.container').height() +
        $('body > footer.footer').height() +
        parseInt($('body').css('margin-bottom'))
    );
    $('body > div.container-fluid').height(height);
});

$('#modal-info').on('show.bs.modal', function (event) {
    let id = $(event.relatedTarget).data('id');

    $('#modal-info').data('id', id);

    $('#note-success').hide();

    fnAPIGet(id).
        then(function (response) {
            $('#modal-info table > tbody').empty();
            $('#note').val('');

            for (let key in response) {
                if (['the_geog','update','note'].includes(key) === false) {
                    let tr = document.createElement('tr');
                    let th = document.createElement('th');
                    let td = document.createElement('td');

                    $(th).text(key).appendTo(tr);
                    $(td).text(response[key]).appendTo(tr);
                    $(tr).appendTo('#modal-info table > tbody');
                }
            }

            $('#note').val(response.note);
        });
});

$('#modal-info form').on('submit', function (event) {
    event.preventDefault();

    let id = $('#modal-info').data('id');
    let note = $('#note').val();

    $('#note-success').hide();

    fnAPIUpdate(id, {
        note: note
    }).
        then(function (response) {
            if (response === 1) {
                if (note.length > 0) {
                    $('a[data-toggle=modal][data-id=' + id + '] > .text-muted').removeClass('text-muted').addClass('text-primary');
                } else {
                    $('a[data-toggle=modal][data-id=' + id + '] > .text-primary').removeClass('text-primary').addClass('text-muted');
                }

                $('#note-success').show();
            }
        });
});
