<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;

use Yajra\Datatables\Datatables;
use Carbon\Carbon;

use App\Project;
use App\PurchaseOrderCustomer;
use App\User;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('project.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(\Auth::user()->can('create-project')){
            $purchase_order_customer_opts = PurchaseOrderCustomer::lists('code', 'id');
            $sales_opts = User::lists('name', 'id');
            return view('project.create')
                ->with('sales_opts', $sales_opts)
                ->with('purchase_order_customer_opts', $purchase_order_customer_opts);
        }
        return abort(403);
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProjectRequest $request)
    {
        
        $project = new Project;
        $project->category = $request->category;
        $project->code = $request->code;
        $project->name = $request->name;
        $project->purchase_order_customer_id = $request->purchase_order_customer_id;
        $project->sales_id = $request->sales_id;
        $project->created_at = $request->created_at;
        $store = $project->save();

        if($store){
            return redirect('project')
                ->with('successMessage', 'Project has been saved');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
        if(\Auth::user()->can('view-project')){
            $project = Project::findOrFail($id);
            $total_paid_invoice = $project->paid_invoice_customer();
            $total_pending_invoice = $project->pending_invoice_customer();

            $total_invoice_due = $project->invoice_customer_due();
            //echo $total_invoice_due;exit();
            $total_amount_invoice_vendor = $project->total_amount_invoice_vendor();
            $total_amount_internal_request = $project->total_amount_internal_request();

            $total_expense_from_settlement = $this->get_total_expense_from_settlement($project);
            $total_expenses = $total_amount_invoice_vendor+$total_amount_internal_request+$total_expense_from_settlement;
            $purchase_order_customer_amount = ($project->purchase_order_customer ? $project->purchase_order_customer->amount : 1);
            $purchase_order_customer_amount_per_ppn = $purchase_order_customer_amount/1.1;

            $invoiced = $project->invoiced;
            $man_hour_information = $project->man_hour_information;
            
            return view('project.show')
                ->with('project', $project)
                ->with('total_paid_invoice', $total_paid_invoice)
                ->with('total_amount_invoice_vendor', $total_amount_invoice_vendor)
                ->with('total_amount_internal_request', $total_amount_internal_request)
                ->with('total_expense_from_settlement', $total_expense_from_settlement)
                ->with('total_expenses', $total_expenses)
                ->with('invoiced', $invoiced)
                ->with('total_invoice_due', $total_invoice_due)
                ->with('man_hour_information', json_decode($man_hour_information));
        }
        return abort(403);

        /*$project = Project::findOrFail($id);

        $paid_invoice_customer = $project->paid_invoice_customer();
        echo $paid_invoice_customer;*/
        
    }

    protected function get_total_expense_from_settlement($project)
    {
        $total_expense_from_settlement = 0;
        //check if project has already settlement
        if($project->internal_requests->count())
        {
           $settlement_amount_array = [];
           $settlement_adder_array = [];
           $settlement_subtracter_array = [];
           foreach($project->internal_requests as $internal_request){
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


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if(\Auth::user()->can('edit-project')){
            $project = Project::findOrFail($id);
            $purchase_order_customer_opts = PurchaseOrderCustomer::lists('code', 'id');
            $sales_opts = User::lists('name', 'id');
            return view('project.edit')
                ->with('sales_opts', $sales_opts)
                ->with('purchase_order_customer_opts', $purchase_order_customer_opts)
                ->with('project', $project);
        }
        return abort(403);
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);
        $project->code = $request->code;
        $project->name = $request->name;
        $project->created_at = $request->created_at;
        $project->purchase_order_customer_id = $request->purchase_order_customer_id;
        $project->sales_id = $request->sales_id;
        $project->enabled = $request->enabled;
        $project->save();
        return redirect('project/'.$id)
            ->with('successMessage', "Project has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $project = Project::findOrFail($request->project_id);
        $project->delete();
        return redirect('project')
            ->with('successMessage', "Project has been deleted");
    }


    public function getSalesFromPurchaseOrderCustomer(Request $request)
    {
        $purchase_order_customer_id = $request->purchase_order_customer_id;
        $purchase_order_customer = PurchaseOrderCustomer::findOrFail($purchase_order_customer_id);
        $sales_id = $purchase_order_customer->quotation_customer->sales->id;
        $sales = User::findOrFail($sales_id);
        return json_encode($sales);
    }


   //PROJECT datatables
    public function dataTables(Request $request)
    {
        if(version_compare(PHP_VERSION, '7.2.0', '>=')) {
            error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
        }
        \DB::statement(\DB::raw('set @rownum=0'));
        $projects = Project::with('purchase_order_customer', 'sales', 'purchase_order_customer.customer')->select([
            \DB::raw('@rownum  := @rownum  + 1 AS rownum'),
            'projects.*',
        ]);
        if ($request->get('cost_margin_value')) {
            $projects = Project::with('purchase_order_customer', 'sales', 'purchase_order_customer.customer')->select([
                \DB::raw('@rownum  := @rownum  + 1 AS rownum'),
                'projects.*',
            ])
            ->get()
            ->filter(function($projects) use($request){
                $cost_margin_operator = $request->get('cost_margin_operator');
                
                if($cost_margin_operator == "="){
                    
                    return $projects->cost_margin == $request->get('cost_margin_value');
                }
                else if($cost_margin_operator == ">="){
                    
                    return $projects->cost_margin >= $request->get('cost_margin_value');
                }
                else if($cost_margin_operator == ">"){
                    
                    return $projects->cost_margin > $request->get('cost_margin_value');
                }
                else if($cost_margin_operator == "<"){
                    
                    return $projects->cost_margin < $request->get('cost_margin_value');
                }
                else{
                    
                }
            });
        }

        if($request->get('invoiced_value')){

            $projects = Project::with('purchase_order_customer', 'sales', 'purchase_order_customer.customer')->select([
                \DB::raw('@rownum  := @rownum  + 1 AS rownum'),
                'projects.*',
            ])
            ->get()
            ->filter(function($projects) use($request){
                $invoiced_operator = $request->get('invoiced_operator');
                
                if($invoiced_operator == "="){
                    
                    return $projects->invoiced == $request->get('invoiced_value');
                }
                else if($invoiced_operator == ">="){
                    
                    return $projects->invoiced >= $request->get('invoiced_value');
                }
                else if($invoiced_operator == ">"){
                    
                    return $projects->invoiced > $request->get('invoiced_value');
                }
                else if($invoiced_operator == "<"){
                    
                    return $projects->invoiced < $request->get('invoiced_value');
                }
                else{
                    
                }
            });
        }
        else{

        }


        
        $data_projects = Datatables::of($projects)
            
            ->editColumn('code', function($projects){
                $code_link  = '<a href="'.url('project/'.$projects->id.'').'">';
                $code_link .=   $projects->code;
                $code_link .= '</a>';
                return $code_link;
            })
            ->editColumn('name', function($projects){
                if(strlen($projects->name) > 99){
                    return substr($projects->name, 0, 100)."...";
                }
                return $projects->name;
            })
            ->editColumn('purchase_order_customer_id', function($projects){
                if($projects->purchase_order_customer){
                    return $projects->purchase_order_customer->code;
                }
                return NULL;
            })
            ->editColumn('sales_id', function($projects){
                if($projects->sales){
                    return $projects->sales->name;
                }
                return NULL;
            })
            ->addColumn('customer_id', function($projects){
                if($projects->purchase_order_customer){

                    return $projects->purchase_order_customer->customer ? $projects->purchase_order_customer->customer->name : NULL;
                }
                return NULL;
            })
            ->addColumn('purchase_order_customer_amount', function($projects){
                return $projects->purchase_order_customer ? number_format($projects->purchase_order_customer->amount, 2) : 0;
            })
            ->addColumn('invoiced', function($projects){
               
                return $projects->invoiced ." %";
            })
            ->addColumn('pending_invoice_customer_amount', function($projects){
                return $projects->pending_invoice_customer() ? number_format($projects->pending_invoice_customer(), 2) : 0 ;
            })
            ->addColumn('paid_invoice_customer_amount', function($projects){
                return $projects->paid_invoice_customer() ? number_format($projects->paid_invoice_customer(), 2) : 0;
            })
            ->editColumn('cost_margin', function($projects){
                if($projects->cost_margin != NULL){
                    return round($projects->cost_margin, 2);
                }else{
                    return NULL;
                }
                
            })
            ->addColumn('created_at', function($projects){
                return $projects->created_at != NULL ? Carbon::parse($projects->created_at)->format('Y-m-d') : '';
            })
            ->addColumn('actions', function($projects){
                    $actions_html ='<a href="'.url('project/'.$projects->id.'').'" class="btn btn-primary btn-xs" title="Click to view the detail">';
                    $actions_html .=    '<i class="fa fa-external-link"></i>';
                    $actions_html .='</a>&nbsp;';
                    $actions_html .='<a href="'.url('project/'.$projects->id.'/edit').'" class="btn btn-success btn-xs" title="Click to edit this project">';
                    $actions_html .=    '<i class="fa fa-edit"></i>';
                    $actions_html .='</a>&nbsp;';
                    if(\Auth::user()->can('delete-project')){
                        $actions_html .='<button type="button" class="btn btn-danger btn-xs btn-delete-project" data-id="'.$projects->id.'" data-text="'.$projects->code.'">';
                        $actions_html .=    '<i class="fa fa-trash"></i>';
                        $actions_html .='</button>';
                    }
                    
                    return $actions_html;
            });
        
    
        if ($keyword = $request->get('search')['value']) {
            $data_projects->filterColumn('rownum', 'whereRaw', '@rownum  + 1 like ?', ["%{$keyword}%"]);
            //$data_projects->filterColumn('cost_margin', 'whereRaw', '@cost_margin  like ?', ["%{$keyword}%"]);
        }
        return $data_projects->make(true);
    }
    //END PROJECT dataables
    

    //Complete project
    public function complete(Request $request)
    {
        $project = Project::findOrFail($request->project_id_to_complete);
        $project->is_completed = TRUE;
        $project->save();
        return redirect()->back()
            ->with('successMessage', "Project has been marked as completed");
    }

    public function select2ForDeliveryOrder(Request $request)
    {
        $data = [];
        if($request->has('q')){
            $search = $request->q;
            $data = \DB::table("projects")
                    ->join('purchase_order_customers', 'projects.purchase_order_customer_id', '=', 'purchase_order_customers.id')
                    ->join('customers', 'purchase_order_customers.customer_id', '=', 'customers.id')
                    ->where('projects.enabled','=',TRUE)
                    ->where('projects.code','LIKE',"%$search%")
                    ->select('projects.code', 'projects.id', \DB::raw('customers.name AS customer_name'))
                    ->get();
        }
        else{
            $data = \DB::table('projects')
                    ->join('purchase_order_customers', 'projects.purchase_order_customer_id', '=', 'purchase_order_customers.id')
                    ->join('customers', 'purchase_order_customers.customer_id', '=', 'customers.id')
                    ->select('projects.code', 'projects.id', \DB::raw('customers.name AS customer_name'))
                    ->where('projects.enabled','=',TRUE)
                    ->get();
        }
        return response()->json($data);
    }


    public function generateManHourCost(Request $request)
    {
        try {
            $project = Project::findOrFail($request->project_id);
            $project_code = $project->code;
            $result = $project::generateManHourCost($project_code);
            return response()->json(['success'=>true, 'data'=>$result]);

        } catch (Exception $e) {
            return $e;
        }
        
    }

    public function getTotalManhorCost(Request $request)
    {
        try {
            $project = Project::findOrFail($request->project_id);
            $project_code = $project->code;
            $result = $project::getTotalManhorCost($project_code);
            return response()->json(['success'=>true, 'data'=>['total_cost'=>$result]]);

        } catch (Exception $e) {
            return $e;
        }
    }
    
}
