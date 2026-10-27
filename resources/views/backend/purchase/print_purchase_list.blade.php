@extends('admin.admin_master')
@section('title') {{'Print purchase'}} @endsection
@section('admin')


 <div class="page-content">
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    <h4 class="mb-sm-0">Print | All Purchase</h4>

                                     

                                </div>
                            </div>
                        </div>
                        <!-- end page title -->
                        
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

    @can('purchase-create')
        <a href="{{ route('purchases.create') }}" class="btn btn-dark btn-rounded waves-effect waves-light" style="float:right;"><i class="fas fa-plus-circle"></i> Add Purchase </a>
    @endcan
    <br>  <br>               

                    <h4 class="card-title">Purchase | All Data </h4>
                    

                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Date </th>
                            <th>Purchase No </th>
                            <th>Supplier Name</th>   
                            <th>Amount (Gh¢)</th>
                             <th>Action</th>
                            
                        </thead>


                        <tbody>
                        	 
                        	@foreach($allData as $key => $item)
            <tr>
                <td> {{ $key+1}} </td>
                <td> {{ date('d-m-Y',strtotime($item->date))  }} </td> 
                <td> {{ $item->purchase_no }} </td>
                <td> {{ $item['purchase_payment']['supplier']['name'] }} </td> 
                <td> {{ $item['purchase_payment']['total_amount'] }} </td>

                <td>
                    @can('purchase-print')
                        <a href="{{ route('print.purchase',$item->id) }}" class="btn btn-danger sm" title="Print purchase" >  <i class="fa fa-print"></i> </a>
                    @endcan
                </td>
               
            </tr>
                        @endforeach
                        
                        </tbody>
                    </table>
        
                                    </div>
                                </div>
                            </div> <!-- end col -->
                        </div> <!-- end row -->
        
                     
                        
                    </div> <!-- container-fluid -->
                </div>
 

@endsection