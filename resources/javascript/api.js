/*global $*/

let fnAPIUpdate = function (id, data) {
    return fetch('/app/check-points/api/object/' + id, {
        body: JSON.stringify(data),
        cache: 'no-cache',
        credentials: 'same-origin',
        headers: new Headers({
            'Content-Type': 'application/json'
        }),
        method: 'PUT',
        mode: 'same-origin'
    }).then(function (response) {
        return response.json();
    });
};

let fnAPIGet = function (id) {
    return fetch('/app/check-points/api/object/' + id, {
        cache: 'no-cache',
        credentials: 'same-origin',
        headers: new Headers({
            'Content-Type': 'application/json'
        }),
        method: 'GET',
        mode: 'same-origin'
    }).then(function (response) {
        return response.json();
    });
};

let fnAPIStatistics = function (data) {
    return fetch('/app/check-points/api/statistics?' + $.param(data), {
        cache: 'no-cache',
        credentials: 'same-origin',
        headers: new Headers({
            'Content-Type': 'application/json'
        }),
        method: 'GET',
        mode: 'same-origin'
    }).then(function (response) {
        return response.json();
    });
};

export {
    fnAPIGet,
    fnAPIUpdate,
    fnAPIStatistics,
};
