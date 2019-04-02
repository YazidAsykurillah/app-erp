<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\InvoiceCustomer;
use App\Project;

class InvoiceCustomer extends Model
{
    protected $table = 'invoice_customers';

    protected $fillable = ['code', 'tax_number', 'sub_amount','vat', 'wht', 'amount', 
        'project_id', 'due_date', 'description', 'status', 'submitted_date', 'prepared_by', 'file',
        'type', 'posting_date', 'tax_date', 'cash_id'
    ];

    public function project()
    {
    	return $this->belongsTo('App\Project', 'project_id');
    }


    public function preparator()
    {
    	return $this->belongsTo('App\User', 'prepared_by');
    }

}
