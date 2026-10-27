<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title></title>

    <!-- Local DataTables baseline -->
    <link href="{{ asset('vendor/datatables/css/buttons.dataTables-2.2.3.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables-1.13.6.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!--Offline Links-->
    <!-- Custom fonts for this template -->
    <link href="{{asset('fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
     <!-- Custom datatable styles for this page -->
    <link rel="stylesheet" type="text/css" href="{{asset('backend/datatables/Buttons-2.2.3/css/buttons.dataTables.min.css')}}"/>
    <link href="{{asset('backend/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="{{asset('css/sb-admin-2.min.css')}}" rel="stylesheet">

<script>
    setTimeout(function() {
        window.location = "{{'admin/login'}}";
    }, 2000);
</script>

<style>
    body {
        background-image:url(images/posbg1.jpg) !important;
        width:100%;
        /*background-image:url(images/school.jpg);*/
        background-repeat:no-repeat;
        background-position:center center;
        background-attachment:fixed;
        background-size:cover;
    }
    
    .jumbotron {
        /*background-image: linear-gradient(rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.9)) !important;*/
        background: transparent;
        background-size: cover;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        color: #fff!important;
        text-align: center;
    }

    a{
        text-decoration: none;
    }
    
    /*MEDIA QUERY*/
    
    @media (max-width: 992px) {
        .jumbotron {
            margin-top: 350px !important;
        }
    }
    
    @media (max-width: 768px) {}
    
    @media (max-width: 576px) {}
</style>
</head>

<body>
<div class="container" style="margin-top:150px;">
    <div class="jumbotron col-md-12">
        <div class="os-animation" data-os-animation="zoomIn" data-os-animation-delay="0.5s">
            <div class="col-md-8 mx-auto">
            <img src="{{asset('images/spinners.gif')}}" width="200px" /> <br><br>
                <h1 style="text-align:center;">Low Prices POS System.....</h1>
                <h5 style="text-align:center;">Powered by <a href="https://rictconsult.org">RICT Consult.....</a></h5>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="{{asset('jquery/jquery.min.js')}}"></script>
<script src="{{asset('bootstrap/js/bootstrap.bundle.min.js')}}"></script>

<!-- Core plugin JavaScript-->
<script src="{{asset('jquery-easing/jquery.easing.min.js')}}"></script>

<!-- Custom scripts for all pages-->
<script src="{{asset('js/sb-admin-2.min.js')}}"></script>

<!-- Page level plugins -->
<script src="{{asset('backend/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('backend/datatables/dataTables.bootstrap4.min.js')}}"></script>

 <!--Offline links-->
<script type="text/javascript" src="{{asset('backend/datatables/JSZip-2.5.0/jszip.min.js')}}"></script>
<script type="text/javascript" src="{{asset('backend/datatables/pdfmake-0.1.36/pdfmake.min.js')}}"></script>
<script type="text/javascript" src="{{asset('backend/datatables/pdfmake-0.1.36/vfs_fonts.js')}}"></script>
<script type="text/javascript" src="{{asset('backend/datatables/Buttons-2.2.3/js/dataTables.buttons.min.js')}}"></script>
<script type="text/javascript" src="{{asset('backend/datatables/Buttons-2.2.3/js/buttons.html5.min.js')}}"></script>
<script type="text/javascript" src="{{asset('backend/datatables/Buttons-2.2.3/js/buttons.print.min.js')}}"></script>

<!-- Page level custom scripts -->
<script src="{{asset('js/demo/datatables-demo.js')}}"></script>

<script>
    //calling datatable function
    $(document).ready( function () {
    $('#myTable').DataTable(
        {
        dom: 'Bfrtip',
        buttons: [
           'copy', 'excel', 'pdf', 'print'
        ]
    } );

    });
</script>

</body>

</html>