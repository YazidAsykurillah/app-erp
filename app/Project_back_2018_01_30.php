<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Project;
use App\PurchaseOrderCustomer;
use App\InvoiceCustomer;
use App\InvoiceVendor;
use App\User;
use App\PurchaseRequest;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = ['category','code', 'name', 'purchase_order_customer_id', 'sales_id'];

    protected $appends = ['cost_margin', 'invoiced', 'estimated_cost_margin'];


    public function purchase_order_customer()
    {
    	return $this->belongsTo('App\PurchaseOrderCustomer', 'purchase_order_customer_id');
    }

    //Relation to table user [to get the sales property]
    public function sales()
    {
    	return $this->belongsTo('App\User', 'sales_id');
    }


    public function invoice_customer()
    {
        return $this->hasMany('App\InvoiceCustomer');
    }


    public function purchase_request()
    {
        return $this->hasOne('App\PurchaseRequest');
    }


    public function invoice_vendors()
    {
        return $this->hasMany('App\InvoiceVendor');
    }

    public function internal_requests()
    {
        return $this->hasMany('App\InternalRequest');
    }

    
    //invoice customer due
    public function invoice_customer_due()
    {
        $result = 0;
        //check if project has already invoice
        if($this->invoice_customer->count())
        {
            if($this->purchase_order_customer){
                $po_customer_amount = $this->purchase_order_customer->amount;
                //get sum of the PAID invoice customer based on this project
                $paid_invoice_amount = floatval(\DB::table('invoice_customers')->where('project_id', $this->id)->where('status', 'paid')->sum('amount'));
                $result = $po_customer_amount-$paid_invoice_amount;
                // echo $result;
                // exit();
                return $result;
            }
            
        }
        else{
            if($this->purchase_order_customer){
                $result =  $this->purchase_order_customer->amount;    
            }
        }
        return $result;
    }

    //paid invoice customer
    public function paid_invoice_customer()
    {
        $result = 0;
        if($this->invoice_customer->count()){
            $result = $this->invoice_customer()->where('status','=','paid')->sum('amount');
        }
        return floatval($result);
    }

    //pending invoice customer
    public function pending_invoice_customer()
    {
        $result = 0;
        if($this->invoice_customer->count()){
            $result = $this->invoice_customer()->where('status','=','pending')->sum('amount');
        }
        return floatval($result);
    }


    //total amount of invoice vendor (wheter paid or pending);

    public function  total_amount_invoice_vendor()
    {
        $result = 0;
        //check if project has already invoice vendor
        if($this->invoice_vendors->count())
        {
            $result = floatval(\DB::table('invoice_vendors')->where('project_id', $this->id)->sum('amount'));
           
        }
        return $result;
    }

    //paid invoice vendor
    public function  total_paid_invoice_vendor()
    {
        $result = 0;
        //check if project has already invoice vendor
        if($this->invoice_vendors->count())
        {
            $result = floatval(\DB::table('invoice_vendors')->where('project_id', $this->id)->where('status', 'paid')->sum('amount'));
           
        }
        return $result;
    }


    //total amount internal request (pending or approved)
    public function total_amount_internal_request()
    {
        $result = 0;
        //check if project has already internal request
        if($this->internal_requests->count())
        {
            $result = floatval(\DB::table('internal_requests')->where('project_id', $this->id)->sum('amount'));
           
        }
        return $result;
    }

    //total approved internal request
    public function total_approved_internal_request()
    {
        $result = 0;
        //check if project has already internal request
        if($this->internal_requests->count())
        {
            $result = floatval(\DB::table('internal_requests')->where('project_id', $this->id)->where('status', 'approved')->sum('amount'));
           
        }
        return $result;
    }


    

    public function scopeCostMargin($query)
    {
        return $query->where('cost_margin', '>', 100);
    }

    protected function total_expense_from_settlement()
    {
        $total_expense_from_settlement = 0;
        //check if project has already settlement
        if($this->internal_requests->count())
        {
           $settlement_amount_array = [];
           $settlement_adder_array = [];
           $settlement_subtracter_array = [];
           foreach($this->internal_requests as $internal_request){
            if(count($internal_request->settlement)){
                if($internal_request->settlement->status == 'approved' || $internal_request->settlement->status == 'pending'){
                    //$settlement_amount_array[] = $internal_request->amount - $internal_request->settlement->amount;
                    if($internal_request->amount - $internal_request->settlement->amount < 0){
                        $settlement_adder_array[] = abs($internal_request->amount - $internal_request->settlement->amount);
                    }
                    if($internal_request->amount - $internal_request->settlement->amount > 0){
                        $settlement_subtracter_array[] = $internal_request->amount - $internal_request->settlement->amount;
                    }
                }
            }

           }
           //$total_expense_from_settlement = array_sum($settlement_amount_array);
           $total_settlement_adder = array_sum($settlement_adder_array);
           $total_settlement_subtracter = array_sum($settlement_subtracter_array);
           //exit($total_settlement_subtracter);
           $total_expense_from_settlement = $total_settlement_adder - $total_settlement_subtracter;
           
        }
        return $total_expense_from_settlement;
    }

    protected function total_expense_from_pending_settlement()
    {
        $total_expense_from_pending_settlement = 0;
        //check if project has already settlement
        if($this->internal_requests->count())
        {
           $settlement_amount_array = [];
           $settlement_adder_array = [];
           $settlement_subtracter_array = [];
           foreach($this->internal_requests as $internal_request){
            if(count($internal_request->settlement)){
                if($internal_request->settlement->status == 'pending'){
                    //$settlement_amount_array[] = $internal_request->amount - $internal_request->settlement->amount;
                    if($internal_request->amount - $internal_request->settlement->amount < 0){
                        $settlement_adder_array[] = abs($internal_request->amount - $internal_request->settlement->amount);
                    }
                    if($internal_request->amount - $internal_request->settlement->amount > 0){
                        $settlement_subtracter_array[] = $internal_request->amount - $internal_request->settlement->amount;
                    }
                }
            }

           }
           //$total_expense_from_pending_settlement = array_sum($settlement_amount_array);
           $total_settlement_adder = array_sum($settlement_adder_array);
           $total_settlement_subtracter = array_sum($settlement_subtracter_array);
           //exit($total_settlement_subtracter);
           $total_expense_from_pending_settlement = $total_settlement_adder - $total_settlement_subtracter;
           
        }
        return $total_expense_from_pending_settlement;
    }

    public function getCostMarginAttribute()
    {
        $total_expenses = $this->total_amount_invoice_vendor() + $this->total_amount_internal_request() + $this->total_expense_from_settlement();
        $purchase_order_customer_amount = ($this->purchase_order_customer ? $this->purchase_order_customer->amount : 1);
        $purchase_order_customer_amount_per_ppn = $purchase_order_customer_amount/1.1;
        if($purchase_order_customer_amount_per_ppn == 0){
            $purchase_order_customer_amount_per_ppn = 1;
        }
        $cost_margin = 100 - ($total_expenses / $purchase_order_customer_amount_per_ppn * 100);
        return $cost_margin;
    }

    public function getEstimatedCostMarginAttribute()
    {

        
        /*$total_expenses = $this->total_amount_invoice_vendor() + $this->total_amount_internal_request() + $this->total_expense_from_settlement();
        $purchase_order_customer_amount = ($this->purchase_order_customer ? $this->purchase_order_customer->amount : 1);
        $purchase_order_customer_amount_per_ppn = $purchase_order_customer_amount/1.1;
        if($purchase_order_customer_amount_per_ppn == 0){
            $purchase_order_customer_amount_per_ppn = 1;
        }
        $cost_margin = 100 - ($total_expenses / $purchase_order_customer_amount_per_ppn * 100);
        return $cost_margin;*/
        $sum_po_vendor_array = 0;
        if($this->invoice_vendors->count())
        {   
            $po_vendor_amount = 0;
            $po_vendor_array = [];
            foreach($this->invoice_vendors as $invoice_vendor){
                $po_vendor_array[]=$invoice_vendor->purchase_order_vendor->amount;
            }
            $unique_po_vendor_array = array_unique($po_vendor_array);
            $sum_po_vendor_array = array_sum($po_vendor_array);
        }

        $total_expenses = $sum_po_vendor_array + $this->total_amount_internal_request() + $this->total_expense_from_settlement();
        $purchase_order_customer_amount = ($this->purchase_order_customer ? $this->purchase_order_customer->amount : 1);
        $purchase_order_customer_amount_per_ppn = $purchase_order_customer_amount/1.1;
        if($purchase_order_customer_amount_per_ppn == 0){
            $purchase_order_customer_amount_per_ppn = 1;
        }
        $cost_margin = 100 - ($total_expenses / $purchase_order_customer_amount_per_ppn * 100);
        return abs($cost_margin);
    }

    public function getInvoicedAttribute()
    {
        $total_paid_invoice = $this->paid_invoice_customer();
        $total_pending_invoice = $this->pending_invoice_customer();
        $total_invoice_due = $this->invoice_customer_due();
        
        $invoiced = "";
        //check if this project has PO Customer
        if($this->purchase_order_customer){
            $po_customer_amount = $this->purchase_order_customer->amount;
            if($po_customer_amount == 0){
                $po_customer_amount = 1;
            }
            $invoiced = round(($total_paid_invoice+$total_pending_invoice)/$po_customer_amount* 100, 2);
        }else{
            $invoiced = 0;
        }
        
        return $invoiced;
    }
    
}
