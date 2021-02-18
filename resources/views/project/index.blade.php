@extends('layouts.app')

@section('page_title')
  Project
@endsection

@section('page_header')
  <h1>
    Project
    <small>Daftar Project</small>
  </h1>
@endsection

@section('breadcrumb')
  <ol class="breadcrumb">
    <li><a href="{{ URL::to('home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    <li><a href="{{ URL::to('project') }}"><i class="fa fa-legal"></i> Project</a></li>
    <li class="active"><i></i> Index</li>
  </ol>
@endsection

@section('content')
  <div class="row">
    <div class="col-lg-12">
        <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Project</h3>
              <a href="{{ URL::to('project/create')}}" class="btn btn-primary pull-right" title="Create new Project">
                <i class="fa fa-plus"></i>&nbsp;Add New
              </a>
            </div><!-- /.box-header -->
            <div class="box-body">
              <div class="table-responsive">
                <form method="POST" id="form-project-filter" class="form-inline" role="form">
                  <div class="form-group">
                      <label for="cost_margin_operator">Cost Margin</label>
                      <select name="cost_margin_operator" id="cost_margin_operator" class="form-control">
                        <option value="=">=</option>
                        <option value=">">&gt;</option>
                        <option value="<">&lt;</option>
                        <option value=">=">&gt;=</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label for="cost_margin_value"></label>
                      <input type="text" class="form-control" name="cost_margin_value" id="cost_margin_value" placeholder="value to be filtered">
                  </div>

                  <div class="form-group">
                      <label for="invoiced_operator">Invoiced</label>
                      <select name="invoiced_operator" id="invoiced_operator" class="form-control">
                        <option value="=">=</option>
                        <option value=">">&gt;</option>
                        <option value="<">&lt;</option>
                        <option value=">=">&gt;=</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label for="invoiced_value"></label>
                      <input type="text" class="form-control" name="invoiced_value" id="invoiced_value" placeholder="value to be filtered">
                  </div>
                  <button type="submit" class="btn btn-primary">Filter</button>
                </form>
              <br/>
                
                <table class="table" id="table-project">
                  <thead>
                    <tr>
                      <th style="width:5%;">#</th>
                      <th style="width:10%;">Category</th>
                      <th style="width:10%;">Project Number</th>
                      <th style="width:10%;">Project Name</th>
                      <th>Customer</th>
                      <th>Sales</th>
                      <th>Purchase Order</th>
                      <th>PO Amount</th>
                      <th>Invoiced</th>
                      <th>Pending Invoice amount</th>
                      <th>Paid Invoice amount</th>
                      <th>Margin</th>
                      <th>Project Date</th>
                      <th>Status</th>
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
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                    </tr>
                  </thead>
                  
                  <tbody>

                  </tbody>
                  <tfoot>
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
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                    </tr>
              </table>
            </div>
          </div><!-- /.box-body -->
          <div class="box-footer clearfix">
            
          </div>
        </div><!-- /.box -->
    </div>
  </div>

  <!--Modal Delete Project-->
  <div class="modal fade" id="modal-delete-Project" tabindex="-1" role="dialog" aria-labelledby="modal-delete-ProjectLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
      {!! Form::open(['url'=>'deleteProject', 'method'=>'post']) !!}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="modal-delete-ProjectLabel">Confirmation</h4>
        </div>
        <div class="modal-body">
          You are going to delete <b id="project-code-to-delete"></b>
          <br/>
          <p class="text text-danger">
            <i class="fa fa-info-circle"></i>&nbsp;This process can not be reverted
          </p>
          <input type="hidden" id="project_id" name="project_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      {!! Form::close() !!}
      </div>
    </div>
  </div>
<!--ENDModal Delete Project-->
@endsection

@section('additional_scripts')
  <script type="text/javascript">
    var tableProject =  $('#table-project').DataTable({
      "lengthMenu": [[10, 25, 100, 500, -1], [10, 25, 100, 500, "All"]],
      processing :true,
      serverSide : true,
      ajax : {
        url : '{!! url('project/dataTables') !!}',
        data: function(d){
          d.cost_margin_operator = $('select[name=cost_margin_operator]').val();
          d.cost_margin_value = $('input[name=cost_margin_value]').val();
          d.invoiced_operator = $('select[name=invoiced_operator]').val();
          d.invoiced_value = $('input[name=invoiced_value]').val();
        }
      },
      columns :[
        {data: 'rownum', name: 'rownum', searchable:false},
        { data: 'category', name: 'category' },
        { data: 'code', name: 'code' },
        { data: 'name', name: 'name' },
        { data: 'customer_id', name: 'purchase_order_customer.customer.name' },
        { data: 'sales_id', name: 'sales.name' },
        { data: 'purchase_order_customer_id', name: 'purchase_order_customer.code' },
        { data: 'purchase_order_customer_amount', name: 'purchase_order_customer.amount' },
        { data: 'invoiced', name: 'invoiced', orderable:false, searchable:false},
        { data: 'pending_invoice_customer_amount', name: 'pending_invoice_customer_amount', orderable:false, searchable:false },
        { data: 'paid_invoice_customer_amount', name: 'paid_invoice_customer_amount', orderable:false, searchable:false },
        { data: 'cost_margin', name: 'cost_margin', orderable:false, searchable:false, render:function(data, type, row, meta){
            if(data != null){
              return data+ '%';
            }else{
              return null;
            }
        }},
        { data: 'created_at', name: 'created_at'},
        { data: 'enabled', name: 'enabled', render:function(data, type, row, meta){
          var disp = '';
          if(data == true){
            disp = 'Enabled';
          }else{
            disp = 'Disabled';
          }
          return disp;
        }},
        { data: 'actions', name: 'actions', orderable:false, searchable:false, className:'dt-body-center' },
      ],
      footerCallback: function( tfoot, data, start, end, display ) {
        var api = this.api();
        var json = api.ajax.json();

        var external_count = 0;

        // Remove the formatting to get float data for summation
        var theFloat = function ( i ) {
            return typeof i === 'string' ?
                parseFloat(i.replace(/[\$,]/g, '')) :
                typeof i === 'number' ?
                    i : 0;
        };

        // Total PO Amount
        total_po_amount = api
            .column(7)
            .data()
            .reduce( function (a, b) {
                return theFloat(a) + theFloat(b);
            }, 0 );
        // Update footer
        $( api.column(7).footer() ).html(
            total_po_amount.toLocaleString()
        );

        // Total Pending Invoice Customer
        total_pending_invoice_customer_amount = api
            .column(9)
            .data()
            .reduce( function (a, b) {
                return theFloat(a) + theFloat(b);
            }, 0 );
        // Update footer
        $( api.column(9).footer() ).html(
            total_pending_invoice_customer_amount.toLocaleString()
        );

        // Total paid Invoice Customer
        total_paid_invoice_customer_amount = api
            .column(10)
            .data()
            .reduce( function (a, b) {
                return theFloat(a) + theFloat(b);
            }, 0 );
        // Update footer
        $( api.column(10).footer() ).html(
            total_paid_invoice_customer_amount.toLocaleString()
        );

        console.log(json.data);


        $.each(json.data, function (i, obj) {
          if(obj.category == 'internal'){
            console.log('internal');
          }else{
            external_count++;
          }
        });


        total_cost_margin = api
            .column(11)
            .data()
            .reduce( function (a, b) {
                return theFloat(a) + theFloat(b);
            }, 0 );
        var average_cost_margin = total_cost_margin/external_count;
        $( api.column(11).footer() ).html(
          
          average_cost_margin.toLocaleString()+'%'
        );
      },
      order : [
        [12, 'desc']
      ]

    });

    // Delete button handler
    tableProject.on('click', '.btn-delete-project', function(e){
      var id = $(this).attr('data-id');
      var code = $(this).attr('data-text');
      $('#project_id').val(id);
      $('#project-code-to-delete').text(code);
      $('#modal-delete-Project').modal('show');
    });

    $('#form-project-filter').on('submit', function(e) {
      tableProject.draw();
      e.preventDefault();
    });

    // Setup - add a text input to each header cell
    $('#searchColumn th').each(function() {      
      if ($(this).index() != 0 && $(this).index()!=8 && $(this).index() != 11 && $(this).index() != 14) {
        $(this).html('<input class="form-control" type="text" placeholder="Search" data-id="' + $(this).index() + '" />');
      }
    });
    //Block search input and select
    $('#searchColumn input').keyup(function() {
      tableProject.columns($(this).data('id')).search(this.value).draw();
    });
    //ENDBlock search input and select
    
  </script>
@endsection