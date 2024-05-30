<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <style>
        th {
            white-space: pre-wrap !important;
        }
        .card {
            border: 0px solid #000;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <section class="pcoded-main-container">
        <div class="pcoded-wrapper">
            <div class="pcoded-content">
                <div class="pcoded-inner-content">
                    <div class="main-body">
                        <div class="page-wrapper">                           
                            <div class='container mt-4 '>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="card">
                                            <div class="card-header bg-dark text-white mb-3">
                                                <h5>Student List</h5>
                                                <p>In this page, you can see information about Students.</p>
                                            </div>
                                            
                                            <div class="card-block">
                                                <div class="table-responsive">
                                                    <table id="dataTable" class="display table nowrap table-striped table-hover dataTable" style="width: 100%;" role="grid" aria-describedby="zero-configuration_info">
                                                        <thead>
                                                            <tr>
                                                                <th>Sr.No</th>
                                                                <th> Name</th>
                                                                <th>House</th>
                                                                <th>Gender</th>
                                                                <th>Mother Tongue</th>
                                                                <th>Birth Place</th>  
                                                                <th>Date Of Addimission</th>  
                                                                <th>GRN No</th>  
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($students as $index => $warehouse)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>{{ $warehouse->first_name }} {{ $warehouse->middle_name }} {{ $warehouse->last_name }}</td>
                                                                <td>{{ $warehouse->house }}</td>
                                                                <td>{{ $warehouse->gender }}</td>
                                                                <td>{{ $warehouse->mother_tongue }}</td>
                                                                <td>{{ $warehouse->birth_place }}</td>
                                                                <td>{{ $warehouse->date_of_admission }}</td>
                                                                <td>{{ $warehouse->grn_no }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script type="text/javascript">
        var table;

        $(function() {
            table = $('#dataTable').DataTable({
                dom: '<"top"lfB>rt<"bottom"ip><"clear">',
                buttons: [
                    {
                        extend: 'print',
                        title: '',
                        className: 'center-align theme-bg2 text-black f-12', 
                        customize: function (win) {
                            var currentDate = '{{ now()->toDateString() }}';
                            $(win.document.body).find('h1').append($('<span style="float: right; font-size: 15px; margin: 5px; padding: 35px 100px 0px 0px">Date of the Report:' + currentDate + '</span>'));

                            var footer = '<div style="position: absolute; bottom: 0; right: 0; text-align: right; margin: 10px; font-size: 20px;">Powered by ArthAgri</div>';
                            $(win.document.body).append(footer);

                            $(win.document.body).find('table').css({
                                'margin': 'auto',
                                'margin-top': '20px', 
                                'border-collapse': 'collapse',
                                'width': '80%', 
                                'border': '10px solid #000',
                                'box-shadow': '5px 5px 15px rgba(0,0,0,0.3)',
                            });
                        }
                    }
                ],
                lengthMenu: [[10, 25, 100, -1], [10, 25, 100, "All"]],
                pageLength: 10,
            });
        });
    </script>
</body>
</html>
