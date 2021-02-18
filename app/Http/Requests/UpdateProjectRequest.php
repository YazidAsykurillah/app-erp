<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UpdateProjectRequest extends Request
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
            'category'=>'required',
            'code'=>'required|unique:projects,code,'.$this->segment(2),
            'name'=>'required',
            'purchase_order_customer_id'=>'required_if:category,external|integer|unique:projects,purchase_order_customer_id,'.$this->segment(2),
            'sales_id'=>'required_if:category,external|integer|exists:users,id'
        ];
    }
}
