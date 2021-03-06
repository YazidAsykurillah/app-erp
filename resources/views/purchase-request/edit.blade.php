@extends('layouts.app')

@section('page_title')
    Purchase Request
@endsection

@section('page_header')
  <h1>
    Purchase Request
    <small>Edit Purchase Request</small>
  </h1>
@endsection

@section('breadcrumb')
  <ol class="breadcrumb">
    <li><a href="{{ URL::to('home') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    <li><a href="{{ URL::to('purchase-request') }}"><i class="fa fa-tag"></i> Purchase Request</a></li>
    <li><i></i> {{ $purchase_request->code }}</li>
    <li class="active"><i></i> Edit</li>
  </ol>
@endsection

@section('content')
  
  <div class="row">
    <div class="col-md-12">
      <!--BOX Basic Informations-->
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Form Edit Purchase Request</h3>
        </div><!-- /.box-header -->
        <div class="box-body">
          {!! Form::model($purchase_request, ['route'=>['purchase-request.update', $purchase_request->id], 'class'=>'form-horizontal','id'=>'form-edit-purchase-request', 'method'=>'put', 'files'=>true]) !!}
          <div class="form-group">
            {!! Form::label('code', 'Purchase Request Number', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('code',null,['class'=>'form-control', 'placeholder'=>'Code of Purchase Request', 'id'=>'code', 'disabled'=>true]) !!}
            </div>
          </div>
          <div class="form-group{{ $errors->has('project_id') ? ' has-error' : '' }}">
            {!! Form::label('project_id', 'Project', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {{ Form::select('project_id', $project_opts, null, ['class'=>'form-control', 'placeholder'=>'Select Project', 'id'=>'project_id']) }}
              @if ($errors->has('project_id'))
                <span class="help-block">
                  <strong>{{ $errors->first('project_id') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <!-- Selection Quotation Vendor-->
          <div class="form-group{{ $errors->has('quotation_vendor_id') ? ' has-error' : '' }}">
            {!! Form::label('quotation_vendor_id', 'Quotation Vendor', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              <select name="quotation_vendor_id" id="quotation_vendor_id" class="form-control" style="width:100%;">
                @if($purchase_request->quotation_vendor)
                  <option value="{{ $purchase_request->quotation_vendor_id }}">{{ $purchase_request->quotation_vendor->code }}</option>
                @endif
                @if(Request::old('quotation_vendor_id') != NULL)
                  <option value="{{Request::old('quotation_vendor_id')}}">
                    {{ \App\QuotationVendor::find(Request::old('quotation_vendor_id'))->name }}
                  </option>
                @endif
              </select>
              @if ($errors->has('quotation_vendor_id'))
                <span class="help-block">
                  <strong>{{ $errors->first('quotation_vendor_id') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <!-- ENDSelection Quotation Vendor-->

          <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
            {!! Form::label('description', 'Description', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::textarea('description',null,['class'=>'form-control', 'placeholder'=>'Description of transaction', 'id'=>'description']) !!}
              @if ($errors->has('description'))
                <span class="help-block">
                  <strong>{{ $errors->first('description') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <!-- Group items -->
            <div class="form-group">
              {!! Form::label('item', 'Item(s)', ['class'=>'col-sm-2 control-label']) !!}
              <div class="col-sm-10">
                <div class="table-responsive">
                  <table id="table-items" class="table">
                    <thead>
                      <tr>
                        <th>Item</th>
                        <th style="width:15%;">Qty</th>
                        <th style="width:15%;">Unit</th>
                        <th style="width:20%;">Price/Unit</th>
                        <th style="width:20%;">Sub Amount</th>
                        <th style="width:5%"><button id="btn-add-item" class="btn btn-primary btn-xs" type="button">Add Item</button></th>
                      </tr>
                    </thead>
                    <tbody id="table_items_body">
                      <!-- Build row items from occured item validation error-->
                      @if(Form::old('item'))
                         @foreach(old('item') as $key => $val)
                            <tr id="row_index_{{$key}}">
                              <td class="{{ $errors->has('item.'.$key.'') ? ' has-error' : '' }}">
                                <textarea name="item[{{ $key }}]" class="form-control item ">{{ old('item.'.$key.'') }}</textarea>
                                 @if ($errors->has('item.'.$key.''))
                                    <span class="help-block">
                                      <strong>{{ $errors->first('item.'.$key.'') }}</strong>
                                    </span>
                                  @endif
                              </td>
                              <td><input type="text" name="quantity[{{$key}}]" class="form-control quantity" value="{{ old('quantity.'.$key) }}" /></td>
                              <td><input type="text" name="unit[{{$key}}]" class="form-control unit" value="{{ old('unit.'.$key) }}" /></td>
                              <td><input type="text" name="price[{{$key}}]" class="form-control price" value="{{ old('price.'.$key) }}" /></td>
                              <td><input type="text" name="sub_amount[{{$key}}]" class="form-control sub_amount" value="{{ old('sub_amount.'.$key) }}" readonly /></td>
                              @if($key !=0)
                              <td><button class="btn btn-danger btn-xs btn-remove-item" type="button"><i class="fa fa-trash"></i></button></td>
                              @endif
                            </tr>
                        @endforeach
                      <!-- Build row items from NOT occured item validation error / The page load at very first-->
                      @else
                        @if(count($items))
                          <?php $item_counter = 0; ?>
                          @foreach($items as $item)
                            <?php $item_counter +=1; ?>
                          <tr id="row_index_{{$item_counter}}">
                            <td class="{{ $errors->has('item.'.$item_counter) ? ' has-error' : '' }}">
                              <textarea name="item[{{$item_counter}}]" class="form-control item">{{ $item->item }}</textarea>
                               @if ($errors->has('item.'.$item_counter))
                                  <span class="help-block">
                                    <strong>{{ $errors->first('item.'.$item_counter) }}</strong>
                                  </span>
                                @endif
                            </td>
                            <td><input type="text" name="quantity[{{$item_counter}}]" class="form-control quantity" value="{{ $item->quantity }}" /></td>
                            <td><input type="text" name="unit[{{$item_counter}}]" class="form-control unit" value="{{ $item->unit }}" /></td>
                            <td><input type="text" name="price[{{$item_counter}}]" class="form-control price" value="{{ $item->price }}" /></td>
                            <td><input type="text" name="sub_amount[{{$item_counter}}]" class="form-control sub_amount" value="{{ $item->sub_amount }}" readonly /></td>
                          </tr>
                          @endforeach
                        @else
                        @endif

                      @endif
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="4"><strong>Total Sub Amount</strong></td>
                        <td><input type="text" name="total_sub_amount" id="total_sub_amount" class="form-control" value="{{ $purchase_request->after_discount }}" readonly></td>
                        <td></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
            <!-- ENDGroup items -->

          <div class="form-group{{ $errors->has('discount') ? ' has-error' : '' }}">
            {!! Form::label('discount', 'Discount', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              <div class="input-group">
                {!! Form::text('discount',null,['class'=>'form-control', 'placeholder'=>'discount of the PO Vendor', 'id'=>'discount']) !!}
                <span class="input-group-btn">
                  <button class="btn btn-default" type="button">%</button>
                </span>
                @if ($errors->has('discount'))
                  <span class="help-block">
                    <strong>{{ $errors->first('discount') }}</strong>
                  </span>
                @endif
              </div>
            </div>
          </div>
          <div class="form-group{{ $errors->has('discount_value') ? ' has-error' : '' }}">
            {!! Form::label('discount_value', 'Discount Value', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              <?php 
                $org_discount_value = 0;
                if($purchase_request->discount !=0){
                  $org_discount_value = $purchase_request->discount / 100 * $purchase_request->sub_amount;
                }
              ?>

              {!! Form::text('discount_value',$org_discount_value,['class'=>'form-control', 'placeholder'=>'discount_value of the PO Vendor', 'id'=>'discount_value', 'readonly'=>true]) !!}
              @if ($errors->has('discount_value'))
                <span class="help-block">
                  <strong>{{ $errors->first('discount_value') }}</strong>
                </span>
              @endif
            </div>
          </div>
          
          <div class="form-group{{ $errors->has('after_discount') ? ' has-error' : '' }}">
            {!! Form::label('after_discount', 'After Discount', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('after_discount',null,['class'=>'form-control', 'placeholder'=>'after_discount of the PO Vendor', 'id'=>'after_discount', 'readonly'=>true]) !!}
              @if ($errors->has('after_discount'))
                <span class="help-block">
                  <strong>{{ $errors->first('after_discount') }}</strong>
                </span>
              @endif
            </div>
          </div>

          <div class="form-group{{ $errors->has('vat') ? ' has-error' : '' }}">
            {!! Form::label('vat', 'VAT', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              <div class="input-group">
                {!! Form::text('vat',null,['class'=>'form-control', 'placeholder'=>'VAT of the PO Vendor', 'id'=>'vat']) !!}
                <span class="input-group-btn">
                  <button class="btn btn-default" type="button">%</button>
                </span>
                @if ($errors->has('vat'))
                  <span class="help-block">
                    <strong>{{ $errors->first('vat') }}</strong>
                  </span>
                @endif
              </div>
            </div>
          </div>
          <div class="form-group{{ $errors->has('wht') ? ' has-error' : '' }}">
            {!! Form::label('wht', 'WHT', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::text('wht',null,['class'=>'form-control', 'placeholder'=>'WHT of the Purchase Request', 'id'=>'wht']) !!}
              @if ($errors->has('wht'))
                <span class="help-block">
                  <strong>{{ $errors->first('wht') }}</strong>
                </span>
              @endif
            </div>
          </div>
          
          <div class="form-group{{ $errors->has('amount') ? ' has-error' : '' }}">
            {!! Form::label('amount', 'Amount', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!!Form::text('amount',null,['class'=>'form-control', 'placeholder'=>'Amount of the purchase order', 'id'=>'amount', 'readonly'=>true])!!}
              @if ($errors->has('amount'))
                <span class="help-block">
                  <strong>{{ $errors->first('amount') }}</strong>
                </span>
              @endif
            </div>
          </div>

          <div class="form-group{{ $errors->has('terms') ? ' has-error' : '' }}">
            {!! Form::label('terms', 'Terms and Condition', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              {!! Form::textarea('terms',null,['class'=>'form-control', 'placeholder'=>'terms of the purchase order', 'id'=>'terms']) !!}
              @if ($errors->has('terms'))
                <span class="help-block">
                  <strong>{{ $errors->first('terms') }}</strong>
                </span>
              @endif
            </div>
          </div>
          <div class="form-group">
              {!! Form::label('', '', ['class'=>'col-sm-2 control-label']) !!}
            <div class="col-sm-10">
              <a href="{{ url('purchase-request/'.$purchase_request->id) }}" class="btn btn-default">
                <i class="fa fa-repeat"></i>&nbsp;Cancel
              </a>&nbsp;
              <button type="submit" class="btn btn-info" id="btn-update-purchase-request">
                <i class="fa fa-save"></i>&nbsp;Update
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
  {!! Html::script('js/autoNumeric.js') !!}
  <script type="text/javascript">
    //Block initialize autonumerical inputs
    $('#amount, .quantity, #vat, #wht, .price, .sub_amount, #total_sub_amount, #discount, #discount_value, #after_discount' ).autoNumeric('init',{
        aSep:',',
        aDec:'.'
    });
    

    
    

    //Block purchase order customer selection
    $('#project_id').select2({
      placeholder: 'Select Project',
      ajax: {
        url: '{!! url('select2ProjectForPurchaseRequest') !!}',
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
    //ENDBlock purchase order customer selection

    //Block Quotation Vendor id Selection
    $('#quotation_vendor_id').select2({
      placeholder: 'Quotation Vendor',
      ajax: {
        url: '{!! url('select2QuotationVendorForPurchaseRequest') !!}',
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
    //ENDBlock Quotation Vendor id Selection


    //Block price input
      //sub amount filling per row when price input is on keyupped
      $('.price').on('keyup', function(){
          var this_price = $(this).val().replace(/,/g, "");
          var this_quantity = $(this).parent().parent().find('.quantity').val().replace(/,/g, "");
          $(this).parent().parent().find('.sub_amount').autoNumeric('set',this_price*this_quantity);
          fill_total_sub_amount();
          update_discount_value_value();
          update_after_discount_value();
          update_amount_value();
      });
    //ENDBLock price input

    //Block sub_amount input
      //sub amount filling per row when quantity input is on keyupped
      $('.quantity').on('keyup', function(){
          var this_quantity = $(this).val().replace(/,/g, "");
          var this_price_per_unit = $(this).parent().parent().find('.price').val().replace(/,/g, "");
          $(this).parent().parent().find('.sub_amount').autoNumeric('set',this_quantity*this_price_per_unit);
          fill_total_sub_amount();
          update_discount_value_value();
          update_after_discount_value();
          update_amount_value();
      });
    //ENDBLock sub_amount input

    //Block Button add item handling
    var index_initiator = parseInt('{{ count($items) }}');
    $('#btn-add-item').on('click', function(){
      
      index_initiator+=1;
      var row_item = '<tr id="row_index_'+index_initiator+'">'+
                        '<td><textarea name="item['+index_initiator+']" class="form-control item" value=""></textarea></td>'+
                        '<td><input type="text" name="quantity['+index_initiator+']" class="form-control quantity" /></td>'+
                        '<td><input type="text" name="unit['+index_initiator+']" class="form-control unit" /></td>'+
                        '<td><input type="text" name="price['+index_initiator+']" class="form-control price" /></td>'+
                        '<td><input type="text" name="sub_amount['+index_initiator+']" class="form-control sub_amount" readonly /></td>'+
                        '<td><button class="btn btn-danger btn-xs btn-remove-item" type="button"><i class="fa fa-trash"></i></button></td>'+
                      '</tr>';
      $('#table-items').find('tbody').append(row_item);
      $('#table-items').find('tr td button.btn-remove-item').on('click', function(){
        remove_row($(this));
      });

      //Prepare all needed inputs that has to be autoNumeric initialized
        // Quantity input
        $('#table-items').find('tr td input.quantity').autoNumeric('init',{
          aSep:',',
          aDec:'.'
        });

        //Sub amount input
        $('#table-items').find('tr td input.sub_amount').autoNumeric('init',{
          aSep:',',
          aDec:'.'
        });

        //Price
        $('#table-items').find('tr td input.price').autoNumeric('init',{
          aSep:',',
          aDec:'.'
        });

      //Register onkeyup event handler to created inputs : quantity and price
        //quantity
        $('#table-items').find('tr td input.quantity').on('keyup', function(){
          var this_quantity = $(this).val().replace(/,/g, "");
          var this_price_per_unit = $(this).parent().parent().find('.price').val().replace(/,/g, "");
          $(this).parent().parent().find('.sub_amount').autoNumeric('set',this_quantity*this_price_per_unit);
          fill_total_sub_amount();
          update_discount_value_value();
          update_amount_value();
          update_after_discount_value();
        });

        //price
        $('#table-items').find('tr td input.price').on('keyup', function(){
          var this_price_per_unit = $(this).val().replace(/,/g, "");
          var this_quantity = $(this).parent().parent().find('.quantity').val().replace(/,/g, "");
          $(this).parent().parent().find('.sub_amount').autoNumeric('set',this_quantity*this_price_per_unit);
          fill_total_sub_amount();
          update_discount_value_value();
          update_amount_value();
          update_after_discount_value();
        });

      
    });
    //ENDBlock Button add item handling

    //Block register .btn-remove-item event handler [After validation fails from the server]
    $('#table-items').find('tr td button.btn-remove-item').on('click', function(){
      remove_row($(this));
    });
    //ENDBlock register .btn-remove-item event handler [After validation fails from the server]


    //Block function to remove additional row
    function remove_row(obj){
      $(obj).parent().parent().remove();
    }
    //ENDBlock function to remove additional row
    

    //Block Function to fill total_sub_amount
    function fill_total_sub_amount(){
      
      var sum = 0;
      $(".sub_amount").each(function(){
          sum += +$(this).val().replace(/,/g, '');
      });
      $("#total_sub_amount").val(sum);
      $('#total_sub_amount').autoNumeric('update',{
          aSep:',',
          aDec:'.'
      });
    }
    //ENDBlock Function to fill total_sub_amount

    //Block function update amount input value
    function update_amount_value(){
      /*var vat_value = parseFloat(get_vat_value());
      var total_sub_amount_value = parseFloat(get_total_sub_amount_value());
      var real_vat_value = vat_value / parseFloat(100) * total_sub_amount_value;
      var wht_value = parseFloat(get_wht_value());
      total_amount = real_vat_value+total_sub_amount_value+wht_value;
      
      $('#amount').autoNumeric('set', total_amount);*/

      var vat_value = parseFloat(get_vat_value());
      var after_discount_value = parseFloat(get_after_discount_value());
      var real_vat_value = vat_value / parseFloat(100) * after_discount_value;
      var wht_value = parseFloat(get_wht_value());
      total_amount = real_vat_value+after_discount_value+wht_value;
      $('#amount').autoNumeric('set', total_amount);

    }
    //ENDBlock function update amount input value

    //Block register onkeyup event handler to vat and wht input
    $('#vat, #wht').on('keyup', function(){
      update_amount_value();
    });
    //ENDBlock register onkeyup event handler to vat and wht input

    //Block register onkeyup event handler to discount input
    $('#discount').on('keyup', function(){
      update_discount_value_value();
      update_amount_value();
    });
    //ENDBlock register onkeyup event handler to discount input

    //Block update input DISCOUNT VALUE
    function update_discount_value_value(){
      //get the value of #discount field
      var discount = parseFloat(get_discount_value());
      //get the value of #total_sub_amount_value;
      var total_sub_amount_value = parseFloat(get_total_sub_amount_value());
      //set the value of #discount_value
      var discount_value = discount/parseFloat(100)*total_sub_amount_value;
      $('#discount_value').autoNumeric('set', discount_value);
      update_after_discount_value();
    }
    //ENDBlock update input DISCOUNT VALUE

    //Block update input AFTER DISCOUNT value
    function update_after_discount_value(){
      //get the value of #discount_value
      var discount_value = parseFloat(get_discount_value_value());
      //get the_value of #total_sub_amount
      var total_sub_amount_value = parseFloat(get_total_sub_amount_value());
      var after_discount_value = total_sub_amount_value-discount_value;
      $('#after_discount').autoNumeric('set', after_discount_value);
    }
    //ENDBlock update input AFTER DISCOUNT value



    //Block get total_sub_amount_value
    function get_total_sub_amount_value(){
      var result = 0;
      if($('#total_sub_amount').val() == ""){
        result = 0;
      }
      else{
        result = $('#total_sub_amount').val().replace(/,/g, '');
      }
      return result;
    }
    //ENDBlock get total_sub_amount_value

    //Block get VAT value
    function get_vat_value()
    {
      var result = 0;
      if($('#vat').val() == ""){
        result = 0;
      }
      else{
        result = $('#vat').val().replace(/,/g, '');
      }
      return result;
    }
    //ENDBlock get VAT value

    //Block get WHT value
    function get_wht_value()
    {
      var result = 0;
      if($('#wht').val() == ""){
        result = 0;
      }
      else{
        result = $('#wht').val().replace(/,/g, '');
      }
      return result;
    }
    //ENDBlock get WHT value

    //Block get DISCOUNT value
    function get_discount_value()
    {
      var result = 0;
      if($('#discount').val() == ""){
        result = 0;
      }
      else{
        result = $('#discount').val().replace(/,/g, '');
      }
      return result;
    }
    //ENDBlock get DISCOUNT value

    //Block get DISCOUNT VALUE value
    function get_discount_value_value()
    {
      var result = 0;
      if($('#discount_value').val() == ""){
        result = 0;
      }
      else{
        result = $('#discount_value').val().replace(/,/g, '');
      }
      return result;
    }
    //ENDBlock get DISCOUNT VALUE value

    //Block get AFTER DISCOUNT value
    function get_after_discount_value()
    {
      var result = 0;
      if($('#after_discount').val() == ""){
        result = 0;
      }
      else{
        result = $('#after_discount').val().replace(/,/g, '');
      }
      return result;
    }
    //ENDBlock get AFTER DISCOUNT value

    $('#form-edit-purchase-request').on('submit', function(){
      $('#btn-update-purchase-request').prop('disabled', true);
    });
  </script>
@endsection