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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

    <script src="http://code.jquery.com/jquery-1.12.0.min.js" type="application/javascript"></script>
    <script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>
    <script src="/extension/ocopendata/design/standard/javascript/jquery.dataTables.js"></script>
    <script src="/extension/ocopendata/design/standard/javascript/dataTables.bootstrap.js"></script>

    <script type="text/javascript" language="javascript" class="init">
        var searchEndpoint = "{'api/opendata/v2/datatable/search'|ezurl(no,full)}/*";
        {literal}
        $(document).ready(function () {
            $('#example').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    url: searchEndpoint,
                    serverSide: true
                },
                "columns": [
                    {"data": "metadata.id", "name": 'ID'},
                    {"data": "metadata.classIdentifier", "name": 'Class identifier'},
                    {"data": "metadata.name.ita-IT", "name": 'Name'}
                ]
            });
        });
        {/literal}
    </script>

</head>

<body>
<div class="container-fluid">
    <table id="example" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>Class identifier</th>
            <th>Name</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th>ID</th>
            <th>Class identifier</th>
            <th>Name</th>
        </tr>
        </tfoot>
    </table>
</div>
</body>
</html>