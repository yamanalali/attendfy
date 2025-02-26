var areaSource, map, draw, jsonParse;
var polyCoords = [], temp = [];
const fitOptions = { duration: 1000 };
var htmlStr, geomType = "Polygon", start_drawing = false;;

var vectorSource = new ol.source.Vector();
var vectorLayer = new ol.layer.Vector({
    source: vectorSource
});

/**
 * Function menu dropdown
 */
$(function () {
    // Change the menu nav
    var url = baseUrl + "/areas/add"; // Change the url base on page
    if (typePage == 'update') {
        $('ul.nav-sidebar').find('a.nav-link').filter(function () {
            return this.href == url;
        }).addClass('active');

        $('ul.nav-sidebar').find('a.nav-link').filter(function () {
            return this.href == url;
        }).parent().parent().parent().addClass('menu-open');

        $('ul.nav-sidebar').find('a.nav-link').filter(function () {
            return this.href == url;
        }).parent().parent().parent().find('a.nav-item').addClass('active');
    }
});

/**
 * If this page is add, we check exist location of area
 */
if (typePage != "add") {
    // Get data from DB
    $.ajax({
        url: baseUrl + "/areas/showAllDataLocation/" + $('#id').val(),
        type: "get",
        success: function (response) {
            if (response.length > 0) {
                for (var i = 0; i < response.length; i++) {
                    // Convert lonLat to coordinate
                    var coordinate = ol.proj.fromLonLat([response[i].longt, response[i].lat]);
                    temp.push("[" + response[i].longt, response[i].lat + "]");
                    polyCoords.push("[" + coordinate + "]");
                }
            } else {
                checkLocation = "No Data";
                console.log("No Data");
            }
        },
    }).done(function () {
        jsonParse = JSON.parse('[[' + polyCoords.toString() + ']]');
        var jsonParseTemp = JSON.parse('[[' + temp.toString() + ']]');
        getPolygonCoords(jsonParseTemp);

        var polygonFeature = new ol.Feature(new ol.geom.Polygon(jsonParse));

        areaSource = new ol.source.Vector({
            features: [polygonFeature]
        });

        var areaVectorLayer = new ol.layer.Vector({
            source: areaSource,
            style: new ol.style.Style({
                fill: new ol.style.Fill({
                    color: 'rgba(255, 167, 66, 0.4)'
                }),
                stroke: new ol.style.Stroke({
                    color: '#ff7733',
                    width: 2
                }),
                image: new ol.style.Circle({
                    radius: 7,
                    fill: new ol.style.Fill({
                        color: '#ff7733'
                    })
                })
            })
        });

        map = new ol.Map({
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM()
                }),
                vectorLayer,
                areaVectorLayer],
            target: 'map-canvas',
            view: new ol.View({
                center: jsonParse[0][0],
                zoom: 5
            })
        });

        // Zoom in into the polygon
        map.getView().fit(polygonFeature.getGeometry(), fitOptions);
    });
} else {
    var features = new ol.Collection();
    var areaSource = new ol.source.Vector({
        features: features
    });

    var areaVectorLayer = new ol.layer.Vector({
        source: areaSource,
        style: new ol.style.Style({
            fill: new ol.style.Fill({
                color: 'rgba(255, 167, 66, 0.4)'
            }),
            stroke: new ol.style.Stroke({
                color: '#ff7733',
                width: 2
            }),
            image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({
                    color: '#ff7733'
                })
            })
        })
    });

    map = new ol.Map({
        layers: [
            new ol.layer.Tile({
                source: new ol.source.OSM()
            }),
            vectorLayer,
            areaVectorLayer],
        target: 'map-canvas',
        view: new ol.View({
            center: [247202.09824526682, 6601102.418249061],
            zoom: 5
        })
    });
}

/**
 * Function get coordinate to prepare the data to save to DB
 * @param {*} overlay 
 */
function getPolygonCoords(overlay) {
    var len = overlay[0].length;
    htmlStr = "";
    for (var i = 0; i < len; i++) {
        htmlStr += overlay[0][i] + "---";
    }
    document.getElementById('info').innerHTML = htmlStr;
}

/**
 * Check all input
 * @param {*} event 
 * @returns 
 */
function submitAndCheckFields(event) {
    if ($('form#areaId')[0].checkValidity()) {
        // Submit the form after all done
        $('form#areaId').trigger('submit');
    } else {
        event.preventDefault();
        alert("Name and Address cannot be blank");
        return false;
    }
}

/**
 * Function save coordinate to DB
 * @param {*} latLongt 
 * @param {*} idArea 
 */
function saveLocation(latLongt, idArea) {
    var splitCoordinate = latLongt.split(','),
        data;

    data = {
        'area_id': idArea,
        'lat': splitCoordinate[1],
        'longt': splitCoordinate[0],
        '_token': document.querySelector('meta[name="csrf-token"]').content,
    };

    $.ajax({
        url: baseUrl + "/areas/storeLocation",
        type: "post",
        data: data,
        success: function (response) {
            // Success
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Error
        }
    });
}

/**
 * Function delete coordinate before save it
 */
function deleteLocationTable() {
    $('.reload').css('display', 'block');
    $.ajax({
        url: baseUrl + "/areas/deleteLocationTable",
        type: "post",
        data: {
            '_token': document.querySelector('meta[name="csrf-token"]').content,
            'area_id': $('#id').val()
        },
        success: function (response) {
            // Success
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Error
        }
    });

    // setTimeout(function () {
    //     $('.reload').css('display', 'none');
    // }, 6000);
}

/**
 * Function draw the area or polygon
 */
function drawLoc() {
    draw = new ol.interaction.Draw({
        source: areaSource,
        type: geomType,
    });
    map.addInteraction(draw);

    // Start draw event
    draw.on('drawstart', function () {
        start_drawing = true;
    });

    // When user click stop draw
    $(document).on("click", "#stopDraw", function () {
        map.removeInteraction(draw);
    });

    // Drawend event
    draw.on('drawend', function (e) {
        start_drawing = false;
        var ft = e.feature.getGeometry().getCoordinates()[0];
        polyCoords = [];

        for (var i = 0; i < ft.length; i++) {
            // Convert to gps coordinate
            var lonLat = ol.proj.toLonLat(ft[i]);
            polyCoords.push("[" + lonLat + "]");
        };
        // Call function write coordinate in the <textarea>
        jsonParse = JSON.parse('[[' + polyCoords.toString() + ']]');
        getPolygonCoords(jsonParse);
        map.removeInteraction(draw);
    });
}

// Clear area
$(document).on("click", "#clear", function () {
    if (!start_drawing) {
        areaSource.clear();
    }
});

// Draw area polygon
$(document).on("click", "#draw", function () {
    areaSource.clear();
    drawLoc();
});

// Save button
$(document).on('click', '#saveLocation', function (event) {
    if (typeof htmlStr !== "undefined") {
        deleteLocationTable();

        setTimeout(function () {
            var getCoordinate = htmlStr.split("---");

            var i = 0;
            var myTimer = setInterval(function () {
                saveLocation(getCoordinate[i], $('#id').val());
                i++;

                if (getCoordinate.length == i) {
                    clearInterval(myTimer);

                    submitAndCheckFields(event);
                }

            }, 900);

        }, 1000);
    } else {
        submitAndCheckFields(event);
    }
});

/**
 * Function to search for a place and draw its polygon on the map
 */
function searchPlace() {
    var place = $('#name').val();
    var url = `https://nominatim.openstreetmap.org/search.php?q=${encodeURIComponent(place)}&format=jsonv2&limit=1&polygon_geojson=1`;

    $.ajax({
        url: url,
        type: "get",
        success: function (response) {
            if (response.length > 0) {
                // Extract the coordinates from the response
                var coordinates = response[0].geojson.coordinates[0];

                // Check if coordinates is a single coordinate or an array of coordinates
                if (!Array.isArray(coordinates[0]) && response[0].geojson.coordinates.length != 1) {
                    var boundingBox = response[0].boundingbox;
                    coordinates = [
                        [parseFloat(boundingBox[2]), parseFloat(boundingBox[0])],
                        [parseFloat(boundingBox[3]), parseFloat(boundingBox[0])],
                        [parseFloat(boundingBox[3]), parseFloat(boundingBox[1])],
                        [parseFloat(boundingBox[2]), parseFloat(boundingBox[1])],
                        [parseFloat(boundingBox[2]), parseFloat(boundingBox[0])]
                    ];
                }

                // Transform the coordinates to the map's projection
                var transformedCoords = coordinates.map(function (coord) {
                    return ol.proj.fromLonLat(coord);
                });

                // Clear the temp array
                temp.length = 0;

                // Populate the temp array with the coordinates
                for (var i = 0; i < coordinates.length; i++) {
                    temp.push("[" + coordinates[i][0] + "," + coordinates[i][1] + "]");
                }

                // Create a new polygon feature with the transformed coordinates
                var polygonFeature = new ol.Feature(new ol.geom.Polygon([transformedCoords]));

                // Clear the area source and add the new polygon feature
                areaSource.clear();
                areaSource.addFeature(polygonFeature);

                // Fit the map view to the new polygon feature
                map.getView().fit(polygonFeature.getGeometry(), fitOptions);

                // Fill the address input field with the display name from the response
                $('#address').val(response[0].display_name);

            } else {
                alert("No data found for the specified place.");
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert("Error occurred while searching for the place.");
        }
    }).done(function () {
        var jsonParseTemp = JSON.parse('[[' + temp.toString() + ']]');
        getPolygonCoords(jsonParseTemp);
    });
}

// Add event listener for the search button
$(document).on('click', '#searchPlace', function () {
    searchPlace();
});
