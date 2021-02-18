<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class StoreProjectRequest extends Request
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
         $rules =  [
            'category'=>'required',
            'code'=>'required|unique:projects,code',
            'name'=>'required',
            //'purchase_order_customer_id'=>'required_if:category,external|integer|exists:purchase_order_customers,id|unique:projects,purchase_order_customer_id',
            //'sales_id'=>'required_if:category,external|integer|exists:users,id'
        ];

        return $rules;
    }
}
