(function($) {
    "use strict";
    window.azd = $.extend({}, window.azd);
    window.azd.map_init = function() {
        if ($('#deals-map').length) {
            window.azd = $.extend({}, {
                mapStyles: [{"featureType": "administrative.locality", "elementType": "all", "stylers": [{"hue": "#2c2e33"}, {"saturation": 7}, {"lightness": 19}, {"visibility": "on"}]}, {"featureType": "landscape", "elementType": "all", "stylers": [{"hue": "#ffffff"}, {"saturation": -100}, {"lightness": 100}, {"visibility": "simplified"}]}, {"featureType": "poi", "elementType": "all", "stylers": [{"hue": "#ffffff"}, {"saturation": -100}, {"lightness": 100}, {"visibility": "off"}]}, {"featureType": "road", "elementType": "geometry", "stylers": [{"hue": "#bbc0c4"}, {"saturation": -93}, {"lightness": 31}, {"visibility": "simplified"}]}, {"featureType": "road", "elementType": "labels", "stylers": [{"hue": "#bbc0c4"}, {"saturation": -93}, {"lightness": 31}, {"visibility": "on"}]}, {"featureType": "road.arterial", "elementType": "labels", "stylers": [{"hue": "#bbc0c4"}, {"saturation": -93}, {"lightness": -2}, {"visibility": "simplified"}]}, {"featureType": "road.local", "elementType": "geometry", "stylers": [{"hue": "#e9ebed"}, {"saturation": -90}, {"lightness": -8}, {"visibility": "simplified"}]}, {"featureType": "transit", "elementType": "all", "stylers": [{"hue": "#e9ebed"}, {"saturation": 10}, {"lightness": 69}, {"visibility": "on"}]}, {"featureType": "water", "elementType": "all", "stylers": [{"hue": "#e9ebed"}, {"saturation": -78}, {"lightness": 67}, {"visibility": "simplified"}]}],
                clusterStyles: [{url: azd.directory + '/images/cluster.png', height: 42, width: 42, textColor: "#ffffff"}],
                markerContent: '<div class="map-marker">' +
                        '<div class="icon">' +
                        '<img class="marker" src="' + azd.directory + '/images/marker.png">' +
                        '<img class="cat" src="{{product_cat}}">' +
                        '</div>' +
                        '</div>',
                infoboxOptions: {
                    disableAutoPan: false,
                    pixelOffset: new google.maps.Size(-120, 0),
                    zIndex: null,
                    alignBottom: true,
                    boxClass: "infobox-wrapper",
                    enableEventPropagation: true,
                    closeBoxMargin: "0px 0px -8px 0px",
                    closeBoxURL: azd.directory + "/images/close-btn.png",
                    infoBoxClearance: new google.maps.Size(1, 1)
                },
                infoboxTemplate: '<div class="entry azd-gmap-deal">' +
                        '<div class="entry-thumbnail">' +
                        '<img class="image" src="" data-src="{{image}}" alt="">' +
                        '{{thumbnail}}</div>' +
                        '<div class="entry-data">' +
                        '<div class="entry-header">' +
                        '<div class="entry-extra">{{extra}}</div>' +
                        '<div class="entry-title"><a href="{{url}}">{{title}}</a></div>' +
                        '<div class="entry-meta">{{meta}}</div>' +
                        '</div>' +
                        '<div class="entry-content">{{description}}</div>' +
                        '<div class="entry-footer">{{footer}}</div>' +
                        '{{data}}</div>' +
                        '</div>'
            }, window.azd);
            Mustache.parse(azd.markerContent);
            Mustache.parse(azd.infoboxTemplate);

            $('#deals-map').each(function() {
                var deals = $(this).data('deals');

                function multiChoice(mc) {
                    var cluster = mc.clusters_;
                    if (cluster.length == 1 && cluster[0].markers_.length > 1) {
                        return false;
                    }
                    return true;
                }
                var map = new google.maps.Map(this, {
                    styles: azd.mapStyles,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });
                var bounds = new google.maps.LatLngBounds();
                var markers = [];
                var activeMarker = false;
                var lastClicked = false;
                for (var k = 0; k < deals.length; k++) {
                    var locations = deals[k].deal_markers[0].split(/[^,][\s]+/);
                    for (var j = 0; j < locations.length; j++) {
                        var boxText = document.createElement("div");
                        var location = new google.maps.LatLng(parseFloat(locations[j].split(",")[0]), parseFloat(locations[j].split(",")[1]));
                        bounds.extend(location);

                        var markerContent = document.createElement('DIV');
                        markerContent.innerHTML = Mustache.render(azd.markerContent, deals[k]);

                        var marker = new RichMarker({
                            position: location,
                            map: map,
                            content: markerContent,
                            flat: true
                        });
                        markers.push(marker);
                        boxText.innerHTML = Mustache.render(azd.infoboxTemplate, deals[k]);
                        azd.infoboxOptions.content = boxText;
                        marker.infobox = new InfoBox(azd.infoboxOptions);
                        (function(marker) {
                            google.maps.event.addDomListener(marker.content, 'click', function(event) {
                                activeMarker = marker;
                                if (activeMarker != lastClicked) {
                                    for (var j = 0; j < markers.length; j++) {
                                        markers[j].infobox.close();
                                    }
                                    $(marker.infobox.content_).find('.image[src=""]').each(function() {
                                        $(this).attr('src', $(this).data('src')).on('load', function() {
                                            marker.infobox.open(map, marker);
                                        });
                                    });
                                    marker.infobox.open(map, marker);
                                }
                                lastClicked = marker;
                                if (event.stopPropagation)
                                    event.stopPropagation();
                                event.returnValue = false;
                            });
                            google.maps.event.addListener(marker.infobox, 'closeclick', function() {
                                lastClicked = 0;
                            });
                        })(marker);
                    }
                }
                google.maps.event.addListener(map, 'click', function(event) {
                    if (activeMarker != false) {
                        setTimeout(function() {
                            activeMarker.infobox.close();
                            lastClicked = 0;
                        }, 0);
                    }
                });
                map.fitBounds(bounds);
                if (markers.length == 1)
                    map.setZoom(14);
                var markerCluster = new MarkerClusterer(map, markers, {
                    styles: azd.clusterStyles,
                    maxZoom: 19
                });
                markerCluster.onClick = function(clickedClusterIcon, sameLatitude, sameLongitude) {
                    return multiChoice(sameLatitude, sameLongitude);
                };
            });
        }
    }

    $(document).ready(function() {

        function handle_images(frameArgs, callback) {
            var SM_Frame = wp.media(frameArgs);

            SM_Frame.on('select', function() {

                callback(SM_Frame.state().get('selection'));
                SM_Frame.close();
            });

            SM_Frame.open();
        }

        $(document).on('click', '.featured-image', function(e) {
            e.preventDefault();

            var frameArgs = {
                multiple: false,
                title: 'Select Featured Image'
            };

            handle_images(frameArgs, function(selection) {
                var model = selection.first();
                $('#deal_featured_image').val(model.id);
                var img = model.attributes.url;
                var ext = img.substring(img.lastIndexOf('.'));
                img = img.replace(ext, '-150x150' + ext);
                $('.featured-image-wrap').html('<img src="' + img + '" class="img-responsive"/>');
            });
        });


        /* DEAL IMAGES */
        $(document).on('click', '.deal-images', function(e) {
            e.preventDefault();

            $('.deal-images-wrap').sortable({
                revert: false,
                update: function(event, ui) {
                    update_deal_images();
                }
            });

            var frameArgs = {
                multiple: true,
                title: 'Select Deal Images'
            };

            handle_images(frameArgs, function(selection) {
                var images = selection.toJSON();
                if (images.length > 0) {
                    for (var i = 0; i < images.length; i++) {
                        var img = images[i].url;
                        var ext = img.substring(img.lastIndexOf('.'));
                        img = img.replace(ext, '-150x150' + ext);
                        $('.deal-images-wrap').append('<div class="deal-image-wrap" data-image_id=' + images[i].id + '><img src="' + img + '" class="img-responsive"/><a href="javascript:;" class="remove-deal-image"><i class="fa fa-close"></i></a></div>');
                    }
                }

                update_deal_images();
            });
        });

        $(document).on('click', '.remove-deal-image', function() {
            $(this).parents('.deal-image-wrap').remove();
            update_deal_images();
        });


        function update_deal_images() {
            var image_ids = [];
            $('.deal-image-wrap').each(function() {
                image_ids.push($(this).attr('data-image_id'));
            });

            $('#deal_images').val(image_ids.join(','));
        }

        /* ADD NEW MARKER */
        $(document).on('click', '.new-marker', function() {
            var $new_marker = $(this).next().clone();
            $new_marker.find('input').val('');
            $(this).after($new_marker);
            var mapElement = $new_marker.find('.map');
            $(mapElement).empty();
            select_position_map(mapElement.get(0), $(mapElement).parent().find('[name*="deal_marker_latitude"]'), $(mapElement).parent().find('[name*="deal_marker_longitude"]'), $(mapElement).parent().find('.map-search'), null, 13, true, true);
        });

        $(document).on('click', '.remove-marker', function() {
            if ($('.marker-wrap').length > 1) {
                $(this).parents('.marker-wrap').remove();
            }
            else {
                $(this).parents('.marker-wrap').find('input').val('');
            }
        });

        /* ADD NEW VARIATION */
        $(document).on('click', '.new-variation', function() {
            var $new_variation = $(this).next().clone();
            $new_variation.find('input').val('');
            $(this).after($new_variation);
        });

        $(document).on('click', '.remove-variation', function() {
            if ($('.variation-wrap').length > 1) {
                $(this).parents('.variation-wrap').remove();
            }
            else {
                $(this).parents('.variation-wrap').find('input').val('');
            }
        });


        /* DATES RANGE */
        function start_date_time_pickers() {
            if ($('#deal_start').length > 0) {
                $('#deal_start').datetimepicker({
                    format: 'Y-m-d',
                    onShow: function(ct) {
                        var start = $('#deal_expire').val();
                        var maxDate = false;
                        var minDate = false;
                        var range = $('#deal_start').data('range');
                        if (start !== '') {
                            var date = new Date(start);
                            date.setDate(date.getDate() - 1);
                            maxDate = date.getFullYear() + '/' + (date.getMonth() + 1) + '/' + date.getDate();
                            if (range !== '') {
                                date.setDate(date.getDate() - range);
                                minDate = date.getFullYear() + '/' + (date.getMonth() + 1) + '/' + date.getDate();
                            }
                        }
                        this.setOptions({
                            maxDate: maxDate,
                            minDate: minDate
                        });
                    },
                    timepicker: false
                });

                $('#deal_expire').datetimepicker({
                    format: 'Y-m-d',
                    onShow: function(ct) {
                        var start = $('#deal_start').val();
                        var maxDate = false;
                        var minDate = false;
                        var range = $('#deal_expire').data('range');
                        if (start !== '') {
                            var date = new Date(start);
                            date.setDate(date.getDate() + 1);
                            minDate = date.getFullYear() + '/' + (date.getMonth() + 1) + '/' + date.getDate();
                            if (range !== '') {
                                date.setDate(date.getDate() + range);
                                maxDate = date.getFullYear() + '/' + (date.getMonth() + 1) + '/' + date.getDate();
                            }
                        }

                        this.setOptions({
                            maxDate: maxDate,
                            minDate: minDate
                        });
                    },
                    timepicker: false
                });
            }
        }
        start_date_time_pickers();

        $.fn.hasAttr = function(name) {
            return this.attr(name) !== undefined;
        };
        function validate_form($container) {
            var valid = true;
            $container.find('small.error').remove();
            $container.find('select, input, textarea').each(function() {
                var $$this = $(this);
                $$this.removeClass('error')
                if ($$this.hasAttr('data-validation') && ($$this.is(':visible') || ($$this.attr('type') == 'hidden' && $$this.parents('.input-group').is(':visible')))) {
                    var validations = $$this.data('validation').split('|');
                    for (var i = 0; i < validations.length; i++) {
                        switch (validations[i]) {
                            case 'length_conditional' :
                                if ($$this.val() !== '') {
                                    var num = parseInt($($$this.data('field_number_val')).val());
                                    if ($$this.val().split(/\r*\n/).length != num) {
                                        valid = false;
                                    }
                                }
                                break;
                            case 'conditional' :
                                if ($$this.val() == '' && $('#' + $$this.data('conditional-field')).val() == '') {
                                    valid = false;
                                }
                                break;
                            case 'required' :
                                if ($$this.val() == '') {
                                    valid = false;
                                }
                                break;
                            case 'number' :
                                if (isNaN(parseInt($$this.val()))) {
                                    valid = false;
                                }
                                break;
                            case 'email' :
                                if (!/\S+@\S+\.\S+/.test($$this.val())) {
                                    valid = false;
                                }
                                break;
                            case 'match' :
                                if ($$this.val() !== $('input[name="' + $$this.data('match') + '"]').val()) {
                                    valid = false;
                                }
                                break;
                            case 'checked' :
                                if (!$$this.prop('checked')) {
                                    valid = false;
                                }
                                break;
                        }
                    }
                    if (!valid) {
                        if ($$this.attr('type') == 'checkbox') {
                            $$this.parent().before('<small class="no-margin error">' + $$this.data('error') + '</small><br />');
                        }
                        else {
                            $$this.before('<small class="error">' + $$this.data('error') + '</small>');
                        }
                    }
                }
            });
            if ($container.find('#deal_description').length > 0) {
                var $desc_label = $('label[for="deal_description"]');
                $desc_label.parent().find('.error').remove();
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('deal_description')) {
                    var tiny = tinyMCE.get('deal_description').getContent();
                    var description = $('#deal_description').val(tiny);
                }
                else {
                    var tiny = $('#deal_description').val();
                }
                if (tiny == '') {
                    valid = false;
                    $desc_label.after('<small class="error">' + $desc_label.data('error') + '</small>');
                }
            }
            return valid;
        }
        if ($('#deal-wizard').length && 'tabs' in $.fn) {
            $('#deal-wizard').tabs({
                activate: function(event, ui) {
                    if (ui.newTab.is(':first-child')) {
                        $('#deal-wizard .deal-prev').hide();
                    } else {
                        $('#deal-wizard .deal-prev').show();
                    }
                    if (ui.newTab.is(':last-child')) {
                        $('#deal-wizard .deal-submit').show();
                        $('#deal-wizard .deal-next').hide();
                    } else {
                        $('#deal-wizard .deal-next').show();
                    }
                    $('.marker-wrap .map').each(function() {
                        google.maps.event.trigger($(this).data('map'), 'resize');
                    });
                },
                beforeActivate: function(event, ui) {
                    if (!validate_form(ui.oldPanel)) {
                        event.preventDefault();
                    } else {
                        if (!$(ui.newPanel).is($(ui.oldTab).next().find('a').attr('href')) && !$(ui.newPanel).is($(ui.oldTab).prev().find('a').attr('href'))) {
                            event.preventDefault();
                        }
                    }
                }
            });
            $('#deal-wizard .deal-prev').click(function() {
                $('#deal-wizard .ui-tabs-nav .ui-tabs-active').prev().find('a').click();
                $('html, body').animate({
                    scrollTop: $("#deal-wizard").offset().top - 100
                }, 500);
                return false;
            });
            $('#deal-wizard .deal-next').click(function() {
                $('#deal-wizard .ui-tabs-nav .ui-tabs-active').next().find('a').click();
                $('html, body').animate({
                    scrollTop: $("#deal-wizard").offset().top - 100
                }, 500);
                return false;
            });
            $('#deal-wizard .deal-prev').hide();
            $('#deal-wizard .deal-submit').hide();
        }

        /* GOOGLE MAPS */

        $('.marker-wrap .map').each(function() {
            select_position_map(this, $(this).parent().find('[name*="deal_marker_latitude"]'), $(this).parent().find('[name*="deal_marker_longitude"]'), $(this).parent().find('.map-search'), null, 13, true, true);
        });
        if ($('.marker-wrap .map').length) {
            var initialLocation = null;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    initialLocation = new google.maps.LatLng(parseFloat(position.coords.latitude), parseFloat(position.coords.longitude));
                });
            }
        }
        function select_position_map(mapElement, latElement, lngElement, searchElement, addressElement, zoom, draggableMarker, scrollwheel) {
            var mapOptions = {
                zoom: zoom,
                disableDefaultUI: true,
                scrollwheel: scrollwheel,
                panControl: false,
                zoomControl: false,
                draggable: true
            };
            var map = new google.maps.Map(mapElement, mapOptions);
            $(mapElement).data('map', map);

            if (initialLocation == null) {
                initialLocation = new google.maps.LatLng(0, 0);
            }
            $(latElement).val(initialLocation.lat());
            $(lngElement).val(initialLocation.lng());

            map.setCenter(initialLocation);


            var autocomplete = new google.maps.places.Autocomplete(searchElement[0]);
            autocomplete.bindTo('bounds', map);

            google.maps.event.addListener(autocomplete, 'place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    return;
                }

                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }

                marker.setPosition(place.geometry.location);

                $(latElement).val(place.geometry.location.lat());
                $(lngElement).val(place.geometry.location.lng());
            });

            $(searchElement).keypress(function(event) {
                if (13 === event.keyCode) {
                    event.preventDefault();
                }
            });

            google.maps.event.addListenerOnce(map, 'tilesloaded', function() {
                $(mapElement).addClass('idle');
                map.setCenter(initialLocation);
            });
            // Create marker on the map
            var marker = new google.maps.Marker({
                position: initialLocation,
                map: map,
                draggable: draggableMarker,
                flat: true
            });
            //click on map to change marker position
            google.maps.event.addListener(map, 'click', function(e) {
                if (draggableMarker) {
                    marker.setPosition(e.latLng);
                }
            });
            google.maps.event.addListener(marker, 'position_changed', function() {
                var location = marker.getPosition();
                var jsonPath = 'http://maps.googleapis.com/maps/api/geocode/json?latlng=' + location.lat() + ',' + location.lng() + '&sensor=true';
                $.getJSON(jsonPath)
                        .done(function(json) {
                            if (json.results.length) {
                                $(addressElement).val(json.results[0].formatted_address);
                            }
                        })
                        .fail(function(jqxhr, textStatus, error) {
                            console.log(error);
                        });
                $(latElement).val(location.lat());
                $(lngElement).val(location.lng());
            });
        }

        var $map = $('#deal-map');
        if ($map.length > 0) {
            var markers = $map.data('markers');
            var markersArray = [];
            var bounds = new google.maps.LatLngBounds();
            var mapOptions = {
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            var map = new google.maps.Map(document.getElementById("deal-map"), mapOptions);
            var location;
            if (markers.length > 0) {
                for (var i = 0; i < markers.length; i++) {
                    location = new google.maps.LatLng(parseFloat(markers[i].latitude), parseFloat(markers[i].longitude));
                    bounds.extend(location);
                    var marker = new google.maps.Marker({
                        position: location,
                        map: map,
                    });
                }
                map.fitBounds(bounds);
                if (markers.length == 1)
                    map.setZoom(14);
            } else {
                $('.map_tab').remove();
            }
            $('.map_tab a').click(function() {
                setTimeout(function() {
                    google.maps.event.trigger(map, 'resize');
                    map.fitBounds(bounds);
                    if (markers.length == 1)
                        map.setZoom(14);
                }, 0);
            });
        }
        if ('google' in window) {
            azd.map_init();
        }
    });
})(jQuery);

