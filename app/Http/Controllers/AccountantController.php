<?php

namespace App\Http\Controllers;

use App\Models\accountant;
use App\Models\AwaitingConsultation;
use App\Models\Diagnosis;
use App\Models\DrugSales;
use App\Models\LabTest;
use App\Models\LeaveApplication;
use App\Models\Payment;
use App\Models\Rates;
use App\Models\Reports;
use App\Models\SalaryAllowances;
use App\Models\Sales;
use App\Models\StockRequest;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountantController extends Controller
{
    public function getAccountantOverview (Request $request){

            $req = $request->all();

            try {

                if (isset($req['start_date']) && isset($req['end_date'])){

                    $startDate = Carbon::parse($req['start_date'])->startOfDay();
                    $endDate = Carbon::parse($req['end_date'])->endOfDay();

                    $accountant = accountant::with(['user.salaryAllowances','user.leaveApplication'])->where('user_id',$request->user()->id)->get();

                    $payments = Payment::whereBetween('created_at', [$startDate,$endDate])->get();

                    $drugSales = Sales::with(['pharmasist'])->where('payment_status','paid')->whereBetween('created_at', [$startDate,$endDate])->get();

                    $labTests = LabTest::with(['labScientist'])->where('lab_test_payment_status','paid')->whereBetween('created_at', [$startDate,$endDate])->get();

                    $consultation = AwaitingConsultation::with(['doctor'])->where('payment_status','paid')->whereBetween('created_at', [$startDate,$endDate])->get();

                    $stockRequest = StockRequest::with(['user'])->where('status','approved')->whereBetween('created_at', [$startDate,$endDate])->get();

                    $salary = SalaryAllowances::whereBetween('created_at', [$startDate,$endDate])->get();

                    $unitReport = Reports::where('unit', 'accountant')->get();

                    $hospitalRates = Rates::all();

                    //$totalLabTest = LabTest::sum('lab_test_amount');
                    $totalLabTest = $labTests->sum('lab_test_amount');

                    //$totalDrugSale = LabTest::sum('total_amount');
                    $totalDrugSale = $drugSales->sum('total_amount');

                    $totalConsultation = $consultation->sum('amount');

                    $totalSalary = $salary->sum('amount');



                    $revenue = $payments->where('status','credit');
                    $expenses = $payments->where('status','debit');

                    $totalRevenue = $revenue->sum('amount');
                    $totalExpenses = $expenses->sum('amount');

                    $pnl = $payments->sortBy('created_at')->groupBy(function ($item){
                        return $item->created_at->format('d-M');
                    })->map(function ($items){
                        return [
                            'revenue' => $items->where('status','credit')->sum('amount'),
                            'expenses' => $items->where('status','debit')->sum('amount')
                        ];
                    });

                    $areaChat = [];

                    foreach ($pnl as $item => $value){
                        $areaChat = [...$areaChat, [
                            'date' => $item,
                            'expenses' => $value['expenses'],
                            'revenue' => $value['revenue']
                        ]];
                    }

                    $departmentalChat = [
                        ['dept'=> 'consultation' , 'amount' => $totalConsultation],
                        ['dept'=> 'pharmacy' , 'amount' => $totalDrugSale],
                        ['dept'=> 'lab test' , 'amount' => $totalLabTest]
                    ];


                    $data = [
                        'accountant' => $accountant,
                        'payments' => $payments,
                        'drugSales' => $drugSales,
                        'labTests' => $labTests,
                        'consultation' => $consultation,
                        'stockRequest' => $stockRequest,
                        'salary' => $salary,
                        'unitReport' => $unitReport,
                        'hospitalRates' => $hospitalRates,
                        'revenue' => $revenue,
                        'expenses' => $expenses,
                        'totalRevenue' => $totalRevenue,
                        'totalExpenses' => $totalExpenses,
                        'totalLabTest' => $totalLabTest,
                        'totalDrugSale' => $totalDrugSale,
                        'totalConsultation' => $totalConsultation,
                        'totalSalary' => $totalSalary,
                        'pnlChart' => $areaChat,
                        'deptChart' => $departmentalChat
                    ];






                    return response()->json(['message'=>'','data'=> $data],200);

                }else{
                    $accountant = accountant::with(['user.salaryAllowances','user.leaveApplication'])->where('user_id',$request->user()->id)->get();

                    $payments = Payment::whereMonth('created_at',now()->month)->whereYear('created_at', now()->year)->get();

                    $drugSales = Sales::with(['pharmasist'])->where('payment_status','paid')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                    $labTests = LabTest::with(['labScientist'])->where('lab_test_payment_status','paid')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                    $consultation = AwaitingConsultation::with(['doctor'])->where('payment_status','paid')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                    $stockRequest = StockRequest::with(['user'])->where('status','approved')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                    $salary = SalaryAllowances::whereYear('created_at', now()->year())->whereMonth('created_at', now()->month)->get();

                    $unitReport = Reports::where('unit', 'accountant')->get();

                    $hospitalRates = Rates::all();



                    $revenue = $payments->where('status','credit');
                    $expenses = $payments->where('status','debit');

                    $totalRevenue = $revenue->sum('amount');
                    $totalExpenses = $expenses->sum('amount');

                    //$totalLabTest = LabTest::sum('lab_test_amount');
                    $totalLabTest = $labTests->sum('lab_test_amount');

                    //$totalDrugSale = LabTest::sum('total_amount');
                    $totalDrugSale = $drugSales->sum('total_amount');

                    $totalConsultation = $consultation->sum('amount');

                    $totalSalary = $salary->sum('amount');

                    $pnl = $payments->sortBy('created_at')->groupBy(function ($item){
                        return $item->created_at->format('d');
                    })->map(function ($items){
                        return [
                            'revenue' => $items->where('status','credit')->sum('amount'),
                            'expenses' => $items->where('status','debit')->sum('amount')
                        ];
                    });

                    $areaChat = [];

                    foreach ($pnl as $item => $value){
                        $areaChat = [...$areaChat, [
                            'date' => $item,
                            'expenses' => $value['expenses'],
                            'revenue' => $value['revenue']
                        ]];
                    }

                    $departmentalChat = [
                        ['dept'=> 'consultation' , 'amount' => $totalConsultation],
                        ['dept'=> 'pharmacy' , 'amount' => $totalDrugSale],
                        ['dept'=> 'lab test' , 'amount' => $totalLabTest]
                    ];


                    $data = [
                        'accountant' => $accountant,
                        'payments' => $payments,
                        'drugSales' => $drugSales,
                        'labTests' => $labTests,
                        'consultation' => $consultation,
                        'stockRequest' => $stockRequest,
                        'salary' => $salary,
                        'unitReport' => $unitReport,
                        'hospitalRates' => $hospitalRates,
                        'revenue' => $revenue,
                        'expenses' => $expenses,
                        'totalRevenue' => $totalRevenue,
                        'totalExpenses' => $totalExpenses,
                        'totalLabTest' => $totalLabTest,
                        'totalDrugSale' => $totalDrugSale,
                        'totalConsultation' => $totalConsultation,
                        'totalSalary' => $totalSalary,
                        'pnlChart' => $areaChat,
                        'deptChart' => $departmentalChat
                    ];






                    return response()->json(['message'=>'','data'=> $data],200);
                }



            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

    public function updatePayment (Request $request){

            $req = $request->all();

            try {
                $payment = Payment::with(['sales.drugStock','labTest.rates'])->where('id', $req['payment_id'])->first();

                $payment->update([
                    'outStanding_balance' => 0,
                    'completion_status' => 'completed'
                ]);
                $payment->refresh();

                return response()->json(['message'=>'Payment has been Settled Completely','data'=> $payment],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }


        /*this function is called after the user makes payment*/
    public function updateDrugSales (Request $request){

                $req = $request->all();

                try {

                    return DB::transaction(function ()use($req,$request){

                        $sales = Sales::with(['drugStock','payment.signedAccountant','payment.patientUser'])->where('id', $req['sales_id'])->lockForUpdate()->first();



                        if ($sales->payment_status === 'paid') {
                            return response()->json(['message' => 'Prescription already paid'], 400);
                        }

                        $paymentPayload  = [
                            'patient_user_id' => $req['patientUser_id'],
                            'signed_accountant_id' => $request->user()->id,
                            'payment_type' => 'drugSales',
                            'title' => $req['title'],
                            'invoice_id' => 'DS'.Str::random(7),
                            'amount' => $sales->total_amount,
                            'status' => 'credit'
                        ];


                        $payment = Payment::create($paymentPayload);

                        $sales->update([
                            'payment_status' => 'paid',
                            'payment_id' => $payment->id
                        ]);

                        $sales = Sales::with(['drugStock','payment.signedAccountant','payment.patientUser'])->where('id', $req['sales_id'])->lockForUpdate()->first();



                        $data =[
                            'sales' => $sales,
                            'payment' => $payment
                        ];






                        return response()->json(['message'=>'Payment has been made Successfully','data'=> $data],203);


                    });


                }catch (Exception $exception){
                    return response()->json(['message' => $exception->getMessage()]);
                }
    }

    public function updateLabPayment (Request $request){

                    $req = $request->all();

                    try {

                      return  DB::transaction(function () use($req,$request){

                            $labTest = LabTest::with(['rates'])->where('id',$req['labTest_id'])->lockForUpdate()->first();

                          if ($labTest->lab_test_payment_status === 'paid') {
                              return response()->json(['message' => 'Lab test already paid'], 400);
                          }

                            $paymentEntry = Payment::create([
                                'patient_user_id' => $req['patientUser_id'],
                                'signed_accountant_id' => $request->user()->id,
                                'payment_type' => 'labTests',
                                'title' => $req['title'],
                                'invoice_id' => 'Drg'.Str::random(7),
                                'amount' => $labTest->lab_test_amount,
                                'status' => 'credit'
                            ]);


                            $labTest->update([
                                'lab_test_payment_status' => 'paid',
                                'payment_id' => $paymentEntry->id
                            ]);

                            $labTest->refresh();

                            $data =[
                                'labTest' => $labTest,
                                'payment' => $paymentEntry
                            ];


                            return response()->json(['message'=>'Lab Test Payment made successfully','data'=> $data],201);

                        });




                    }catch (Exception $exception){
                        return response()->json(['message' => $exception->getMessage()]);
                    }
    }


    public function getPatientPaymentByRegNo(Request $request){

        $req = $request->all();

        /* todo finish up the getpatient labtest function*/
        try {



            //$patientLabTests = LabTest::with(['rates','labScientist.user','patient.user'])->where('patient_id',$req['patient_id'])->get();

            //$patientLabTests = User::with(['patient','patient.labtest.labScientist.user','patient.labtest.rates'])->where('regID',$req['regID'])->first();

            $patientDiagnosis = User::with(['patient.diagnosis' => function($query) {
                $query-> with([
                    'labTest' => function($query) {
                        $query->where('lab_test_payment_status', 'unpaid')
                        ->orWhereHas('payment',function ($q){
                            $q->where('completion_status','pending');
                        })
                        ;
                    },

                    'labTest.rates',

                    'sales' => function($query) {
                        $query->where('payment_status', 'unpaid')
                        ->orWhereHas('payment',function ($q){
                            $q->where('completion_status','pending');
                        })
                        ;

                    },
                    'sales.drugStock',
                    'consultation' => function($query) {
                        $query->where('payment_status', 'unpaid');

                    }])->orderBy('created_at', 'desc'); // Latest diagnosis first
            }])
                ->where('regID', $req['regID'])
                ->first();



            if (!$patientDiagnosis){
                return response()->json(['message' => "No Record found for this Patient"],203);
            }

            return response()->json(['data' => $patientDiagnosis,'message'=>'User has been fetched successfully']);





        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }



    public function updateConsultation (Request $request){

        $req = $request->all();

        try {

            return  DB::transaction(function () use($req,$request){

                $consultation = AwaitingConsultation::where('id',$req['consultation_id'])->lockForUpdate()->first();

                if ($consultation->payment_status === 'paid') {
                    return response()->json(['message' => 'Consultation already paid'], 400);
                }

                $paymentEntry = Payment::create([
                    'patient_user_id' => $req['patientUser_id'],
                    'signed_accountant_id' => $request->user()->id,
                    'payment_type' => 'consultations',
                    'title' => $req['title'],
                    'invoice_id' => 'Cos'.Str::random(7),
                    'amount' => $consultation->amount,
                    'status' => 'credit'
                ]);


                $consultation->update([
                    'payment_status' => 'paid',
                    'payment_id' => $paymentEntry->id
                ]);

                $consultation->refresh();

                $data =[
                    'consultation' => $consultation,
                    'payment' => $paymentEntry
                ];


                return response()->json(['message'=>'Consultation Payment made successfully','data'=> $data],201);

            });




        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function generateInvoice (Request $request){

            $req = $request->all();

            try {

                $invoice = Diagnosis::with([
                    'labTest' => function($query) {
                        $query->with('payment')->where('lab_test_payment_status', 'paid');

                    },
                    'labTest.rates',
                    'sales' => function($query) {
                        $query->with('payment')->where('payment_status', 'paid');

                    },
                    'sales.drugStock',
                    'consultation' => function($query) {
                        $query->with('payment')->where('payment_status', 'paid');

                    }
                ])->where('id', $req['diagnosis_id'])->first();

                if (!$invoice) {
                    return response()->json([
                        'message' => 'Unable to generate invoice. Diagnosis not found'
                    ], 404);
                }


                $data = [
                    'labTest' => $invoice->labTest,
                    'sales' => $invoice->sales,
                    'consultation' => $invoice->consultation,
                ];


                return response()->json(['message'=>'Invoice Generated ','data'=> $data],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
        }


   /*     public function patientDeposit (Request $request){

                $req = $request->all();

                try {
                    return DB::transaction(function () use($req){

                        $patientUser = User::where('id',$req['user_id'])->lockForUpdate()->first();

                        if (!$patientUser){
                            return response()->json(['message'=> 'No user found'],401);
                        }


                        $newBalance = $patientUser->wallet_balance + $req['deposit'];

                        $patientUser->update([
                            'wallet_balance' => $newBalance
                        ]);

                        $patientUser->refresh();


                        return response()->json(['message'=>'','data'=> $patientUser],203);
                    });




                }catch (Exception $exception){
                    return response()->json(['message' => $exception->getMessage()]);
                }
            }

    public function patientWithdrawal (Request $request){

        $req = $request->all();

        try {
            return DB::transaction(function () use($req){

                $patientUser = User::where('id',$req['user_id'])->lockForUpdate()->first();

                if (!$patientUser){
                    return response()->json(['message'=> 'No user found'],401);
                }

                if ($patientUser->wallet_balance < $req['withdraw']){
                    return response()->json(['message'=> "Insufficient fund for withdrawal. Withdrawal Limit is $patientUser->wallet_balance"],401);
                }


                $newBalance = $patientUser->wallet_balance - $req['withdraw'];

                $patientUser->update([
                    'wallet_balance' => $newBalance
                ]);

                $patientUser->refresh();


                return response()->json(['message'=>'','data'=> $patientUser],200);
            });




        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }*/

    public function getPeriodicalPayment (Request $request){

            $req = $request->all();

            try {

                $startDate = Carbon::parse($req['start_date'])->startOfDay();
                $endDate = Carbon::parse($req['end_date'])->endOfDay();

                $payments = Payment::whereBetween('created_at', [$startDate,$endDate])->get();


                $revenue = $payments->where('status','credit');
                $expenses = $payments->where('status','debit');

                $totalRevenue = $revenue->sum('amount');
                $totalExpenses = $expenses->sum('amount');

                $data = [
                    'payments' => $payments,
                    'revenue' => $revenue,
                    'expenses' => $expenses,
                    'totalRevenue' => $totalRevenue,
                    'totalExpenses' => $totalExpenses,
                ];

                return response()->json(['message'=>'','data'=> $data],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
        }


    public function getPeriodicalDrugSales (Request $request){

        $req = $request->all();

        try {

            $startDate = Carbon::parse($req['start_date'])->startOfDay();
            $endDate = Carbon::parse($req['end_date'])->endOfDay();

            $drugSales = Sales::with(['pharmasist'])->where('payment_status','paid')->whereBetween('created_at', [$startDate,$endDate])->get();



            return response()->json(['message'=>'','data'=> $drugSales],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function getPeriodicalLabtest (Request $request){

        $req = $request->all();

        try {

            $startDate = Carbon::parse($req['start_date'])->startOfDay();
            $endDate = Carbon::parse($req['end_date'])->endOfDay();

            $labTests = LabTest::with(['labScientist'])->where('lab_test_payment_status','paid')->whereBetween('created_at', [$startDate,$endDate])->get();



            return response()->json(['message'=>'','data'=> $labTests],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function getPeriodicalConsultation (Request $request){

        $req = $request->all();

        try {

            $startDate = Carbon::parse($req['start_date'])->startOfDay();
            $endDate = Carbon::parse($req['end_date'])->endOfDay();

            $consultation = AwaitingConsultation::with(['doctor'])->where('payment_status','paid')->whereBetween('created_at', [$startDate,$endDate])->get();



            return response()->json(['message'=>'','data'=> $consultation],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


    public function getPeriodicalStockRequest (Request $request){

        $req = $request->all();

        try {

            $startDate = Carbon::parse($req['start_date'])->startOfDay();
            $endDate = Carbon::parse($req['end_date'])->endOfDay();

            $stockRequest = StockRequest::with(['user'])->where('status','approved')->whereBetween('created_at', [$startDate,$endDate])->get();



            return response()->json(['message'=>'','data'=> $stockRequest],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function getPeriodicalSalary (Request $request){

        $req = $request->all();

        try {

            $startDate = Carbon::parse($req['start_date'])->startOfDay();
            $endDate = Carbon::parse($req['end_date'])->endOfDay();

            $salary = SalaryAllowances::whereBetween('created_at', [$startDate,$endDate])->get();



            return response()->json(['message'=>'','data'=> $salary],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function getPaymentInvoice (Request $request){

            $req = $request->all();

            try {

                $patient = User::with(['patient.user',
                    'patient.sales' => function($query) {
                        $query->where('payment_status', 'unpaid')
                            /*->orWhereHas('payment',function ($q){
                                $q->where('completion_status','pending');
                            })*/->orderBy('created_at','desc');
                    },
                    'patient.sales.payment',


                    'patient.labTest' => function($query) {
                        $query->where('lab_test_payment_status', 'unpaid')
                           /* ->orWhereHas('payment',function ($q){
                                $q->where('completion_status','pending');
                            })*/->orderBy('created_at','desc');
                    },
                    'patient.labTest.payment',
                    'patient.consultation' => function($query) {
                        $query->where('payment_status', 'unpaid')
                            ->orderBy('created_at','desc');
                    },
                    'patient.consultation.payment',
                ])->whereAny(['regID','email','phone_no'], $req['details'])->first();

                $data = [
                    'labTest' => $patient->patient['labTest'],
                    'sales' => $patient->patient['sales'],
                    'consultation' => $patient->patient['consultation'],
                    'patient' => $patient
                ];


                return response()->json(['message'=>'','data'=> $data],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }
    public function getGeneratedBill (Request $request){

            $req = $request->all();

            try {

                $patient = User::with(['patient.user',
                    'patient.sales' => function($query) {
                        $query->where('payment_status', 'paid')
                            ->WhereHas('payment',function ($q){
                                $q->where('completion_status','completed');
                            })->orderBy('created_at','desc');
                    },
                    'patient.sales.payment',


                    'patient.labTest' => function($query) {
                        $query->where('lab_test_payment_status', 'paid')
                            ->WhereHas('payment',function ($q){
                                $q->where('completion_status','completed');
                            })->orderBy('created_at','desc');
                    },
                    'patient.labTest.payment',
                    'patient.consultation' => function($query) {
                        $query->where('payment_status', 'paid')
                            ->orderBy('created_at','desc');
                    },
                    'patient.consultation.payment',
                ])->whereAny(['regID','email','phone_no'], $req['details'])->first();

                $data = [
                    'labTest' => $patient->patient['labTest'],
                    'sales' => $patient->patient['sales'],
                    'consultation' => $patient->patient['consultation'],
                    'patient' => $patient
                ];


                return response()->json(['message'=>'','data'=> $data],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

    public function getUnSettledPayment (Request $request){

            $req = $request->all();

            try {

                $patientUser = User::with(['patient','patientUserPayment'=> function($q){
                    $q->where('completion_status','pending');
                }])->whereAny(
                    [
                        'regID',
                        'email',
                        'phone_no'
                    ]
                    ,$req['details'])->first();

                return response()->json(['message'=>'Unsettled Transaction has been fetched Successfully','data'=> $patientUser],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

}
