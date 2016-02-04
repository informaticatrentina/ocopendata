<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Datatable</title>

    <link href="http://getbootstrap.com/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css"
          href="/extension/ocopendata/design/standard/stylesheets/dataTables.bootstrap.css">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

    <script src="http://code.jquery.com/jquery-1.12.0.min.js"
            type="application/javascript"></script>
    <script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>
    <script src="/extension/ocopendata/design/standard/javascript/jquery.dataTables.js"></script>
    <script src="/extension/ocopendata/design/standard/javascript/dataTables.bootstrap.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.1/moment.min.js"></script>

    <script type="text/javascript" language="javascript" class="init">
        {literal}

        (function ( $, window, document, undefined ) {
            "use strict";
            var pluginName = "ocopendatatable",
                defaults = {
                    "builder":{
                        "url": null,
                        "query": '/sort [published=>desc]',
                        "columns": [
                            {"data": "metadata.id", "name": 'id', "title": 'ID'},
                            {"data": "metadata.published", "name": 'published', "title": 'Published'},
                            {
                                "data": "metadata.classIdentifier",
                                "name": 'classIdentifier',
                                "title": 'Class'
                            },
                            {"data": "metadata.name.ita-IT", "name": 'name', "title": 'Name'}
                        ]
                    },
                    "table":{
                        "id": 'exemple',
                        "template": '<table class="table table-striped table-bordered" cellspacing="0" width="100%"></table>'
                    },
                    "datatable": {
                        "processing": true,
                        "serverSide": true,
                        "ajax": {
                            serverSide: true
                        },
                        "columnDefs": [
                            {
                                "render": function ( data, type, row, meta ) {
                                    var validDate = moment(data,moment.ISO_8601);
                                    if ( validDate.isValid() ){
                                        return '<span style="white-space:nowrap">'+validDate.format("d MMMM YYYY, hh:mm")+'</span>';
                                    }else{
                                        return data;
                                    }
                                },
                                "targets": '_all'
                            }
                        ]
                    }
                };

            // The actual plugin constructor
            function Ocpendatatable ( element, options ) {
                this.element = element;
                this.settings = $.extend( true, {}, defaults, options );
                this._defaults = defaults;
                this._name = pluginName;
                this.init();
            }

            $.extend(Ocpendatatable.prototype, {
                init: function () {
                    var that = this;
                    if (this.settings.builder.url != null) {
                        $.get(this.settings.builder.url, function (data) {
                            that.settings.datatable.ajax.url += that.settings.builder.query;
                            that.settings.datatable.columns = that.settings.builder.columns;
                            that.loadDataTable();
                        });
                    }else{
                        that.settings.datatable.ajax.url += that.settings.builder.query;
                        that.settings.datatable.columns = this.settings.builder.columns;
                        that.loadDataTable();
                    }

                },
                loadDataTable: function () {
                    $(this.settings.table.id).remove();
                    var table = $(this.settings.table.template).attr( 'id', this.settings.table.id );
                    $(this.element).append(table);
                    table.DataTable(this.settings.datatable);
                }
            });

            $.fn[ pluginName ] = function ( options ) {
                return this.each(function() {
                    if ( !$.data( this, "plugin_" + pluginName ) ) {
                        $.data( this, "plugin_" + pluginName, new Ocpendatatable ( this, options ) );
                    }
                });
            };

        })( jQuery, window, document );
        $(document).ready(function () {
            $('.container-fluid').ocopendatatable({
                "builder":{
                    url: "{/literal}{'/opendata/datatable/builder'|ezurl(no,full)}{literal}"
                },
                "datatable":{
                    "ajax": {
                        url: "{/literal}{'api/opendata/v2/datatable/search'|ezurl(no,full)}{literal}"
                    }
                }
            });
        });
        {/literal}
    </script>

</head>

<body>
<div class="container-fluid">
</div>
</body>
</html>