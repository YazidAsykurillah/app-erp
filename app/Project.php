<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Project;
use App\PurchaseOrderCustomer;
use App\PurchaseOrderVendor;
use App\InvoiceCustomer;
use App\InvoiceVendor;
use App\User;
use App\PurchaseRequest;
use App\Ets;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = ['category','code', 'name', 'purchase_order_customer_id', 'sales_id', 'enabled', 'created_at'];

    protected $appends = ['cost_margin', 'invoiced', 'estimated_cost_margin', 'customer', 'man_hour_information'];


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


    public function purchase_order_vendors()
    {
        return $this->hasManyThrough('App\PurchaseOrderVendor', 'App\PurchaseRequest');
    }

    public function purchase_order_vendors_with_ppn()
    {
        return $this->hasManyThrough('App\PurchaseOrderVendor', 'App\PurchaseRequest')
            ->where('purchase_order_vendors.vat','>',0);
    }

    public function purchase_order_vendors_without_ppn()
    {
        return $this->hasManyThrough('App\PurchaseOrderVendor', 'App\PurchaseRequest')
            ->where('purchase_order_vendors.vat','=',0);
    }
    
    //invoice customer due
    public function invoice_customer_due()
    {
        $result = 0;
        //initial check if this project is completed simply return 0;
        if($this->is_completed == TRUE){
            return 0;
        }
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
        if($this->is_completed == TRUE){
            return 0;
        }
        if($this->invoice_customer->count()){
            $result = $this->invoice_customer()->where('status','=','pending')->sum('amount');
        }
        return floatval($result);
    }


    //total amount of invoice vendor (wheter paid or pending);

    //WITH PPN
    public function  total_amount_invoice_vendor()
    {
        $result = 0;
        //check if project has already invoice vendor
        if($this->invoice_vendors->count())
        {
            $result = floatval(\DB::table('invoice_vendors')
                ->where('project_id', $this->id)
                ->sum('amount'));
           
        }
        return $result;
    }

    //with PPN
    public function  total_amount_invoice_vendor_with_ppn()
    {
        $result = 0;
        //check if project has already invoice vendor
        if($this->invoice_vendors->count())
        {
            $result = floatval(\DB::table('invoice_vendors')
                ->where('project_id', $this->id)
                ->where('vat','!=',0)
                ->sum('amount'));
           
        }
        return $result;
    }
    //without PPN
    public function  total_amount_invoice_vendor_without_ppn()
    {
        $result = 0;
        //check if project has already invoice vendor
        if($this->invoice_vendors->count())
        {
            $result = floatval(\DB::table('invoice_vendors')
                ->where('project_id', $this->id)
                ->where('vat',0)
                ->sum('amount'));
           
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
            if($internal_request->settlement){
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
        /*
        //freezed at 2017-04-17
        $total_expenses = $this->total_amount_invoice_vendor() + $this->total_amount_internal_request() + $this->total_expense_from_settlement();
        $purchase_order_customer_amount = ($this->purchase_order_customer ? $this->purchase_order_customer->amount : 1);
        $purchase_order_customer_amount_per_ppn = $purchase_order_customer_amount/1.1;
        if($purchase_order_customer_amount_per_ppn == 0){
            $purchase_order_customer_amount_per_ppn = 1;
        }
        $cost_margin = 100 - ($total_expenses / $purchase_order_customer_amount_per_ppn * 100);
        return $cost_margin;
        */
        $cost_margin = NULL;
        if($this->category == 'internal'){
            
        }else{
            $total_expenses = $this->total_amount_invoice_vendor_without_ppn()+$this->total_amount_invoice_vendor_with_ppn()+ $this->total_amount_internal_request() + $this->total_expense_from_settlement();
            $purchase_order_customer_amount = ($this->purchase_order_customer ? $this->purchase_order_customer->amount : 1);
            $purchase_order_customer_amount = $purchase_order_customer_amount;
            if($purchase_order_customer_amount == 0){
                $purchase_order_customer_amount = 1;
            }
            $cost_margin = 100 - ($total_expenses / $purchase_order_customer_amount * 100);
        }
        
        return $cost_margin;

    }

    

    public function getEstimatedCostMarginAttribute()
    {

        $sum_po_vendor_array = 0;
        $sum_po_vendor_amount = 0;
        $sum_po_vendor_amount_with_ppn = $this->purchase_order_vendors_with_ppn->sum('amount');
        $sum_po_vendor_amount_without_ppn = $this->purchase_order_vendors_without_ppn->sum('amount');
        $sum_po_vendor_amount = $sum_po_vendor_amount_with_ppn+$sum_po_vendor_amount_without_ppn;
        
       /* $purchase_requests = \DB::table('purchase_requests')->select('id')->where('project_id', $this->id)->get();
        
        if(count($purchase_requests)){
            foreach($purchase_requests as $purchase_request){
                $sum_po_vendor_amount+=PurchaseRequest::find($purchase_request->id)->amount;
            }
        }*/

        
        $total_expenses = $sum_po_vendor_amount + $this->total_amount_internal_request() + $this->total_expense_from_settlement();
        $purchase_order_customer_amount = ($this->purchase_order_customer ? $this->purchase_order_customer->amount : 1);
        $purchase_order_customer_amount_per_ppn = $purchase_order_customer_amount;
        if($purchase_order_customer_amount_per_ppn == 0){
            $purchase_order_customer_amount_per_ppn = 1;
        }
        $cost_margin = 100 - ($total_expenses / $purchase_order_customer_amount_per_ppn * 100);
        return abs($cost_margin);
    }


   

    public function getInvoicedAttribute()
    {
        if($this->is_completed == TRUE){
            return 100;
        }
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
    
    public function getCustomerAttribute()
    {
        if($this->purchase_order_customer){
            return $this->purchase_order_customer->customer;
        }
    }

    //return man hour information,
    //collect from ets model
    public function getManHourInformationAttribute()
    {
        //project_number
        $code = $this->code;
        
        //get related ets
        $ets = Ets::where('type','site')
                ->where('project_number','LIKE',"%$code%");
        

        $total_normal = $ets->sum('normal');
        $total_I = $ets->sum('I');
        $total_II = $ets->sum('II');
        $total_III = $ets->sum('III');
        $total_IV = $ets->sum('IV');
        $grand_total = $total_normal+$total_I+$total_II+$total_III+$total_IV;

        $man_hour_information = [
            'total_normal'=>$total_normal,
            'total_I'=>$total_I,
            'total_II'=>$total_II,
            'total_III'=>$total_III,
            'total_IV'=>$total_IV,
            'grand_total'=>$grand_total
        ];
        return collect($man_hour_information)->toJson();
    }

    public static function generateManHourCost($project_code = NULL)
    {
        $result = NULL;
        if($project_code!=NULL){
            $ets_list = Ets::where('type','site')
                ->where('project_number','LIKE',"%$project_code%");
            if($ets_list->count()){
                foreach($ets_list->get() as $ets){
                    $user_id = $ets->user_id;
                    $rate = User::findOrFail($user_id)->man_hour_rate;
                    $transport = User::findOrFail($user_id)->transportation_allowance;
                    $allowance = User::findOrFail($user_id)->eat_allowance;

                    $normal = $ets->normal;
                    $I= $ets->I;
                    $II= $ets->II;
                    $III= $ets->III;
                    $IV= $ets->IV;
                    $total_manhour = ($normal*1)+($I*1.5)+($II*2)+($III*3)+($IV*4);
                    
                    $total_cost = ($rate*$total_manhour)+$transport+$allowance;

                    $ets = Ets::findOrFail($ets->id);
                    $ets->total_manhour = $total_manhour;
                    $ets->rate = $rate;
                    $ets->transport = $transport;
                    $ets->allowance = $allowance;
                    $ets->total_cost = $total_cost;
                    $ets->save();
                }
            }
            
        }
        return $result;

    }

    public static function getTotalManhorCost($project_code = NULL)
    {
        
        if($project_code !=NULL){
            $result = 0;
            //get related ets
            $ets = Ets::where('type','site')
                ->where('project_number','LIKE',"%$project_code%");
            if($ets->count()){
                $result = $ets->sum('total_cost');
            }
            return number_format($result);
        }
    }
}
