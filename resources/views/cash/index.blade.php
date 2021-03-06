@extends('layouts.app')

@section('page_title')
  Cash
@endsection

@section('page_header')
  <h1>
    Cash
    <small>Daftar Cash</small>
  </h1>
@endsection

@section('breadcrumb')
  <ol class="breadcrumb">
    <li><a href="{{ URL::to('home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    <li><a href="{{ URL::to('cash') }}"><i class="fa fa-cube"></i> Cash</a></li>
    <li class="active"><i></i> Index</li>
  </ol>
@endsection
  
@section('content')
  <div class="row">
    <div class="col-lg-12">
        <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Cash</h3>
              <a href="{{ URL::to('cash/create')}}" class="btn btn-primary pull-right" title="Create new Cash">
                <i class="fa fa-plus"></i>&nbsp;Add New
              </a>
            </div><!-- /.box-header -->
            <div class="box-body">
              <form method="POST" id="form-filter" class="form-inline" role="form">
                <div class="form-group">
                  <label for="status">Status</label>
                  <select name="status" id="status" class="form-control">
                    <option value="enabled">Enabled</option>
                    <option value="disabled">Disabled</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for=""></label>
                  <button type="submit" class="btn btn-primary">Filter</button>  
                </div>
                
              </form>
            </div>
            <div class="box-body">
              <div class="table-responsive">
                
                <br/>
                <table class="table table-bordered" id="table-cash">
                  <thead>
                    <tr>
                      <th style="width:5%;">#</th>
                      <th>Type</th>
                      <th>Name</th>
                      <th>Account Number</th>
                      <th>Description</th>
                      <th style="text-align: right;">Amount</th>
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
                      <th style="text-align:right;"></th>
                      <th></th>
                    </tr>
                  </tfoot>
              </table>
            </div>
          </div><!-- /.box-body -->
          <div class="box-footer clearfix">
            
          </div>
        </div><!-- /.box -->
    </div>
  </div>

  <!--Modal Delete Cash-->
  <div class="modal fade" id="modal-delete-cash" tabindex="-1" role="dialog" aria-labelledby="modal-delete-cashLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
      {!! Form::open(['url'=>'deleteCash', 'method'=>'post']) !!}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="modal-delete-cashLabel">Confirmation</h4>
        </div>
        <div class="modal-body">
          You are going to delete <b id="cash-name-to-delete"></b>
          <br/>
          <p class="text text-danger">
            <i class="fa fa-info-circle"></i>&nbsp;This process can not be reverted
          </p>
          <input type="hidden" id="cash_id" name="cash_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      {!! Form::close() !!}
      </div>
    </div>
  </div>
  <!--ENDModal Delete Cash-->
@endsection

@section('additional_scripts')
 
   <script type="text/javascript">
    var tableCash =  $('#table-cash').DataTable({
      processing :true,
      serverSide : true,
      //ajax : '{!! route('datatables.getCashes') !!}',
      ajax : {
        url : '{!! route('datatables.getCashes') !!}',
        data: function(d){
          d.status = $('select[name=status]').val();
        }
      },
      columns :[
        {data: 'rownum', name: 'rownum', searchable:false},
        { data: 'type', name: 'type' },
        { data: 'name', name: 'name' },
        { data: 'account_number', name: 'account_number' },
        { data: 'description', name: 'description' },
        { data: 'amount', name: 'amount', className:'dt-body-right' },
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
            .column( 5 )
            .data()
            .reduce( function (a, b) {
                return theFloat(a) + theFloat(b);
            }, 0 );
        // Update footer
        $( api.column( 5 ).footer() ).html(
            total.toLocaleString()
        );
      }

    });

    // Delete button handler
    tableCash.on('click', '.btn-delete-cash', function(e){
      var id = $(this).attr('data-id');
      var code = $(this).attr('data-text');
      $('#cash_id').val(id);
      $('#cash-name-to-delete').text(code);
      $('#modal-delete-cash').modal('show');
    });

    // Setup - add a text input to each header cell
    $('#searchColumn th').each(function() {
      if ($(this).index() != 0 && $(this).index() != 6) {
        $(this).html('<input class="form-control" type="text" placeholder="Search" data-id="' + $(this).index() + '" />');
      }
          
    });
    //Block search input and select
    $('#searchColumn input').keyup(function() {
      tableCash.columns($(this).data('id')).search(this.value).draw();
    });
    //ENDBlock search input and select
    $('#form-filter').on('submit', function(e) {
      tableCash.draw();
      e.preventDefault();
    });
    
  </script>
@endsection