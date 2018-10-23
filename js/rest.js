"use strict";

function get(url, action, id, params) {

    return new Promise(function(success, error) {

        var xmlhttp = new XMLHttpRequest();

        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == XMLHttpRequest.DONE) {
                if (xmlhttp.status == 200) {

                    success(JSON.parse(xmlhttp.response));

                } else {
                    error(new Error("Request failed: " + request.statusText));
                }

            }
        };

        xmlhttp.open("GET", url + "?action=" + action + "&id=" + id + "&params=" + params, true);
        xmlhttp.send();

    });
}

function post(url, action, body) {

    return new Promise(function(success, error) {

        var xmlhttp = new XMLHttpRequest();

        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == XMLHttpRequest.DONE) {
                if (xmlhttp.status == 200) {

                    success(JSON.parse(xmlhttp.response));

                } else {
                    error(new Error("Request failed: " + request.statusText));
                }

            }
        };

        xmlhttp.open("POST", url + "?action=" + action, true);
        xmlhttp.setRequestHeader('Content-type', 'application/json; charset=utf-8');
        xmlhttp.send(body);

    });
}