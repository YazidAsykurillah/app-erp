@extends('layouts.app')

@section('page_title')
    Purchase Order Customer
@endsection

@section('page_header')
  <h1>
    Purchase Order Customer
    <small>Daftar Purchase Order Customer</small>
  </h1>
@endsection

@section('breadcrumb')
  <ol class="breadcrumb">
    <li><a href="{{ URL::to('home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    <li><a href="{{ URL::to('purchase-order-customer') }}"><i class="fa fa-bookmark-o"></i> PO Customer</a></li>
    <li class="active"><i></i> Index</li>
  </ol>
@endsection

@section('content')
  <div class="row">
    <div class="col-lg-12">
        <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">PO Customer</h3>
              
              <a href="{{ URL::to('purchase-order-customer/create')}}" class="btn btn-primary pull-right" title="Create new PO Customer">
                <i class="fa fa-plus"></i>&nbsp;Add New
              </a>
            </div><!-- /.box-header -->
            <div class="box-body">
              <div class="table-responsive">
                <table class="table table-bordered" id="table-purchase-order-customer">
                  <thead>
                    <tr>
                      <th style="width:5%;">#</th>
                      <th style="width:15%;">PO Number</th>
                      <th>Quotation</th>
                      <th>Sales</th>
                      <th style="width:20%;">Customer Name</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Received Date</th>
                      <th>Project</th>
                      <th style="width:10%;text-align:center;">Actions</th>
                    </tr>
                  </thead>
                  <thead id="searchColumn">
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                    </tr>
                  </thead>
                  
                  <tbody></tbody>
                  <tfoot>
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th style="text-align:right;"></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                    </tr>
                  </tfoot>
              </table>
            </div>
          </div><!-- /.box-body -->
          <div class="box-footer clearfix">
            <div id="button-table-tools" class=""></div>
          </div>
        </div><!-- /.box -->
    </div>
  </div>

  <!--Modal Delete Purchase Order-->
  <div class="modal fade" id="modal-delete-purchaseOrderCustomer" tabindex="-1" role="dialog" aria-labelledby="modal-delete-purchaseOrderCustomerLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
      {!! Form::open(['url'=>'deletePOCustomer', 'method'=>'post']) !!}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="modal-delete-purchaseOrderCustomerLabel">Confirmation</h4>
        </div>
        <div class="modal-body">
          You are going to delete <b id="po-customer-code-to-delete"></b>
          <br/>
          <p class="text text-danger">
            <i class="fa fa-info-circle"></i>&nbsp;This process can not be reverted
          </p>
          <input type="hidden" id="po_customer_id" name="po_customer_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      {!! Form::close() !!}
      </div>
    </div>
  </div>
<!--ENDModal Delete Purchase Order-->
@endsection

@section('additional_scripts')
  <script type="text/javascript">
    var tablePOCustomer =  $('#table-purchase-order-customer').DataTable({
      processing :true,
      serverSide : true,
      ajax : '{!! url('purchase-order-customer/dataTables') !!}',
      columns :[
        {data: 'rownum', name: 'rownum', searchable:false},
        { data: 'code', name: 'code' },
        { data: 'quotation_customer', name: 'quotation_customer.code' },
        { data: 'sales', name: 'quotation_customer.sales.name' },
        { data: 'customer_id', name: 'customer.name' },
        { data: 'amount', name: 'amount', className:'dt-body-right' },
        { data: 'status', name: 'status' },
        { data: 'received_date', name: 'received_date' },
        { data: 'project_code', name: 'project.code' },
        { data: 'actions', name: 'actions', orderable:false, searchable:false, className:'dt-body-center' },
      ],
      footerCallback: function( tfoot, data, start, end, display ) {
        var api = this.api();
        // Remove the formatting to get float data for summation
        var theFloat = function ( i ) {
            return typeof i === 'string' ?
                parseFloat(i.replace(/[\$,]/g, '')) :
                typeof i === 'number' ?
                    i : 0;
        };

        // Total over all pages
        total = api
            .column(5)
            .data()
            .reduce( function (a, b) {
                return theFloat(a) + theFloat(b);
            }, 0 );
        // Update footer
        $( api.column(5).footer() ).html(
            total.toLocaleString()
        );
      },
      order : [
        [7, 'desc']
      ]

    });
    
    var buttonTableTools = new $.fn.dataTable.Buttons(tablePOCustomer,{
        buttons: [
          {
            extend: 'excelHtml5',
            exportOptions: {
                columns: [0,1,2,3,4,5,6,7,8]
            }
          },
        ],
      }).container().appendTo($('#button-table-tools'));

    // Setup - add a text input to each header cell
    $('#searchColumn th').each(function() {
      if ($(this).index() != 0 && $(this).index() != 9) {
        $(this).html('<input class="form-control" type="text" placeholder="Search" data-id="' + $(this).index() + '" />');
      }
          
    });
    //Block search input and select
    $('#searchColumn input').keyup(function() {
      tablePOCustomer.columns($(this).data('id')).search(this.value).draw();
    });
    //ENDBlock search input and select

    // Delete button handler
    tablePOCustomer.on('click', '.btn-delete-purchase-order-customer', function(e){
      var id = $(this).attr('data-id');
      var code = $(this).attr('data-text');
      $('#po_customer_id').val(id);
      $('#po-customer-code-to-delete').text(code);
      $('#modal-delete-purchaseOrderCustomer').modal('show');
    });


    
  </script>
@endsection