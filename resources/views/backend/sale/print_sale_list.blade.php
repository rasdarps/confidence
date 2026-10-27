@extends('layout.main')
@section('title') {{'Print Sale'}} @endsection
@section('admin')


 <div class="page-content">
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    <h4 class="mb-sm-0">Print Inovice All</h4>

                                     

                                </div>
                            </div>
                        </div>
                        <!-- end page title -->
                        
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

    <a href="{{ route('sales.index') }}" class="btn btn-dark btn-rounded waves-effect waves-light" style="float:right;"><i class="fas fa-plus-circle"> New Sales </i></a> <br>  <br>               

                    <h4 class="card-title">Inovice | Data </h4>
                    

                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Customer Name</th> 
                            <th>Sale No </th>
                            <th>Date </th>
                            <th>Description</th>  
                            <th>Amount (Gh¢)</th>
                            <th>Action</th>
                            
                        </thead>


                        <tbody>
                        	 
                        	@foreach($allData as $key => $item)
            <tr>
                <td> {{ $key+1}} </td>
                <td> {{ $item['payment']['customer']['name'] }} </td> 
                <td> {{ $item->sale_no }} </td> 
                <td> {{ date('d-m-Y',strtotime($item->date))  }} </td> 
                 
                  
                 <td>  {{ $item->description }} </td> 

                <td>  {{ $item['payment']['total_amount'] }} </td>

                <td>
                    @can('sale-print')
                        <a href="{{ route('print.sale',$item->id) }}" class="btn btn-danger sm" title="Print Sale" >  <i class="fa fa-print"></i> </a>
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
