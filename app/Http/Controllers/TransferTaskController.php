<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Event;
use Carbon\Carbon;
use App\Events\TransferInvoiceVendor;


use App\Period;
use App\InternalRequest;
use App\InvoiceVendor;
use App\InvoiceVendorTax;
use App\Settlement;
use App\Cashbond;
use App\CashbondInstallment;
use App\Cash;
use App\Transaction;
use App\TheLog;
use App\Payroll;

class TransferTaskController extends Controller
{

    public function internal_request()
    {
    	return view('transfer-task.internal_request');
    }

    public function transferInternalRequest(Request $request){

    	$internal_request = InternalRequest::findOrFail($request->internal_request_id_to_transfer);
        if($internal_request->type == 'pindah_buku'){
            return $this->transferInternalRequestPindahBuku($internal_request);
        }
        else{
            //regiter to transaction
            $this->register_transaction_from_internal_request($internal_request);

            //update accounted status
            $internal_request->accounted = TRUE;
            $internal_request->transaction_date = date('Y-m-d');
            $internal_request->save();

            //register to the_logs table;
            $log_description = "Transfered to requester";
            $log = $this->register_to_the_logs('internal_request', 'update', $internal_request->id, $log_description );
            return redirect()->back()
                ->with('successMessage', "$internal_request->code has been transfered");
        }
        
    }





    public function transferInternalRequestMultiple(Request $request)
    {
        $internal_request_multiple = $request->internal_request_multiple;
        
        /*print_r($internal_request_multiple);
        exit();*/

        $count = 0;
        foreach($internal_request_multiple as $ir){
            $internal_request = InternalRequest::findOrFail($ir);
            
            if($internal_request->type == 'pindah_buku'){
                $this->transferInternalRequestPindahBuku($internal_request);
            }
            else{
                if($internal_request->accounted_approval == 'approved'){
                    $count++;
                    //regiter to transaction
                    $this->register_transaction_from_internal_request($internal_request);

                    //update accounted status
                    $internal_request->accounted = TRUE;
                    $internal_request->transaction_date = date('Y-m-d');
                    $internal_request->save();

                    //register to the_logs table;
                    $log_description = "Transfered to requester";
                    $log = $this->register_to_the_logs('internal_request', 'update', $ir, $log_description );
                }
                
                
            }
        }
        return redirect()->back()
            ->with('successMessage', "$count internal request(s) has been transfered");
    }

    protected function transferInternalRequestPindahBuku($internal_request)
    {
        //register transaction for the bank source // the debet

        $transaction_for_source = $this->transaction_for_source($internal_request);
        //register transaction for the bank target // the Credit
        $transaction_for_target = $this->transaction_for_target($internal_request);

        //transaction is done, now update the internal request it self
        $internal_request->accounted = TRUE;
        $internal_request->transaction_date = date('Y-m-d');
        $internal_request->settled = TRUE;
        $internal_request->save();
        return redirect()->back()
                ->with('successMessage', "$internal_request->code has been transfered");

    }

    protected function transaction_for_source($internal_request)
    {
        //get the cash model
        $cash = Cash::find($internal_request->remitter_bank_id);

        $transaction = new Transaction;
        $transaction->cash_id = $internal_request->remitter_bank_id;
        $transaction->refference = 'internal_request';
        $transaction->refference_id = $internal_request->id;
        $transaction->refference_number = $internal_request->code;
        $transaction->type = 'debet';
        $transaction->amount = $internal_request->amount;
        $transaction->notes = $internal_request->description;
        $transaction->transaction_date = date('Y-m-d');
        $transaction->reference_amount = $cash->amount - $internal_request->amount;
        $transaction->save();

        //now fix the cash amount,
        if($cash){
            $cash->amount = $cash->amount - $internal_request->amount;
            $cash->save();
        }
    }

    protected function transaction_for_target($internal_request)
    {

        $cash = Cash::find($internal_request->bank_target_id);

        $transaction = new Transaction;
        $transaction->cash_id = $internal_request->bank_target_id;
        $transaction->refference = 'internal_request';
        $transaction->refference_id = $internal_request->id;
        $transaction->refference_number = $internal_request->code;
        $transaction->type = 'credit';
        $transaction->amount = $internal_request->amount;
        $transaction->notes = $internal_request->description;
        $transaction->transaction_date = date('Y-m-d');
        $transaction->reference_amount = $cash->amount + $internal_request->amount;
        $transaction->save();

        //now fix the cash amount id,
        
        if($cash){
            $cash->amount = $cash->amount + $internal_request->amount;
            $cash->save();
        }
    }


    public function approveInternalRequest(Request $request)
    {
        $internal_request = InternalRequest::findOrFail($request->internal_request_id_to_approve);
        $internal_request->remitter_bank_id = $request->remitter_bank_id;
        $internal_request->beneficiary_bank_id = $request->beneficiary_bank_id;
        $internal_request->accounted_approval = 'approved';
        $internal_request->save();
        return redirect()->back()
            ->with('successMessage', "Internal Request $internal_request->code has been approved to be transfered");
    }

    public function approveInternalRequestMultiple(Request $request)
    {
        $internal_request_multiple = $request->internal_request_multiple;
        $force_transfer = $request->force_transfer;
        
        $approved = 0;
        $transfered = 0;

        if($force_transfer == 'on'){
            //first approve them
            foreach($internal_request_multiple as $internal_request){
                $ir = InternalRequest::findOrFail($internal_request);
                if($ir->accounted_approval == 'pending'){
                    $approved++;
                    $ir->remitter_bank_id = $request->remitter_bank_id_multiple;
                    $ir->beneficiary_bank_id = $request->beneficiary_bank_id_multiple;
                    $ir->accounted_approval = 'approved';
                    $ir->save();

                    //register to the_logs table;
                    $log_description = "approved to be registered to transfer task";
                    $log = $this->register_to_the_logs('internal_request', 'update', $internal_request, $log_description );

                    //now run the force transfer
                    $ft_internal_request = $this->forceTransferInternalRequest($ir);
                    if($ft_internal_request == TRUE){
                        $transfered++;
                    }
                }

            }
        }else{
            foreach($internal_request_multiple as $internal_request){
                $ir = InternalRequest::findOrFail($internal_request);
                if($ir->accounted_approval == 'pending'){
                    $approved++;
                    $ir->remitter_bank_id = $request->remitter_bank_id_multiple;
                    $ir->beneficiary_bank_id = $request->beneficiary_bank_id_multiple;
                    $ir->accounted_approval = 'approved';
                    $ir->save();

                    //register to the_logs table;
                    $log_description = "approved to be registered to transfer task";
                    $log = $this->register_to_the_logs('internal_request', 'update', $internal_request, $log_description );
                }
               
            }    
        }
        
        return redirect()->back()
            ->with('successMessage', "$approved has been approved, $transfered has been transfered");
    }

    protected function forceTransferInternalRequest($obj = NULL){
        
        if($obj!=NULL){
            $internal_request = $obj;
            if($internal_request->type == 'pindah_buku'){
                //Temporary, disable pindah buku
                //return $this->transferInternalRequestPindahBuku($internal_request);
                return TRUE;
            }
            else{
                //regiter to transaction
                $this->register_transaction_from_internal_request($internal_request);

                //update accounted status
                $internal_request->accounted = TRUE;
                $internal_request->transaction_date = date('Y-m-d');
                $internal_request->save();

                //register to the_logs table;
                $log_description = "Transfered to requester";
                $log = $this->register_to_the_logs('internal_request', 'update', $internal_request->id, $log_description );
                return TRUE;
            }
        }
        return TRUE;
    }

    protected function register_transaction_from_internal_request($internal_request)
    {
        //get the cash model
        $cash = Cash::find($internal_request->remitter_bank_id);


        $transaction = new Transaction;
        $transaction->cash_id = $internal_request->remitter_bank_id;
        $transaction->refference = 'internal_request';
        $transaction->refference_id = $internal_request->id;
        $transaction->refference_number = $internal_request->code;
        $transaction->type = 'debet';
        $transaction->amount = $internal_request->amount;
        $transaction->transaction_date = date('Y-m-d');
        $transaction->notes = $internal_request->description;

        //reference amount is taken from the operation result between curren cash amount, transaction type and the transaction amount it self
        //since it comes from internal request, it is always subtracting the cash amount
        $transaction->reference_amount = $cash->amount - $internal_request->amount;
        $transaction->save();

        //now fix the cash amount id,
        
        if($cash){
            $cash->amount = $cash->amount - $internal_request->amount;
            $cash->save();
        }

    }


    public function approveInternalRequestPindahBuku(Request $request)
    {
        $internal_request = InternalRequest::findOrFail($request->internal_request_pindah_buku_id_to_approve);
        //remittter_bank_id is bank_source_id input
        $internal_request->remitter_bank_id = $request->bank_source_id;
        $internal_request->bank_target_id = $request->bank_target_id;
        $internal_request->accounted_approval = 'approved';
        $internal_request->save();
        return redirect()->back()
            ->with('successMessage', "Internal Request $internal_request->code has been approved to be transfered");
    }


    //return transfer task invoice vendor lists page
    public function invoice_vendor(Request $request)
    {
        $filter = $request->type;
        return view('transfer-task.invoice_vendor')
        ->with('filter', $filter);
    }

    

    public function approveInvoiceVendor(Request $request)
    {
        $invoice_vendor = InvoiceVendor::findOrFail($request->invoice_vendor_id_to_approve);
        //remittter_bank_id is bank_source_id input
        $invoice_vendor->cash_id = $request->remitter_bank_id;
        $invoice_vendor->accounted_approval = 'approved';
        $invoice_vendor->save();

        //register to the_logs table;
        $log_description = "approved invoice vendor to be registered to transfer task";
        $log = $this->register_to_the_logs('invoice_vendor', 'update', $request->invoice_vendor_id_to_approve, $log_description );

        return redirect()->back()
            ->with('successMessage', "Internal Request $invoice_vendor->code has been approved to be transfered");
    }


    public function approveInvoiceVendorMultiple(Request $request)
    {

        $force_transfer = $request->force_transfer;
        
        $invoice_vendor_multiple = $request->invoice_vendor_multiple;

        if($force_transfer == 'on'){
            //first, approve them
            foreach($invoice_vendor_multiple as $invoice_vendor){
                $iv = InvoiceVendor::findOrFail($invoice_vendor);
                if($iv->accounted_approval == 'pending'){
                    $iv->cash_id = $request->remitter_bank_id_multiple;
                    $iv->accounted_approval = 'approved';
                    $iv->save();
                    //register to the_logs table;
                    $log_description = "approved invoice vendor to be registered to transfer task";
                    $log = $this->register_to_the_logs('invoice_vendor', 'update', $invoice_vendor, $log_description );
                }
               
            }
            //now run force transfer
            $this->forceTransferInvoiceVendorMultiple($invoice_vendor_multiple);
            return redirect()->back()
            ->with('successMessage', "Invoice vendor(s) has been force transfered");
        }
        else{
            $count = count($invoice_vendor_multiple);
            foreach($invoice_vendor_multiple as $invoice_vendor){
                $iv = InvoiceVendor::findOrFail($invoice_vendor);
                if($iv->accounted_approval == 'pending'){
                    $iv->cash_id = $request->remitter_bank_id_multiple;
                    $iv->accounted_approval = 'approved';
                    $iv->save();
                    //register to the_logs table;
                    $log_description = "approved invoice vendor to be registered to transfer task";
                    $log = $this->register_to_the_logs('invoice_vendor', 'update', $invoice_vendor, $log_description );
                }
               
            }
            return redirect()->back()
            ->with('successMessage', "$count Invoice Vendor(s) has been approved to be transfered");
        }
       
        
        
    }


    protected function forceTransferInvoiceVendorMultiple($forced_invoice_vendors = array())
    {
        $invoice_vendor_multiple = $forced_invoice_vendors;
        
        $count = 0;
        foreach($invoice_vendor_multiple as $iv){
            $invoice_vendor = InvoiceVendor::findOrFail($iv);
            // transaction registration
            
            if($invoice_vendor->accounted_approval =='approved' && $invoice_vendor->cash_id!=NULL){
                /*echo $invoice_vendor->code;
                echo '</br>';*/
                $transaction_registration = $this->register_transaction_from_invoice_vendor($invoice_vendor);
                if($transaction_registration == TRUE){
                    $count++;
                    //Block register to tax lists
                    if($invoice_vendor->vat !=0){
                        $this->register_to_tax_list_from_vat($invoice_vendor);
                    }
                    if($invoice_vendor->wht_amount !=0){
                        $this->register_to_tax_list_from_wht($invoice_vendor);
                    }
                    //ENDBlock register to tax lists

                    //set status to paid and accounted status of invoice vendor to TRUE;
                    $invoice_vendor->status = 'paid';
                    $invoice_vendor->accounted = TRUE;
                    $invoice_vendor->save();

                    //register to the_logs table;
                    $log_description = "Transfered to vendor";
                    $log = $this->register_to_the_logs('invoice_vendor', 'update', $iv, $log_description );

                    //Fire the event transver invoice vendor
                    Event::fire(new TransferInvoiceVendor($invoice_vendor));
                    
                   
                }
                
            }
            
        }
        
        
    }

    public function transferInvoiceVendor(Request $request)
    {
        $invoice_vendor = InvoiceVendor::findOrFail($request->invoice_vendor_id_to_transfer);
        
        // transaction registration
        $transaction_registration = $this->register_transaction_from_invoice_vendor($invoice_vendor);
        
        if($transaction_registration == TRUE){

            //Block register to tax lists
            if($invoice_vendor->vat !=0){
                $this->register_to_tax_list_from_vat($invoice_vendor);
            }
            if($invoice_vendor->wht_amount !=0){
                $this->register_to_tax_list_from_wht($invoice_vendor);
            }
            //ENDBlock register to tax lists

            //set status to paid and accounted status of invoice vendor to TRUE;
            $invoice_vendor->status = 'paid';
            $invoice_vendor->accounted = TRUE;
            $invoice_vendor->save();

            //register to the_logs table;
            $log_description = "Transfered to vendor";
            $log = $this->register_to_the_logs('invoice_vendor', 'update', $request->invoice_vendor_id_to_transfer, $log_description );

            //Fire the event transver invoice vendor
            Event::fire(new TransferInvoiceVendor($invoice_vendor));
            
            return redirect()->back()
                ->with('successMessage', "Invoice vendor has $invoice_vendor->code has been transfered");
        }
        return abort(500);
        
        
    }

    public function transferInvoiceVendorMultiple(Request $request)
    {
        $invoice_vendor_multiple = $request->invoice_vendor_multiple;
        $count = 0;
        foreach($invoice_vendor_multiple as $iv){
            $invoice_vendor = InvoiceVendor::findOrFail($iv);
            // transaction registration
            
            if($invoice_vendor->accounted_approval =='approved' && $invoice_vendor->cash_id!=NULL){
                /*echo $invoice_vendor->code;
                echo '</br>';*/
                $transaction_registration = $this->register_transaction_from_invoice_vendor($invoice_vendor);
                if($transaction_registration == TRUE){
                    $count++;
                    //Block register to tax lists
                    if($invoice_vendor->vat !=0){
                        $this->register_to_tax_list_from_vat($invoice_vendor);
                    }
                    if($invoice_vendor->wht_amount !=0){
                        $this->register_to_tax_list_from_wht($invoice_vendor);
                    }
                    //ENDBlock register to tax lists

                    //set status to paid and accounted status of invoice vendor to TRUE;
                    $invoice_vendor->status = 'paid';
                    $invoice_vendor->accounted = TRUE;
                    $invoice_vendor->save();

                    //register to the_logs table;
                    $log_description = "Transfered to vendor";
                    $log = $this->register_to_the_logs('invoice_vendor', 'update', $iv, $log_description );

                    //Fire the event transver invoice vendor
                    Event::fire(new TransferInvoiceVendor($invoice_vendor));
                    
                   
                }
                
            }
            
        }
        return redirect()->back()
            ->with('successMessage', "$count Invoice vendor(s) has been transfered");
        
    }


    protected function register_to_tax_list_from_vat($invoice_vendor){
        $invoice_vendor_tax = new InvoiceVendorTax;
        $invoice_vendor_tax->tax_number = $invoice_vendor->tax_number;
        $invoice_vendor_tax->invoice_vendor_id = $invoice_vendor->id;
        $invoice_vendor_tax->source = 'vat';
        $invoice_vendor_tax->percentage = $invoice_vendor->vat;
        $invoice_vendor_tax->amount = $invoice_vendor->vat_amount;
        $invoice_vendor_tax->save();
    }

     protected function register_to_tax_list_from_wht($invoice_vendor){
        $invoice_vendor_tax = new InvoiceVendorTax;
        $invoice_vendor_tax->tax_number = $invoice_vendor->tax_number;
        $invoice_vendor_tax->invoice_vendor_id = $invoice_vendor->id;
        $invoice_vendor_tax->source = 'wht';
        $invoice_vendor_tax->amount = $invoice_vendor->wht_amount;
        $invoice_vendor_tax->save();
    }

    protected function register_transaction_from_invoice_vendor($invoice_vendor)
    {
        $cash = Cash::find($invoice_vendor->cash_id);

        $transaction = new Transaction;
        $transaction->cash_id = $invoice_vendor->cash_id;
        $transaction->refference = 'invoice_vendor';
        $transaction->refference_id = $invoice_vendor->id;
        $transaction->refference_number = $invoice_vendor->code;
        $transaction->type = 'debet';
        $transaction->amount = $invoice_vendor->amount;
        $transaction->notes = "";
        $transaction->transaction_date = date('Y-m-d');
        $transaction->accounting_expense_id = 4;
        $transaction->reference_amount = $cash->amount - $invoice_vendor->amount;
        $transaction->save();

        //now fix the cash amount id,
        
        if($cash){
            $cash->amount = $cash->amount - $invoice_vendor->amount;
            $cash->save();
        }
        return TRUE;
    }


    //loging method
    protected function register_to_the_logs($source = NULL,  $mode = NULL, $refference_id = NULL, $description = NULL)
    {
        $the_log = new TheLog;
        $the_log->source = $source;
        $the_log->mode = $mode;
        $the_log->refference_id = $refference_id;
        $the_log->user_id = \Auth::user()->id;
        $the_log->description = $description;
        $the_log->save();
       
    }



    //return transfer task settlement lists page
    public function settlement()
    {
        return view('transfer-task.settlement');
    }


    public function approveSettlement(Request $request)
    {
        $settlement = Settlement::findOrFail($request->settlement_id_to_approve);
        
        //remittter_bank_id
        $settlement->remitter_bank_id = $request->remitter_bank_id;
        $settlement->accounted_approval = 'approved';
        $settlement->save();

        //register to the_logs table;
        $log_description = "approved to be registered to transfer task";
        $log = $this->register_to_the_logs('settlement', 'update', $request->settlement_id_to_approve, $log_description );

        return redirect()->back()
            ->with('successMessage', "Settlement $settlement->code has been approved to be transfered");
    }


    public function approveSettlementMultiple(Request $request)
    {
        
        $settlement_multiple = $request->settlement_multiple;
        $force_transfer = $request->force_transfer;
        $approved = 0;
        $transfered = 0;
        if($force_transfer == 'on'){
            foreach($settlement_multiple as $item){
                $settlement = Settlement::findOrFail($item);
                if($settlement->accounted_approval == 'pending'){
                    
                    //first, set the accounted approval to be approved
                    
                    $settlement->remitter_bank_id = $request->remitter_bank_id_multiple;
                    $settlement->accounted_approval = 'approved';
                    $settlement->save();

                    //register to the_logs table with approved information;
                    $log_description = "approved to be registered to transfer task";
                    $log = $this->register_to_the_logs('settlement', 'update', $item, $log_description );
                    $approved++;
                    
                    //now force transferd them
                    $force_transfer = $this->forceTransferSettlement($settlement);
                    if($force_transfer == TRUE){
                        $transfered++;
                    }


                }
                
            }
        }else{
            foreach($settlement_multiple as $item){
                $settlement = Settlement::findOrFail($item);
                if($settlement->accounted_approval == 'pending'){
                    //remittter_bank_id
                    $settlement->remitter_bank_id = $request->remitter_bank_id_multiple;
                    $settlement->accounted_approval = 'approved';
                    $settlement->save();

                    //register to the_logs table;
                    $log_description = "approved to be registered to transfer task";
                    $log = $this->register_to_the_logs('settlement', 'update', $item, $log_description );
                    $approved++;
                }
                
            }
        }
        return redirect()->back()
            ->with('successMessage', "$approved approved, $transfered transfered");
    }


    public function transferSettlement(Request $request)
    {
        $settlement = Settlement::findOrFail($request->settlement_id_to_transfer);
        // transaction registration
        $transaction_registration = $this->register_transaction_from_settlement($settlement);
        if($transaction_registration == TRUE){
            //set accounted status of settlement to TRUE;
            
            $settlement->accounted = TRUE;
            $settlement->save();

            //register to the_logs table;
            $log_description = "Transfered";
            $log = $this->register_to_the_logs('settlement', 'update', $request->settlement_id_to_transfer, $log_description );

            return redirect()->back()
                ->with('successMessage', "$settlement->code has been transfered");
        }
        return abort(500);
        
    }


    public function transferSettlementMultiple(Request $request)
    {
        $transfered = 0;
        if(count($request->settlement_multiple)){
            foreach($request->settlement_multiple as $sm){
                $settlement = Settlement::findOrFail($sm);
                if($settlement->accounted == 0){
                    $transfered++;
                    // transaction registration
                    $transaction_registration = $this->register_transaction_from_settlement($settlement);
                    if($transaction_registration == TRUE){
                        //set accounted status of settlement to TRUE;
                        
                        $settlement->accounted = TRUE;
                        $settlement->save();

                        //register to the_logs table;
                        $log_description = "Transfered";
                        $log = $this->register_to_the_logs('settlement', 'update', $sm, $log_description );
                    }
                }
            }
        }
        
        return redirect()->back()
            ->with('successMessage', "$transfered has been transfered");
        
    }

    protected function forceTransferSettlement($obj = NULL)
    {
        if($obj != NULL){
            $settlement = $obj;
            // transaction registration
            $transaction_registration = $this->register_transaction_from_settlement($settlement);
            if($transaction_registration == TRUE){
                //set accounted status of settlement to TRUE;
                
                $settlement->accounted = TRUE;
                $settlement->save();

                //register to the_logs table;
                $log_description = "Transfered";
                $log = $this->register_to_the_logs('settlement', 'update', $settlement->id, $log_description );

                return TRUE;
            }
            return abort(500);
        }
    }

    protected function register_transaction_from_settlement($settlement)
    {
        $cash = Cash::find($settlement->remitter_bank_id);

        $transaction = new Transaction;
        $transaction->cash_id = $settlement->remitter_bank_id;
        $transaction->refference = 'settlement';
        $transaction->refference_id = $settlement->id;
        $transaction->refference_number = $settlement->code;
        $transaction->notes = $settlement->description;
        $transaction->transaction_date = date('Y-m-d');
        $balance = $settlement->internal_request->amount - $settlement->amount;

        if($balance > 0){
            $transaction->type = 'credit';    
        }else{
            $transaction->type = 'debet';
        }
        
        //count balance to be transfered
        $transaction->amount = abs($balance);
        if($balance > 0){
            $transaction->reference_amount = $cash->amount + abs($balance);
        }else{
            $transaction->reference_amount = $cash->amount - abs($balance);
        }
        
        $transaction->save();

        //now fix the cash amount id,
        
        if($balance > 0){
            $cash->amount = $cash->amount + abs($balance);
            $cash->save();
        }
        else{
            $cash->amount = $cash->amount - abs($balance);
            $cash->save();
        }
        return TRUE;
    }


    //return transfer task cashbond lists page
    public function cashbond()
    {
        return view('transfer-task.cashbond');
    }


    public function approveCashbond(Request $request)
    {
        $cashbond = Cashbond::findOrFail($request->cashbond_id_to_approve);
        
        //remittter_bank_id
        $cashbond->remitter_bank_id = $request->remitter_bank_id;
        $cashbond->accounted_approval = 'approved';
        $cashbond->save();

        //register to the_logs table;
        $log_description = "approved to be registered to transfer task";
        $log = $this->register_to_the_logs('cashbond', 'update', $request->cashbond_id_to_approve, $log_description );

        return redirect()->back()
            ->with('successMessage', "cashbond $cashbond->code has been approved to be transfered");
    }

    public function approveCashbondMultiple(Request $request)
    {

        $count = 0;
        $remitter_bank_id = $request->remitter_bank_id_multiple;
        if(count($request->cashbond_multiple)){
            foreach ($request->cashbond_multiple as $cashbond_id){
                $cashbond = Cashbond::findOrFail($cashbond_id);
        
                //remittter_bank_id
                $cashbond->remitter_bank_id = $remitter_bank_id;
                $cashbond->accounted_approval = 'approved';
                $cashbond->save();

                //register to the_logs table;
                $log_description = "approved to be registered to transfer task";
                $log = $this->register_to_the_logs('cashbond', 'update', $cashbond_id, $log_description );
                $count++;
            }
        }
        return redirect()->back()
            ->with('successMessage',"$count data has been approved");
    }

    public function transferCashbond(Request $request)
    {
        $cashbond = cashbond::findOrFail($request->cashbond_id_to_transfer);
        // transaction registration
        $transaction_registration = $this->register_transaction_from_cashbond($cashbond);
        if($transaction_registration == TRUE){
            //set accounted status of cashbond to TRUE;
            $cashbond->transaction_date =  date('Y-m-d');
            $cashbond->accounted = TRUE;
            $cashbond->save();

            //register to the_logs table;
            $log_description = "Transfered";
            $log = $this->register_to_the_logs('cashbond', 'update', $request->cashbond_id_to_transfer, $log_description );

            //Register cashbond installment
            $this->register_cashbond_installment($cashbond);

            return redirect()->back()
                ->with('successMessage', "$cashbond->code has been transfered");
        }
        return abort(500);
        
    }


    protected function register_transaction_from_cashbond($cashbond)
    {
        $cash = Cash::find($cashbond->remitter_bank_id);

        $transaction = new Transaction;
        $transaction->cash_id = $cashbond->remitter_bank_id;
        $transaction->refference = 'cashbond';
        $transaction->refference_id = $cashbond->id;
        $transaction->refference_number = $cashbond->code;
        $transaction->notes = $cashbond->description;
        $transaction->transaction_date = $cashbond->transaction_date;
        $transaction->type = 'debet';
        $transaction->amount = abs($cashbond->amount);
        $transaction->reference_amount = $cash->amount - abs($cashbond->amount);
        $transaction->save();
        //now fix the cash amount id,
        
        if($cash){
            $cash->amount = $cash->amount - abs($cashbond->amount);
            $cash->save();
        }
        return TRUE;
    }

    protected function register_cashbond_installment($obj)
    {
        $cashbond = $obj;

        $current_year = date('Y');
        $current_month = date('m');
        $period = Period::where('end_date', 'LIKE', "%$current_year-$current_month%")->get()->first();
        
        $first_installment_schedule = $period->end_date;
        $next_installment_schedule_arr = [ $first_installment_schedule ];

        $amount_per_installment = $cashbond->amount / $cashbond->term;

        if($cashbond->term > 1){
            
            for($i = 1;$i<$cashbond->term;$i++){
                $next_installment_schedule =  Carbon::parse($first_installment_schedule)->addMonth($i)->format('Y-m-d');
                $next_installment_schedule_arr[] = $next_installment_schedule;
            }
            
        }

        if(count($next_installment_schedule_arr)){
            //clear all the instalment related to cashbond id at first
            \DB::table('cashbond_installments')->where('cashbond_id', '=', $cashbond->id)->delete();
            foreach($next_installment_schedule_arr as $nisa){
                $cashbond_installment = new CashbondInstallment;
                $cashbond_installment->cashbond_id = $cashbond->id;
                $cashbond_installment->amount = $amount_per_installment;
                $cashbond_installment->installment_schedule = $nisa;
                $cashbond_installment->status = 'unpaid';
                $cashbond_installment->save();
            }
        }

    }


    //return transfer task payroll lists page
    public function payroll()
    {
        return view('transfer-task.payroll');
    }

    public function transferPayroll(Request $request)
    {
        // dd($request->all());
        $cash_id = $request->remitter_bank_id;
        if($request->has('id_to_transfer')){
            foreach($request->id_to_transfer as $id){
                $this->run_transfer_payroll($id, $cash_id);
            }
            return redirect()->back()
                ->with('successMessage', "Payroll has been accounted");
        }
    }

    protected function run_transfer_payroll($id, $cash_id)
    {
        try{
            $payroll = Payroll::findOrFail($id);
            $cash = Cash::findOrFail($cash_id);
            //get payroll gross amount
            $gross_amount = $payroll->gross_amount;

            $transaction = new Transaction;
            $transaction->cash_id = $cash->id;
            $transaction->refference = 'payroll';
            $transaction->refference_id = $payroll->id;
            $transaction->refference_number = $payroll->user->name."_".$payroll->period->code;
            $transaction->notes = $payroll->period->code;
            $transaction->transaction_date = Carbon::now();
            $transaction->type = 'debet';
            $transaction->amount = abs($gross_amount);
            $transaction->reference_amount = $cash->amount - abs($gross_amount);
            $transaction->save();

            //now fix the cash amount id,
            if($cash){
                $cash->amount = $cash->amount - abs($gross_amount);
                $cash->save();
            }

            //set payroll accounted to TRUE;
            $payroll->accounted = TRUE;
            $payroll->save();
            return TRUE;
        }
        catch(Exception $e){
            print_r($e);
        }

        
    }
}
