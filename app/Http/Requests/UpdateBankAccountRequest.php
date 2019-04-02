<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UpdateBankAccountRequest extends Request
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
            'user_id'=>'required|integer|exists:users,id',
            'name'=>'required',
            'account_number' => 'required|unique:bank_accounts,account_number,'.$this->segment(2)
        ];
    }
}
