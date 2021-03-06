@extends('layouts.app')

@section('page_title')
  Edit Settlement
@endsection

@section('additional_styles')
  {!! Html::style('css/datepicker/datepicker3.css') !!}
@endsection

@section('page_header')
  <h1>
    Settlement
    <small>Edit Settlement</small>
  </h1>
@endsection

@section('breadcrumb')
  <ol class="breadcrumb">
    <li><a href="{{ URL::to('home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    <li><a href="{{ URL::to('settlement') }}"><i class="fa fa-retweet"></i>Settlement</a></li>
    <li><a href="{{ URL::to('settlement/'.$settlement->code) }}">{{ $settlement->code }}</a></li>
    <li class="active"><i></i> Edit</li>
  </ol>
@endsection
  
@section('content')
  <div class="row">
    <div class="col-md-8">
      <!--BOX Basic Informations-->
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-retweet"></i>&nbsp;Form Create Settlement</h3>
        </div><!-- /.box-header -->
        <div class="box-body">
          {!! Form::model($settlement, ['route'=>['settlement.update', $settlement->id], 'class'=>'form-horizontal','id'=>'form-edit-quotation-vendor', 'method'=>'put', 'files'=>true]) !!}
          <div class="form-group{{ $errors->has('internal_request_id') ? ' has-error' : '' }}">
            {!! Form::label('internal_request_id', 'Internal Request', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('internal_request_code',$settlement->internal_request->code,['class'=>'form-control', 'id'=>'internal_request_code', 'readonly'=>true]) !!}
              {!! Form::hidden('internal_request_id',$settlement->internal_request->id,['class'=>'form-control', 'id'=>'internal_request_id']) !!}
              @if ($errors->has('internal_request_id'))
                <span class="help-block">
                  <strong>{{ $errors->first('internal_request_id') }}</strong>
                </span>
              @endif
            </div>
          </div>

          <div class="form-group{{ $errors->has('transaction_date') ? ' has-error' : '' }}">
            {!! Form::label('transaction_date', 'Transaction Date', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('transaction_date',null,['class'=>'form-control', 'id'=>'transaction_date', 'placeholder'=>'Transaction date of settlement']) !!}
              @if ($errors->has('transaction_date'))
                <span class="help-block">
                  <strong>{{ $errors->first('transaction_date') }}</strong>
                </span>
              @endif
            </div>
          </div>

          <div class="form-group{{ $errors->has('amount') ? ' has-error' : '' }}">
            {!! Form::label('amount', 'Amount', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('amount',null,['class'=>'form-control', 'id'=>'amount', 'placeholder'=>'Amount of settlement']) !!}
              @if ($errors->has('amount'))
                <span class="help-block">
                  <strong>{{ $errors->first('amount') }}</strong>
                </span>
              @endif
            </div>
          </div>

          <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
            {!! Form::label('description', 'Description', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::textarea('description',null,['class'=>'form-control', 'id'=>'description', 'placeholder'=>'Description of settlement']) !!}
              @if ($errors->has('description'))
                <span class="help-block">
                  <strong>{{ $errors->first('description') }}</strong>
                </span>
              @endif
            </div>
          </div>
          
          
          <div class="form-group">
              {!! Form::label('', '', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              <a href="{{ url('settlement') }}" class="btn btn-default">
                <i class="fa fa-repeat"></i>&nbsp;Cancel
              </a>&nbsp;
              <button type="submit" class="btn btn-info" id="btn-submit-settlement">
                <i class="fa fa-save"></i>&nbsp;Submit
              </button>
            </div>
          </div>
          {!! Form::close() !!}
          
        </div><!-- /.box-body -->
      </div>
      <!--ENDBOX Basic Informations-->
    </div>

    <div class="col-md-4">
      <!--BOX Basic Informations-->
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-tag"></i>&nbsp;Internal Request</h3>
        </div><!-- /.box-header -->
        <div class="box-body">
          <strong>Code</strong>
          <p class="text-muted">{{ $settlement->internal_request->code }}</p>
          <strong>Amount</strong>
          <p class="text-muted">{{ number_format($settlement->internal_request->amount, 2) }}</p>
          <strong>Description</strong>
          <p class="text-muted">{!! nl2br($settlement->internal_request->description) !!}</p>
          
        </div><!-- /.box-body -->
      </div>
      <!--ENDBOX Basic Informations-->
    </div>
  </div>
@endsection

@section('additional_scripts')
  {!! Html::script('js/datepicker/bootstrap-datepicker.js') !!}
  {!! Html::script('js/autoNumeric.js') !!}
  <script type="text/javascript">
    $('#amount').autoNumeric('init',{
        aSep:',',
        aDec:'.'
    });
    //Block Transacation Date
    $('#transaction_date').on('keydown', function(event){
      event.preventDefault();
    });
    $('#transaction_date').datepicker({
      format : 'yyyy-mm-dd'
    });
    //ENDBlock Transacation Date
    $('#form-create-settlement').on('submit', function(){
      $('#btn-submit-settlement').prop('disabled', true);
    });
  </script>
@endsection