@props(['title'])

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>rictPOS | {{$title ?? ""}}</title>

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

    <style>
     body{
        background-image: url(images/posbg1.jpg);
        background-repeat:no-repeat;
        background-position:center center;
        background-attachment:fixed;
        background-size:cover;

     }

     .top-bar{
        background:#034141 !important; 

     }

     .card-header{
        background:#034141 !important;
        color:#fff !important;
     }

    .bg-gradient-primary {
    background-color: #6e042b !important;
    background-image: linear-gradient(180deg,#034141 10%,#034141 100%);
   }

   .btn-primary{
    background-color:#0F766E !important;
    border:none !important;
   }

   .btn-success{
    background-color:#0F766E !important;
    border:none !important;
   }

   i{
    font-size:25px !important;
   }

  /* .topbar {
    background: rgb(55, 78, 78) !important;
}*/

@media only screen and (max-width: 600px) {
    .top-buttons{
    display: none;
        }
           
        }


    </style>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center " href="{{route('home')}}">
                <div class="sidebar-brand-icon">
                    <img class="mt-10" src="{{asset('images/wayne.png')}}" alt="logo" width="70px" height="70px">
                   
                </div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="{{route('home')}}">
                    <i class="fas fa-fw fa-home"></i>
                    <span>Home</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Interface
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            @role('Admin')
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseZero"
                    aria-expanded="true" aria-controls="collapseZero">
                    <i class="fas fa-fw fa-building"></i>
                    <span>Company</span>
                </a>
                <div id="collapseZero" class="collapse" aria-labelledby="headingZero" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Company Setup:</h6>
                        <a class="collapse-item" href="#">Create</a>
                        <a class="collapse-item" href="#">View</a>
                    </div>
                </div>
            </li>
            @endrole

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseOne"
                    aria-expanded="true" aria-controls="collapseOne">
                    <i class="fas fa-fw fa-money-bill"></i>
                    <span>Transactions</span>
                </a>
                <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Entries:</h6>
                        <a class="collapse-item" href="#">Sales</a>
                        <a class="collapse-item" href="#">Purchases</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Manage Customers</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Customer Setup:</h6>
                        <a class="collapse-item" href="{{route('customer.index')}}">View</a>
                        <a class="collapse-item" href="{{route('customer.create')}}">Create</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

             <!-- Nav Item - Pages Collapse Menu -->
             <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseThree"
                    aria-expanded="true" aria-controls="collapseThree">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Manage Suppliers</span>
                </a>
                <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Supplier Setup:</h6>
                        <a class="collapse-item" href="{{route('supplier.index')}}">View</a>
                        <a class="collapse-item" href="{{route('supplier.create')}}">Create</a>
                    </div>
                </div>
            </li>

             <!-- Divider -->
             <hr class="sidebar-divider">

             <!-- Nav Item - Pages Collapse Menu -->
             <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFour"
                    aria-expanded="true" aria-controls="collapseFour">
                    <i class="fas fa-fw fa-truck"></i>
                    <span>Manage Products</span>
                </a>
                <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Category Setup:</h6> 
                        <a class="collapse-item" href="#">Create Category</a>
                        <a class="collapse-item" href="#">View Category</a>
                        <h6 class="collapse-header">Item Setup:</h6>
                        <a class="collapse-item" href="{{ route('products.index') }}">View Product</a> 
                        <a class="collapse-item" href="{{ route('products.create') }}">Create Product</a>    
                        <h6 class="collapse-header">Unit Setup:</h6> 
                        <a class="collapse-item" href="#">Create Unit</a>   
                        <a class="collapse-item" href="#">View Unit</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Nav Item - Pages Collapse Menu -->
            @role('Admin')
            <li class="nav-item">
               <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseEight"
                   aria-expanded="true" aria-controls="collapseEight">
                   <i class="fas fa-fw fa-cog"></i>
                   <span>Manage Units</span>
               </a>
               <div id="collapseEight" class="collapse" aria-labelledby="headingEight" data-parent="#accordionSidebar">
                   <div class="bg-white py-2 collapse-inner rounded">
                       <h6 class="collapse-header">Unit Setup:</h6>
                       <a class="collapse-item" href="{{ route('units.index') }}">View</a>
                       <a class="collapse-item" href="{{ route('units.create') }}">Create</a>
                   </div>
               </div>
           </li>
           @endrole

           

             <!-- Divider -->
             <hr class="sidebar-divider">

             <!-- Nav Item - Pages Collapse Menu -->
             @role('Admin')
             <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFive"
                    aria-expanded="true" aria-controls="collapseFive">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <div id="collapseFive" class="collapse" aria-labelledby="headingFive" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">User Setup:</h6>
                        <a class="collapse-item" href="{{ route('users.index') }}">View</a>
                        <a class="collapse-item" href="{{ route('users.create') }}">Create</a>
                    </div>
                </div>
            </li>
            @endrole

             <!-- Divider -->
             <hr class="sidebar-divider">

              <!-- Nav Item - Pages Collapse Menu -->
              @role('Admin')
              <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSix"
                    aria-expanded="true" aria-controls="collapseSix">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Manage Roles</span>
                </a>
                <div id="collapseSix" class="collapse" aria-labelledby="headingSix" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Role Setup:</h6>
                        <a class="collapse-item" href="{{ route('roles.index') }}">View</a>
                        <a class="collapse-item" href="{{ route('roles.create') }}">Create</a>
                    </div>
                </div>
            </li>
            @endrole

             <!-- Divider -->
             <hr class="sidebar-divider">

             <!-- Nav Item - Pages Collapse Menu -->
             @role('Admin')
             <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSeven"
                    aria-expanded="true" aria-controls="collapseSeven">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Reports</span>
                </a>
                <div id="collapseSeven" class="collapse" aria-labelledby="headingSeven" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Report Setup:</h6>
                        <a class="collapse-item" href="#">Sales Report</a>
                        <a class="collapse-item" href="#">Purchases Report</a>
                        <a class="collapse-item" href="#">Purchases Report</a>
                    </div>
                </div>
            </li>
           @endrole

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow top-bar">

                    <!-- Sidebar Toggle (Topbar) -->
                    <form class="form-inline">
                        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                            <i class="fa fa-bars"></i>
                        </button>
                    </form>

                    <!--<div class="top-buttons">
                        <button class="btn btn-primary">Sales</button>
                        <button class="btn btn-primary">Sales</button>
                        <button class="btn btn-primary">Sales</button>
                        <button class="btn btn-primary">Sales</button>
                        <button class="btn btn-primary">Sales</button>
                        <button class="btn btn-primary">Sales</button>
                    </div>-->
                    <div class="col-10">
                        <marquee behavior="" direction="left"><h3 style="color:#b7b9cc">WELCOME TO DarPOS V 1.0..... <span style="color:#b7b9cc">{{ Auth::user()->name }}</span> </h3></marquee>
                    </div>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        

                        <!-- Nav Item - Search Dropdown (Visible Only XS) 
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                             Dropdown - Messages 
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>-->

                        <div class="topbar-divider d-none d-sm-block"></div>
                        <!---------------------------------------------------------->
                        <!-- Authentication Links -->
                        @guest
                            <li><a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a></li>
                            <li><a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a></li>
                        @else
                           <!-- <li><a class="nav-link" href="{{ route('users.index') }}">Manage Users</a></li>
                            <li><a class="nav-link" href="{{ route('roles.index') }}">Manage Role</a></li>
                            <li><a class="nav-link" href="{{ route('products.index') }}">Manage Product</a></li>-->
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>


                                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>


                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest

                    </ul>

                </nav>
                <!-- End of Topbar -->
    <!-- Begin Page Content -->
    <div class="container-fluid">

      {{$slot}} <!--Helps to extend x-layout-->

    </div>
    <!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

<!-- Footer -->
<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>&copy; 2020-<?php echo date("Y");?> rictPOS <a href="https://rictconsult.com"><span style="color:orange;">Powered by RICT </span></a></span>
        </div>
    </div>
</footer>
<!-- End of Footer -->

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
<i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<!--<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
    </div>
    <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
    <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
        <a class="btn btn-primary" href="{{route('logout')}}">Logout</a>
    </div>
</div>
</div>
</div>-->

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
            paging: true,
           lengthChange: true,
           searching: true,
           ordering: true,
           info: true,
           autoWidth: false,
            dom: 'lBfrtip',

           responsive: true,
           buttons: [ "csv",
           {
            extend: 'copy',
            exportOptions: {
                columns:  [ 0, 1, 2, 3, 4, 5]
            }
        },
        {
            extend: 'excel',
            exportOptions: {
                columns:  [ 0, 1, 2, 3, 4, 5]
            }
        },
        {
            extend: 'pdf',
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5]
            }
        },

        'colvis' ],
         })
         .buttons()
          .container()
          .appendTo("#example_wrapper .col-md-6:eq(0)");
          

    });
</script>

</body>

</html>