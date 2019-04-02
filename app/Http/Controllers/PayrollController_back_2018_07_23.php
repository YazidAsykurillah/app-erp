<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\StorePayrollRequest;

use App\Payroll;
use App\User;
use App\Ets;
use App\Period;
use App\Allowance;
use App\AllowanceItem;
use App\MedicalAllowance;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('payroll.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $period_opts = Period::lists('code', 'id');
        return view('payroll.create')
            ->with('period_opts', $period_opts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePayrollRequest $request)
    {
        $payroll = new Payroll;
        $payroll->user_id = $request->user_id;
        $payroll->period_id = $request->period_id;
        $payroll->save();
        return redirect('payroll/'.$payroll->id)
            ->with('successMessage', "Payroll has been created, now you can calculate the salary");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    

    public function show($id)
    {
        $payroll = Payroll::findOrFail($id);
        $period = $payroll->period;
        $user = $payroll->user;
        $ets_lists = Ets::where('user_id','=', $user->id)->where('period_id', $period->id)->get();
        
        $normal_count = Ets::where('user_id','=', $user->id)->where('period_id', $period->id)->sum('normal');
        $normal_total = $normal_count*1;

        $I_count = Ets::where('user_id','=', $user->id)->where('period_id', $period->id)->sum('I');
        $I_total = $I_count*1.5;

        $II_count = Ets::where('user_id','=', $user->id)->where('period_id', $period->id)->sum('II');
        $II_total = $II_count*2;

        $III_count = Ets::where('user_id','=', $user->id)->where('period_id', $period->id)->sum('III');
        $III_total = $III_count*3;

        $IV_count = Ets::where('user_id','=', $user->id)->where('period_id', $period->id)->sum('IV');
        $IV_total = $IV_count*4;


        $man_hour_total = $normal_total+$I_total+$II_total+$III_total+$IV_total;

        $total_basic_salary = $user->salary;

        $total_man_hour_salary = $user->man_hour_rate * $man_hour_total;

        //check allowance
        $check_allowances = $this->check_allowances($user, $period);

        $allowances = Allowance::where('user_id', $user->id)
                        ->where('period_id', $period->id)
                        ->get();

        //check medical allowance
        $check_medical_allowance = $this->check_medical_allowance($user, $period);
        $medical_allowance = MedicalAllowance::where('user_id', $user->id)
                        ->where('period_id', $period->id)
                        ->get();

        

        return view('payroll.show')
            ->with('ets_lists', $ets_lists)
            ->with('normal_count', $normal_count)
            ->with('normal_total', $normal_total)
            
            ->with('I_count', $I_count)
            ->with('I_total', $I_total)

            ->with('II_count', $II_count)
            ->with('II_total', $II_total)

            ->with('III_count', $III_count)
            ->with('III_total', $III_total)

            ->with('IV_count', $IV_count)
            ->with('IV_total', $IV_total)

            ->with('man_hour_total', $man_hour_total)

            ->with('total_basic_salary', $total_basic_salary)
            
            ->with('total_man_hour_salary', $total_man_hour_salary)

            ->with('allowances', $allowances)

            ->with('medical_allowance', $medical_allowance)

            ->with('payroll', $payroll);

        
    }


    protected function check_medical_allowance($user, $period){
        $medical_allowance = MedicalAllowance::where('user_id', $user->id)
                        ->where('period_id', $period->id)
                        ->get();

        if(count($medical_allowance) == 0){
            $this->register_medical_allowance($user, $period);
        }
    }

    protected function register_medical_allowance($user, $period)
    {
        \DB::table('medical_allowances')->where('user_id', '=', $user->id)->where('period_id', '=', $period->id)->delete();
        $medical_allowance = [
            'user_id'=>$user->id,
            'period_id'=>$period->id,
            'amount'=>$user->medical_allowance,
            'multiplier'=>0,
            'total_amount'=>0
        ];
        \DB::table('medical_allowances')->insert($medical_allowance);
    }


    protected function check_allowances($user, $period){
        
        $allowances = Allowance::where('user_id', $user->id)
                        ->where('period_id', $period->id)
                        ->get();

        if(count($allowances) == 0){
            $this->register_allowance($user, $period);
        }
    }

    protected function register_allowance($user, $period){
        $allowance_names = [
            'local', 'non-local'
        ];
        
        if($user && $period){
            foreach($allowance_names as $name){
                $allowance = new Allowance;
                $allowance->period_id = $period->id;
                $allowance->user_id = $user->id;
                $allowance->name = $name;
                $allowance->save();
                $allowance_id = $allowance->id;
                //build allowance items
                $this->register_allowance_items($allowance_id, $user);
            }
        }
        return TRUE;
    }

    protected function register_allowance_items($allowance_id, $user){
        \DB::table('allowance_items')->where('allowance_id', $allowance_id)->delete();

        $data = [
            ['allowance_id'=>$allowance_id, 'type'=>'transportation', 'amount'=>$user->transportation_allowance, 'multiplier'=>0, 'total_amount'=>0],
            ['allowance_id'=>$allowance_id, 'type'=>'meal', 'amount'=>$user->eat_allowance, 'multiplier'=>0, 'total_amount'=>0]
        ];

        return \DB::table('allowance_items')->insert($data);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function update_thp_amount(Request $request)
    {
        $thp_amount = 0;

        $payroll = Payroll::findOrFail($request->payroll_id);
        $user = User::findOrFail($payroll->user->id);
        $period = Period::findOrFail($payroll->period->id);

        $total_man_hour_salary = $request->total_man_hour_salary;

        //collect allowances
        $total_amount_from_allowances = 0;
        $allowances = Allowance::where('user_id','=',$user->id)
                                ->where('period_id', '=',$period->id)
                                ->get();
        if(count($allowances)){
            $allowance_ids = [];
            foreach($allowances as $allowance){
                $allowance_ids[] = $allowance->id;
            }
            $total_amount_from_allowances = AllowanceItem::whereIn('allowance_id', $allowance_ids)->sum('total_amount');
        }

        //collect total_amount from medical allowance
        $total_amount_from_medical_allowance = 0;
        $medical_allowance = MedicalAllowance::where('user_id', '=',$user->id)->where('period_id', '=', $period->id)->get();
        if(count($medical_allowance)){
            $total_amount_from_medical_allowance = $medical_allowance->first()->total_amount;
        }

        
        $thp_amount = $total_man_hour_salary+$total_amount_from_allowances+$total_amount_from_medical_allowance;
        //update thp amount of this payroll
        $payroll->thp_amount = $thp_amount;
        $payroll->save();

        return response()->json(
            ['thp_amount'=>number_format($thp_amount,2)]
        );

    }
}
