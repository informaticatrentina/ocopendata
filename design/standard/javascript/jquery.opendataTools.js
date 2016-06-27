;(function ($) {
    var defaults = {
        language: 'ita-IT',
        endpoint: {
            geo: '/opendata/api/geo/search/',
            search: '/opendata/api/content/search/',
            class: '/opendata/api/classes/'
        }
    };

    $.opendataTools = function (options) {

        var settings = $.extend({}, defaults, options);

        var map, markers, userMarker, markerBuilder, lastMapQuery;

        var setUserMarker = function(latlng, cb, context){
            var customIcon = L.MakiMarkers.icon({icon: "star", color: "#f00", size: "l"});
            if (typeof userMarker != 'object')
                userMarker = new L.marker(latlng,{icon: customIcon});
            userMarker.setLatLng(latlng);
            userMarker.addTo(map);
            map.addLayer(userMarker);
            if ($.isFunction(cb)) {
                cb.call(context, userMarker);
            }
            //$('#geolocation').html( latlng.lat+','+latlng.lng);
            //if ($.isFunction(markerBuilder) && lastMapQuery){
            //    geoJsonFind(lastMapQuery + ' geosort ['+latlng.lat+','+latlng.lng+'] limit 10', function (response) {
            //        if (response.features.length > 0) {
            //            markers.clearLayers();
            //            var geoJsonLayer = markerBuilder(response);
            //            markers.addLayer(geoJsonLayer);
            //            if (typeof userMarker == 'object') {
            //                var group = new L.FeatureGroup([markers, userMarker]);
            //                map.fitBounds(group.getBounds());
            //            } else {
            //                map.fitBounds(markers.getBounds());
            //            }
            //        }
            //    });
            //}
        };

        var loadMarkersInMap =  function(query, onLoad, geoJsonBuilder, context){
            if (map) {
                markers.clearLayers();
                lastMapQuery = query;
                geoJsonCacheAll(query, function (response) {
                    if (response.features.length > 0) {
                        var geoJsonLayer = $.isFunction(geoJsonBuilder) ? geoJsonBuilder(response) : markerBuilder(response);
                        markers.addLayer(geoJsonLayer);
                        if (typeof userMarker == 'object') {
                            var group = new L.FeatureGroup([markers, userMarker]);
                            map.fitBounds(group.getBounds());
                        } else {
                            map.fitBounds(markers.getBounds());
                        }
                        if ($.isFunction(onLoad)) {
                            onLoad.call(context, response);
                        }
                    }
                });
            }
        };

        var find = function (query, cb, context) {
            $.ajax({
                type: "GET",
                url: settings.endpoint.search,
                data: {q: encodeURIComponent(query)},
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function (data) {
                    cb.call(context, data);
                },
                error: function (data) {
                    cb.call(context, data);
                }
            });
        };

        var findOne = function (query, cb, context) {

            $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
                if (options.cache && window.sessionStorage !== undefined) {
                    var success = originalOptions.success || $.noop,
                        url = originalOptions.url;

                    options.cache = false; //remove jQuery cache as we have our own sessionStorage
                    options.beforeSend = function () {
                        if (sessionStorage.getItem(url)) {
                            success(JSON.parse(sessionStorage.getItem(url)));
                            return false;
                        }
                        return true;
                    };
                    options.success = function (data, textStatus) {
                        var responseData = JSON.stringify(data);
                        sessionStorage.setItem(url, responseData);
                        if ($.isFunction(success)) success(data); //call back to original ajax call
                    };
                }
            });

            $.ajax({
                type: "GET",
                url: settings.endpoint.search,
                data: {q: encodeURIComponent(query)},
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                cache: true,
                success: function (data) {
                    cb.call(context, data.searchHits[0]);
                },
                error: function (data) {
                    cb.call(context, data);
                }
            });
        };

        var findAll = function (query, cb, context) {
            var collectData = [];
            var getSubRequest = function (query) {
                find(query, function (data) {
                    parseSubResponse(data);
                })
            };
            var parseSubResponse = function (response) {
                $.each(response.searchHits, function () {
                    collectData.push(this);
                });
                if (response.nextPageQuery) {
                    getSubRequest(response.nextPageQuery);
                } else {
                    cb.call(context, collectData);
                }
            };
            getSubRequest(query);
        };

        var cacheAllItems = {
            items: [],
            instance: function (id, generator, cb, context) {
                for (var i = 0, len = this.items.length; i < len; i++) {
                    if (this.items[i].id === id) {
                        if ($.isFunction(cb)) {
                            cb.call(context, this.items[i].data);
                            return true;
                        }
                    }
                }
                var newItem = generator();
                this.items.push(newItem);
                newItem.load(cb, context);
            }
        };

        var contentClass = function (query, cb, context) {

            $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
                if (options.cache && window.sessionStorage !== undefined) {
                    var success = originalOptions.success || $.noop,
                        url = originalOptions.url;

                    options.cache = false; //remove jQuery cache as we have our own sessionStorage
                    options.beforeSend = function () {
                        if (sessionStorage.getItem(url)) {
                            success(sessionStorage.getItem(url));
                            return false;
                        }
                        return true;
                    };
                    options.success = function (data, textStatus) {
                        var responseData = JSON.stringify(data);
                        sessionStorage.setItem(url, responseData);
                        if ($.isFunction(success)) success(data); //call back to original ajax call
                    };
                }
            });

            $.ajax({
                type: "GET",
                url: settings.endpoint.class + encodeURIComponent(query),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                cache: true,
                success: function (data) {
                    cb.call(context, data);
                },
                error: function (data) {
                    cb.call(context, data);
                }
            });
        };

        var geoJsonFind = function (query, cb, context) {
            $.ajax({
                type: "GET",
                url: settings.endpoint.geo,
                data: {q: encodeURIComponent(query)},
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function (response) {
                    if ('error_message' in response) {
                        // @todo handle error
                        console.log(response);
                    } else {
                        if ($.isFunction(cb)) {
                            cb.call(context, response);
                        }
                    }
                },
                error: function (data) {
                    // @todo handle error
                    console.log(data);
                }
            });
        };

        var geoJsonFindAll = function (query, cb, context) {
            var features = [];
            var getSubRequest = function (query) {
                geoJsonFind(query, function (data) {
                    parseSubResponse(data);
                })
            };
            var parseSubResponse = function (response) {
                if (response.features.length > 0) {
                    $.each(response.features, function () {
                        features.push(this);
                    });
                }
                if (response.nextPageQuery) {
                    getSubRequest(response.nextPageQuery);
                } else {
                    var featureCollection = {
                        'type': 'FeatureCollection',
                        'features': features
                    };
                    cb.call(context, featureCollection);
                }
            };
            getSubRequest(query);
        };

        var cacheAll = function(query, cb, context){
            cacheAllItems.instance(
                query,
                function(){
                    return {
                        id: query,
                        query: query,
                        data: null,
                        load: function (cb, context) {
                            if (sessionStorage.getItem(this.query)) {
                                this.data = JSON.parse(sessionStorage.getItem(this.query));
                                if ($.isFunction(cb)) {
                                    cb.call(context, this.data);
                                }
                            } else {
                                var that = this;
                                findAll(this.query, function (response) {
                                    that.data = response;
                                    sessionStorage.setItem(that.query, JSON.stringify(response));
                                    if ($.isFunction(cb)) {
                                        cb.call(context, that.data);
                                    }
                                });
                            }
                        }
                    };
                },
                cb, context
            );
        };

        var geoJsonCacheAll = function(query, cb, context){
            cacheAllItems.instance(
                query,
                function(){
                    return {
                        id: query,
                        query: query,
                        data: null,
                        load: function (cb, context) {
                            if (sessionStorage.getItem(this.query)) {
                                this.data = JSON.parse(sessionStorage.getItem(this.query));
                                if ($.isFunction(cb)) {
                                    cb.call(context, this.data);
                                }
                            } else {
                                var that = this;
                                geoJsonFindAll(this.query, function (response) {
                                    that.data = response;
                                    sessionStorage.setItem(that.query, JSON.stringify(response));
                                    if ($.isFunction(cb)) {
                                        cb.call(context, that.data);
                                    }
                                });
                            }
                        }
                    };
                },
                cb, context
            );
        };

        return {

            settings: function (key, value) {
                if (value)
                    settings[key] = value;
                if (key)
                    return settings[key];
                return settings;
            },

            geoJsonFind: function (query, cb, context) {
                return geoJsonFind(query, cb, context);
            },

            geoJsonFindAll: function (query, cb, context) {
                return geoJsonFindAll(query, cb, context);
            },

            find: function (query, cb, context) {
                return find(query, cb, context);
            },

            findOne: function (query, cb, context) {
                return findOne(query, cb, context);
            },

            findAll: function (query, cb, context) {
                return findAll(query, cb, context);
            },

            cacheAll: function (query, cb, context) {
                cacheAll(query, cb, context);
            },

            geoJsonCacheAll: function (query, cb, context) {
                geoJsonCacheAll(query, cb, context);
            },

            contentClass: function (query, cb, context) {
                return contentClass(query, cb, context);
            },

            clearCache: function (startsWith) {
                startsWith = startsWith || settings.endpoint.class;
                var myLength = startsWith.length;

                Object.keys(sessionStorage)
                    .forEach(function (key) {
                        if (key.substring(0, myLength) == startsWith) {
                            sessionStorage.removeItem(key);
                        }
                    });
            },

            buildFacetsString: function (facets) {
                var facetStringList = [];
                $.each(facets, function () {
                    facetStringList.push(this.field + '|' + this.sort + '|' + this.limit);
                });
                return facetStringList.join(',');
            },

            buildFilterInput: function (facets, facet, datatable, cb, context) {
                for (var i = 0, len = facets.length; i < len; i++) {
                    var currentFilters = datatable.settings.builder.filters;
                    var facetDefinition = facets[i];

                    if (facetDefinition.field === facet.name && !facetDefinition.hidden) {

                        var select = $('<select id="' + facetDefinition.field + '" data-field="' + facetDefinition.field + '" data-placeholder="Seleziona" name="' + facetDefinition.field + '">');

                        if (facetDefinition.multiple) {
                            select.attr('multiple', 'multiple');
                        } else {
                            select.append($('<option value=""></option>'));
                        }

                        facetDefinition.data = facet.data;

                        $.each(facetDefinition.data, function (value, count) {
                            if (value.length > 0) {
                                var quotedValue = facetDefinition.field.search("extra_") > -1 ? encodeURIComponent('"' + value + '"') : value;
                                var option = $('<option value="' + quotedValue + '">' + value + ' (' + count + ')</option>');
                                if (currentFilters[facetDefinition.field]
                                    && currentFilters[facetDefinition.field].value
                                    && $.inArray(quotedValue, currentFilters[facetDefinition.field].value) > -1) {
                                    option.attr('selected', 'selected');
                                }
                                select.append(option);
                            }
                        });

                        var selectContainer = $('<div class="form-group" style="margin-bottom: 10px"></div>');
                        var label = $('<label for="' + facetDefinition.field + '">' + facetDefinition.name + '</label>');

                        selectContainer.append(label);
                        selectContainer.append(select);

                        select.show().chosen({width: '100%', allow_single_deselect: true}).on('change', function (e) {
                            var that = $(e.currentTarget);
                            var values = $(e.currentTarget).val();
                            if (typeof $(e.currentTarget).attr('multiple') == 'undefined' && values) {
                                values = [values]
                            }
                            if (values != null && values.length > 0) {
                                currentFilters[that.data('field')] = {
                                    'field': that.data('field'),
                                    'operator': 'contains',
                                    'value': values
                                };
                            } else {
                                currentFilters[that.data('field')] = null;
                            }
                            datatable.loadDataTable();
                        });

                        if ($.isFunction(cb)) {
                            cb.call(context, selectContainer);
                        }
                    }
                }
            },

            refreshFilterInput: function (facet, cb, context) {
                var select = $('[data-field="' + facet.name + '"]');
                var data = facet.data;
                select.find('option').attr('disabled', 'disabled');
                select.find('option[value=""]').removeAttr('disabled');
                $.each(data, function (value, count) {
                    if (value.length > 0) {
                        var quotedValue = facet.name.search("extra_") > -1 ? encodeURIComponent('"' + value + '"') : value;
                        var xpath = 'option[value="' + quotedValue + '"]';
                        var newText = value + ' (' + count + ')';
                        $(xpath, select).text(newText).removeAttr('disabled').show();
                    }
                });
                if ($.isFunction(cb)) {
                    cb.call(context, select);
                }
            },

            initMap: function(id, cb){
                markerBuilder = cb;
                map = L.map('map').setView([0, 0], 1);
                L.tileLayer('//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
                markers = L.markerClusterGroup();
                map.addLayer(markers);
                return map;
            },

            initUserMarker: function(cb, context){
                if (map) {
                    map.on('click', function (e) {
                        setUserMarker(e.latlng, cb, context);
                    });
                    $('.fa-map-marker').bind( 'click', function(){
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(function(position){
                                setUserMarker(new L.latLng(position.coords.latitude,position.coords.longitude), cb, context);
                            });
                        }
                    });
                }
            },

            setUserMarker: function(latlng, cb, context){
                setUserMarker(latlng, cb, context);
            },

            getMap: function(){
                return map;
            },

            loadMarkersInMap:  function(query, onLoad, geoJsonBuilder, context){
                loadMarkersInMap(query, onLoad, geoJsonBuilder, context);
            },

            refreshMap: function(){
                if (map) {
                    map.invalidateSize(false);
                    if (typeof userMarker == 'object') {
                        var group = new L.FeatureGroup([markers, userMarker]);
                        map.fitBounds(group.getBounds());
                    } else {
                        map.fitBounds(markers.getBounds());
                    }
                }
            }
        }
    }
}(jQuery));