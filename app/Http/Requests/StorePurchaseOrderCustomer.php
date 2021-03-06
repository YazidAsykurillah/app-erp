<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class StorePurchaseOrderCustomer extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code'=>'required|unique:purchase_order_customers,code',
            'quotation_customer_id'=>'required|integer|exists:quotation_customers,id',
            'customer_id'=>'required|integer',
            'description'=>'required',
            'amount'=>'required',
            'received_date'=>'required',
            'file'=>'mimes:pdf,doc,docx,xlsx'
        ];
    }
}
