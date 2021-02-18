@extends('layouts.app')

@section('page_title')
    Project
@endsection

@section('page_header')
  <h1>
    Project
    <small>Project Detail</small>
  </h1>
@endsection

@section('breadcrumb')
  <ol class="breadcrumb">
    <li><a href="{{ URL::to('home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    <li><a href="{{ URL::to('project') }}"><i class="fa fa-legal"></i> Project</a></li>
    <li><i></i> {{ $project->code }}</li>
    <li class="active"><i></i> Detail</li>
  </ol>
@endsection

@section('content')
  <div class="row">
    <div class="col-md-7">
      <!--BOX Basic Informations-->
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-legal"></i>&nbsp;Project Information</h3>
          <a href="{{ URL::to('project/'.$project->id.'/edit')}}" class="btn btn-success btn-xs pull-right" title="Edit">
                <i class="fa fa-edit"></i>&nbsp;Edit
          </a>
        </div><!-- /.box-header -->
        <div class="box-body">
          <div class="table-responsive">
            <table class="table">
              <tr>
                <td style="width: 20%;">Category</td>
                <td style="width: 1%;">:</td>
                <td>{{ $project->category }}</td>
              </tr>
              <tr>
                <td style="width: 20%;">Project Number</td>
                <td style="width: 1%;">:</td>
                <td>{{ $project->code }}</td>
              </tr>
              <tr>
                <td style="width: 20%;">Project Name</td>
                <td style="width: 1%;">:</td>
                <td>{{ $project->name }}</td>
              </tr>
              <tr>
                <td style="width: 20%;">Customer Name</td>
                <td style="width: 1%;">:</td>
                <td>
                @if($project->purchase_order_customer)
                  {{ $project->purchase_order_customer->customer ? $project->purchase_order_customer->customer->name : '' }}
                @endif
                </td>
              </tr>
              <tr>
                <td style="width: 20%;">Project Date</td>
                <td style="width: 1%;">:</td>
                <td>
                  @if($project->created_at)
                    {{ date_format(date_create($project->created_at), 'Y-m-d') }}
                  @else
                  @endif
                </td>
              </tr>
              <tr>
                <td style="width: 20%;">Sales Name</td>
                <td style="width: 1%;">:</td>
                <td>
                  @if($project->sales)
                    {{ $project->sales->name }}
                  @else
                  @endif
                </td>
              </tr>
              
              <tr>
                <td style="width: 20%;">Status</td>
                <td style="width: 1%;">:</td>
                <td>
                  @if($project->enabled == TRUE)
                    Enabled
                  @else
                    Disabled
                  @endif
                </td>
              </tr>
              <tr>
                <td style="width: 20%;">Is Completed</td>
                <td style="width: 1%;">:</td>
                <td>
                  @if($project->is_completed == TRUE)
                    Yes
                  @else
                    No 
                    @if(\Auth::user()->can('complete-project'))
                    <a href="#" id="btn-complete-project" class="btn btn-xs btn-warning">
                      <i class="fa fa-check"></i> Mark as completed
                    </a>
                    @endif
                  @endif
                </td>
              </tr>
            </table>
          </div>
        </div><!-- /.box-body -->
        <div class="box-footer clearfix">
          <a href="{{ URL::to('invoice-customer/create/?project_id='.$project->id.'')}}" class="btn btn-default btn-xs" title="Create new Invoice Customer">
            <i class="fa fa-plus"></i>&nbsp;Invoice Customer
          </a>&nbsp;
          @if($project->purchase_request && $project->purchase_request->count() == 0)
          <a href="{{ URL::to('purchase-request/create/?project_id='.$project->id.'')}}" class="btn btn-default btn-xs" title="Register Purchase Request to this project">
            <i class="fa fa-plus"></i>&nbsp;Purchase Request
          </a>
          @endif
        </div>
      </div>
      <!--ENDBOX Basic Informations-->

      <!--BOX Invoice Customer Information-->
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-credit-card"></i>&nbsp;Invoice Customer Information</h3>
          
        </div><!-- /.box-header -->
        <div class="box-body">
          <div class="table-responsive">
            <table class="table" id="table-invoice-customer">
              <thead>
                <tr>
                  <th style="width:20%;">Code</th>
                  <th style="width:20%;">Tax Number</th>
                  <th style="width:20%;">Amount</th>
                  <th style="width:20%;">Due Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              @if(count($project->invoice_customer))
                @foreach($project->invoice_customer as $invoice_customer)
                <tr>
                  <td>
                    <a href="{{ url('invoice-customer/'.$invoice_customer->id.'') }}">
                      {{ $invoice_customer->code }}
                    </a>
                  </td>
                  <td>{{ $invoice_customer->tax_number }}</td>
                  <td>{{ number_format($invoice_customer->amount, 2) }}</td>
                  <td>{{ $invoice_customer->due_date }}</td>
                  <td>{{ $invoice_customer->status }}</td>
                </tr>
                @endforeach
              @endif
              </tbody>
            </table>
          </div>
        </div><!-- /.box-body -->
      </div>
      <!--ENDBOX Invoice Customer Information-->

      <!--BOX Expenses Information-->
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-credit-card"></i>&nbsp;Expenses</h3>
        </div><!-- /.box-header -->
        <div class="box-body">
          <h4>Invoice Vendor</h4>
          <div class="table-responsive">
            <table class="table" id="table-invoice-vendor">
              <thead>
                <tr>
                  <th style="width:20%;">Code</th>
                  <th style="width:20%;">Tax Number</th>
                  <th style="width:20%;">Amount</th>
                  <th style="width:20%;">Vendor</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              @if(count($project->invoice_vendors))
                @foreach($project->invoice_vendors as $invoice_vendors)
                <tr>
                  <td>
                    <a href="{{ url('invoice-vendor/'.$invoice_vendors->id) }}">
                      {{ $invoice_vendors->code }}
                    </a>
                  </td>
                  <td>{{ $invoice_vendors->tax_number }}</td>
                  <td>{{ number_format($invoice_vendors->amount, 2) }}</td>
                  <td>
                      @if($invoice_vendors->purchase_order_vendor)
                        @if($invoice_vendors->purchase_order_vendor->vendor)
                          {{ $invoice_vendors->purchase_order_vendor->vendor->name }}
                        @endif
                      @endif
                    
                  </td>
                  <td>{{ $invoice_vendors->status }}</td>
                </tr>
                @endforeach
              @endif
              </tbody>
            </table>
          </div>
          <br>

          <h4>Internal Request</h4>
          <div class="table-responsive">
            <table class="table" id="table-internal-request">
              <thead>
                <tr>
                  <th style="width:20%;">Code</th>
                  <th style="width:20%;">Requester</th>
                  <th style="width:20%;">Amount</th>
                  <th style="width:20%;">Description</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              @if(count($project->internal_requests))
                @foreach($project->internal_requests as $internal_request)
                <tr>
                  <td>
                    <a href="{{ url('internal-request/'.$internal_request->id) }}">
                      {{ $internal_request->code }}
                    </a>
                  </td>
                  <td>{{ $internal_request->requester->name }}</td>
                  <td>{{ number_format($internal_request->amount, 2) }}</td>
                  <td>{{ $internal_request->description }}</td>
                  <td>{{ $internal_request->status }}</td>
                </tr>
                @endforeach
              @endif
              </tbody>
            </table>
          </div>
          <br>
          <h4>Settlement</h4>
          <div class="table-responsive">
            <table class="table" id="table-settlement">
              <thead>
                <tr>
                  <th style="width:20%;">Code</th>
                  <th style="width:20%;">Description</th>
                  <th style="width:20%;">Amount</th>
                  <th style="width:20%;">Status</th>
                  <th>Result</th>
                </tr>
              </thead>
              <tbody>
              @if($project->internal_requests)
                @foreach($project->internal_requests as $internal_request)
                  @if($internal_request->settlement)
                  <tr>
                    <td>
                      <a href="{{ url('settlement/'.$internal_request->settlement->id) }}">
                        {{ $internal_request->settlement->code }}
                      </a>
                    </td>
                    <td>{{ substr($internal_request->settlement->description,0,50) }}</td>
                    <td>{{ number_format($internal_request->amount - $internal_request->settlement->amount, 2) }}</td>
                    <td>{{ $internal_request->settlement->status }}</td>
                    <td>{{ $internal_request->settlement->result }}</td>
                  </tr>
                  @endif
                @endforeach
              @endif
              </tbody>
              <tfoot>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>


          </div>
        </div><!-- /.box-body -->
        <div class="box-footer">
          <div class="row alert alert-info">
            <div class="col-md-6">
              <strong>Total Expenses</strong>
            </div>
            <div class="col-md-6">
              <strong>{{ number_format($total_expenses,2) }}</strong>
            </div>
          </div>
        </div>
      </div>
      <!--ENDBOX Expenses Information-->
    </div>

    <div class="col-md-5">
      <!--BOX PO Customer Information-->
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-bookmark-o"></i>&nbsp;PO Customer Information</h3>
          
        </div><!-- /.box-header -->
        @if($project->purchase_order_customer)
        <div class="box-body">
          <strong>PO Number</strong>
          <p class="text-muted">
            <a href="{{ url('purchase-order-customer/'.$project->purchase_order_customer->id) }}">
              {{ $project->purchase_order_customer->code }}
            </a>
          </p>
          <strong>Amount</strong>
          <p class="text-muted">{{ number_format($project->purchase_order_customer->amount) }} <text class="text-info">(After PPN)</text></p>
          <p class="text-muted">{{ number_format($project->purchase_order_customer->amount/1.1) }} <text class="text-info">(Before PPN)</text> </p>
          <div class="pull-left">
            <strong>Margin</strong>
            <p class="text-muted">{{ round($project->cost_margin, 2) }}&nbsp;%</p>
          </div>
          <div class="pull-right">
            <?php 
              $sum_purchase_order_vendors = $project->purchase_order_vendors->sum('amount');
              $sum_internal_request = $project->internal_requests->sum('amount');
              $total_expense_from_settlement = $total_expense_from_settlement;
              $total_expenses = $sum_purchase_order_vendors+$sum_internal_request+$total_expense_from_settlement;
              $purchase_order_customer_amount = $project->purchase_order_customer->amount;
              //$estimated_cost_margin = 100 - (($total_expenses/$purchase_order_customer_amount) * 100);
              $estimated_cost_margin = $project->estimated_cost_margin;
            ?>
            <strong>Estimated Margin</strong>
            <!-- <p class="text-muted">{{ round($project->estimated_cost_margin, 2) }}&nbsp;%</p> -->
            <p class="text-muted">{{ round($estimated_cost_margin, 2) }}&nbsp;%</p>
            <strong>Total Purchase Order Vendor</strong>
            <p class="text-muted">{{ number_format($project->purchase_order_vendors->sum('amount')) }}</p>
            <strong>Total Internal Request</strong>
            <p class="text-muted">{{ number_format($project->internal_requests->sum('amount')) }}</p>
            <strong>Total Expense From Settlement</strong>
            <p class="text-muted">{{ number_format($total_expense_from_settlement) }}</p>
            

          </div>
        </div><!-- /.box-body -->
        <div class="box-footer">
          <div class="pull-left">
            <strong>Total Paid Invoice</strong>
            <p class="text-muted">{{ number_format($total_paid_invoice, 2) }}</p>
            <strong>Invoiced</strong>
            <p class="text-muted">{!! $invoiced !!} %</p>
          </div>
          <div class="pull-right">
            <strong class="text text-warning">Total Invoice Due</strong>
            <p class="text-muted">{{ number_format($total_invoice_due, 2) }}</p>
          </div>
        </div>
        @else
        <div class="box-body">
          <p class="alert alert-info">
            <i class="fa fa-info-circle"></i>&nbsp;This project doesn't have Purchase Order Customer data.
          </p>
        </div>
        @endif
      </div>
      <!--ENDBOX PO Customer Information-->

      
    </div>
  </div>

  <!--Modal Complete Project-->
  <div class="modal fade" id="modal-complete-project" tabindex="-1" role="dialog" aria-labelledby="modal-complete-projectLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
      {!! Form::open(['url'=>'project/complete', 'method'=>'post']) !!}
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="modal-complete-projectLabel">Confirmation</h4>
        </div>
        <div class="modal-body">
          Press Mark as Completed to continue
          <br/>
          <p class="text text-danger">
            <i class="fa fa-info-circle"></i>&nbsp;This process can not be reverted
          </p>
          <input type="hidden" id="project_id_to_complete" name="project_id_to_complete" value="{{$project->id}}">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Mark as Completed</button>
        </div>
      {!! Form::close() !!}
      </div>
    </div>
  </div>
<!--ENDModal Complete Project-->
@endsection

@section('additional_scripts')
  <script type="text/javascript">
    

    $(document).ready(function() {
      var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

      $('table#table-invoice-customer').DataTable();
      $('table#table-invoice-vendor').DataTable();
      $('table#table-internal-request').DataTable();
      $('table#table-settlement').DataTable({
          footerCallback: function( tfoot, data, start, end, display ) {
          var api = this.api();
          // Remove the formatting to get float data for summation
          var theFloat = function ( i ) {
              return typeof i === 'string' ?
                  parseFloat(i.replace(/[\$,]/g, '')) :
                  typeof i === 'number' ?
                      i : 0;
          };

          // Total amount from current page
          total_amount = api
              .column(2)
              .data()
              .reduce( function (a, b) {
                  return theFloat(a) + theFloat(b);
              }, 0 );
          
          $( api.column(2).footer() ).html(
              total_amount.toLocaleString()
          );
          // ENDTotal amount from current page
        },
      });

      //Handler Complete project
      $('#btn-complete-project').on('click', function(event){
        event.preventDefault();
        $('#modal-complete-project').modal('show');
      });
      //ENDHandler Complete project

      //Handler Generate manhour cost
      $('#btn-generate-manhour-cost').on('click', function(event){
        event.preventDefault();
        $.ajax({
            url: '{!! url('project/generate-manhour-cost') !!}',
            type: 'POST',
            data: {_token: CSRF_TOKEN, project_id:'{!! $project->id !!}'},
            dataType: 'JSON',
            success: function (response) { 
              console.log(response);
              if(response.success == true){
                getTotalCost();
              }else{
                alert('not success');
              }
            }
        });
      });
      //ENDHandler Generate manhour cost
      getTotalCost();
      //get total cost of the project from manhour
      function getTotalCost(){
        $.ajax({
            url: '{!! url('project/getTotalManhorCost') !!}',
            type: 'get',
            data: {_token: CSRF_TOKEN, project_id:'{!! $project->id !!}'},
            dataType: 'JSON',
            success: function (response) { 
              console.log(response);
              if(response.success == true){
                var total_cost = response.data.total_cost;
                console.log(total_cost);
                if(total_cost != 0){
                  $('#total_manhour_cost').text(total_cost);
                }else{
                  $('#total_manhour_cost').text('No data, please click the generate button and make sure this project is related to ets');
                }
               
              }else{
                alert('not success');
              }
            }
        });
      }

    });

    
  </script>
@endsection