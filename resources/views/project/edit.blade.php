@extends('layouts.app')

@section('page_title')
    Project
@endsection

@section('page_header')
  <h1>
    Project
    <small>Edit Project</small>
  </h1>
@endsection

@section('breadcrumb')
  <ol class="breadcrumb">
    <li><a href="{{ URL::to('home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    <li><a href="{{ URL::to('project') }}"><i class="fa fa-legal"></i> Project</a></li>
    <li><i></i> {{ $project->code }} </li>
    <li class="active"><i></i> Edit</li>
  </ol>
@endsection

@section('content')
  <div class="row">
    <div class="col-md-12">
      <!--BOX Basic Informations-->
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Form Edit Project</h3>
        </div><!-- /.box-header -->
        <div class="box-body">
          {!! Form::model($project, ['route'=>['project.update', $project->id], 'class'=>'form-horizontal','id'=>'form-edit-purchase-order-customer', 'method'=>'put', 'files'=>true]) !!}
          
          <div class="form-group{{ $errors->has('category') ? ' has-error' : '' }}">
            {!! Form::label('category', 'Category', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {{ Form::select('category', ['internal'=>"Internal", 'external'=>"External"], null, ['class'=>'form-control', 'placeholder'=>'Select Category', 'id'=>'category']) }}
              @if ($errors->has('category'))
                <span class="help-block">
                  <strong>{{ $errors->first('category') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <div class="form-group{{ $errors->has('code') ? ' has-error' : '' }}">
            {!! Form::label('code', 'Project code', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('code',null,['class'=>'form-control', 'placeholder'=>'Project code', 'id'=>'code', 'disabled'=>true]) !!}
              @if ($errors->has('code'))
                <span class="help-block">
                  <strong>{{ $errors->first('code') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
            {!! Form::label('name', 'Project Name', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('name',null,['class'=>'form-control', 'placeholder'=>'Project Name', 'id'=>'name']) !!}
              @if ($errors->has('name'))
                <span class="help-block">
                  <strong>{{ $errors->first('name') }}</strong>
                </span>
              @endif
            </div>
          </div> 
          <div class="form-group{{ $errors->has('purchase_order_customer_id') ? ' has-error' : '' }}">
            {!! Form::label('purchase_order_customer_id', 'Purchase Order Customer', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {{ Form::select('purchase_order_customer_id', $purchase_order_customer_opts, null, ['class'=>'form-control', 'placeholder'=>'Select Purchase Order customer', 'id'=>'purchase_order_customer_id']) }}
              @if ($errors->has('purchase_order_customer_id'))
                <span class="help-block">
                  <strong>{{ $errors->first('purchase_order_customer_id') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <div class="form-group{{ $errors->has('sales_id') ? ' has-error' : '' }}">
            {!! Form::label('sales_id', 'Sales', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              @if($project->sales)
                {!! Form::text('sales_name',$project->purchase_order_customer->quotation_customer->sales->name,['class'=>'form-control', 'placeholder'=>'Sales Name', 'id'=>'sales_name', 'readonly'=>true]) !!}
                {!! Form::hidden('sales_id',$project->purchase_order_customer->quotation_customer->sales->id,['class'=>'form-control', 'placeholder'=>'Sales ID', 'id'=>'sales_id']) !!}
              @else
                {!! Form::text('sales_name',null,['class'=>'form-control', 'placeholder'=>'Sales Name', 'id'=>'sales_name', 'readonly'=>true]) !!}
                {!! Form::hidden('sales_id',null,['class'=>'form-control', 'placeholder'=>'Sales ID', 'id'=>'sales_id']) !!}
              @endif
              @if ($errors->has('sales_id'))
                <span class="help-block">
                  <strong>{{ $errors->first('sales_id') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <div class="form-group{{ $errors->has('enabled') ? ' has-error' : '' }}">
            {!! Form::label('enabled', 'Project enabled', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {{ Form::select('enabled', [TRUE=>"Enabled", FALSE=>"Disabled"], null, ['class'=>'form-control', 'placeholder'=>'Select Status', 'id'=>'enabled']) }}
              @if ($errors->has('enabled'))
                <span class="help-block">
                  <strong>{{ $errors->first('enabled') }}</strong>
                </span>
              @endif
            </div>
          </div> 
          <div class="form-group">
              {!! Form::label('', '', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              <a href="{{ url('project/'.$project->id) }}" class="btn btn-default">
                <i class="fa fa-repeat"></i>&nbsp;Cancel
              </a>&nbsp;
              <button type="submit" class="btn btn-info" id="btn-submit-customer">
                <i class="fa fa-save"></i>&nbsp;Submit
              </button>
            </div>
          </div>
          {!! Form::close() !!}
          
        </div><!-- /.box-body -->
      </div>
      <!--ENDBOX Basic Informations-->
    </div>
  </div>
@endsection

@section('additional_scripts')
  <script type="text/javascript">

    //Block purchase order customer selection
    $('#purchase_order_customer_id').select2({
      placeholder: 'Select Purchase Order Customer',
      ajax: {
        url: '{!! url('select2PurchaseOrderCustomerForProject') !!}',
        dataType: 'json',
        delay: 250,
        processResults: function (data) {
          return {
            results:  $.map(data, function (item) {
                  return {
                      text: item.code,
                      id: item.id
                  }
              })
          };
        },
        cache: true
      }
    });

    $('#purchase_order_customer_id').on('select2:select', function(){
      var purchase_order_customer_id = $(this).val();
      var token = $('meta[name="csrf-token"]').attr('content');
      $.ajax({
        url : '/project/getSalesFromPurchaseOrderCustomer',
        type : 'POST',
        data : 'purchase_order_customer_id='+purchase_order_customer_id+'&_token='+token,
        beforeSend : function(){},
        success:function(response){
          obj = $.parseJSON(response);
          $('#sales_name').val(obj.name);
          $('#sales_id').val(obj.id);
          console.log(response);
          
        }
      });
    });
    //ENDBlock purchase order customer selection

    function check_category()
    {
      var category = $('#category').val();
      if(category == 'internal'){
        $('#code').prop('disabled', false);
        $('#sales_id').val('');
        $('#sales_name').val('');
        $('#purchase_order_customer_id').val('').trigger('change');
        $('#purchase_order_customer_id').prop('disabled', true);
      }
      else{
        $('#code').prop('disabled', true);
        $('#purchase_order_customer_id').prop('disabled', false);
      }
    }
    
    $('#category').on('change', function(){
      check_category();
    });
    check_category();
</script>
@endsection