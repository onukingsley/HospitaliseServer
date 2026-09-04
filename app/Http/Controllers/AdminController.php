<?php

namespace App\Http\Controllers;

use App\Models\accountant;
use App\Models\AwaitingConsultation;
use App\Models\Clerk;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\DrugStock;
use App\Models\LabScientist;
use App\Models\LabStock;
use App\Models\LabTest;
use App\Models\LeaveApplication;
use App\Models\Nurses;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Pharmasist;
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

class AdminController extends Controller
{
    public function getAdminOverView (Request $request){

        $req = $request->all();

        try {
            $totalUser = User::count();
            $totalDoctors = Doctor::count();
            // todo total nurses, clerk, lab attendant, accountant, pharmasist, patitent
            $totalNurses = Nurses::count();
            $totalClerk = Clerk::count();
            $totalPharmasist = Pharmasist::count();
            $totalLabScientist = LabScientist::count();
            $totalPatient = Patient::count();
            $totalAccountant = Accountant::count();

            $outPatient = User::with(['patient',
                'patient.diagnosis'=> function($q){
                    $q->where('ward_status','outPatient');
                }
            ])->whereHas('patient.diagnosis',function ($q){
                $q->where('ward_status', 'outPatient');
            })->get();
            $inPatient = User::with(['patient',
                'patient.diagnosis'=> function($q){
                    $q->where('ward_status','inPatient');
                }
            ])->whereHas('patient.diagnosis',function ($q){
                $q->where('ward_status', 'inPatient');
            })->get();

            $allUsers = User::paginate(50);
            /*$allDoctors = Doctor::with(['user'])->paginate(50);
            $allAccountant = accountant::with(['user'])->paginate(50);
            $allPharmasist = Pharmasist::with(['user'])->paginate(50);
            $allLabAttendant = LabScientist::with(['user'])->paginate(50);
            $allClerk = Clerk::with(['user'])->paginate(50);
            $allNurses = Nurses::with(['user'])->paginate(50);*/
            $allPatients = Patient::with(['user'])->paginate(50);


            /*$allStaff = User::with(['doctor','clerk','nurse','accountant','pharmasist','labScientist'])->where(
                function ($query) {
                    $query->where('user_role','doctor')
                        ->orWhere('user_role','clerk')
                        ->orWhere('user_role','nurse')
                        ->orWhere('user_role','accountant')
                        ->orWhere('user_role','pharmasist')
                        ->orWhere('user_role','labScientist');
                }
            )->get();*/

            $allStaff = User::with(['doctor','clerk','nurse','accountant','pharmasist','labScientist'])->whereIn('user_role',[
                'doctor','clerk','nurse','accountant','pharmasist','labScientist'
            ])->get();

            /*todo all user roles should extend from all staff*/


            $allDoctors = $allStaff->where('user_role','doctor');
            $allAccountant =  $allStaff->where('user_role','accountant');
            $allPharmasist =  $allStaff->where('user_role','pharmasist');
            $allLabAttendant =  $allStaff->where('user_role','labScientist');
            $allClerk =  $allStaff->where('user_role','clerk');
            $allNurses =  $allStaff->where('user_role','nurse');




            $salaryAllowances  = SalaryAllowances::latest()->get();
            $leaveApplication = LeaveApplication::all();

            $diagnosis = Diagnosis::with(['patient.user','doctor.user','sales.drugStock',])->get();

            $unitReport = Reports::latest()->paginate(50);
            $labStock = LabStock::all();

            $hospitalRates = Rates::all();

            $consultationRate = $hospitalRates->where('rate_type' , 'consultation');
            $enrollmentRate = $hospitalRates->where('rate_type' , 'enrollment');
            $labTestRate = $hospitalRates->where('rate_type' , 'labTest');


            if (isset($req['start_date']) && isset($req['end_date'])){

                $startDate = Carbon::parse($req['start_date'])->startOfDay();
                $endDate = Carbon::parse($req['end_date'])->endOfDay();


                $drugStock = DrugStock::with(['sales' => function($q) use($startDate,$endDate){
                    $q->where('payment_status','paid')->whereBetween('sales.created_at',[$startDate, $endDate]);
                }])->get();

                $rankingLabTest = Rates::with(['labTests' => function($q) {
                    $q->with(['payment'])->where('lab_test_payment_status','paid')->whereYear('lab_tests.created_at', now()->year)->whereMonth('lab_tests.created_at', now()->month);
                }, ])->where('rate_type','labTest')->get();





                $pendingDrugStock = $drugStock->where('status', 'pendingApproval');



                $payments = Payment::with(['rates','labTest','sales','consultation','salaryAllowance','signedAccountant','patientUser'])->whereBetween('created_at', [$startDate,$endDate])->get();

                $drugSales = Sales::with(['pharmasist'])->whereBetween('created_at', [$startDate,$endDate])->get();

                $labTests = LabTest::with(['labScientist'])->whereBetween('created_at', [$startDate,$endDate])->get();

                $consultation = AwaitingConsultation::with(['doctor'])->whereBetween('created_at', [$startDate,$endDate])->get();

                $stockRequest = StockRequest::with(['user','drugStock','labStock'])->whereBetween('created_at', [$startDate,$endDate])->get();

                $salary = SalaryAllowances::whereBetween('created_at', [$startDate,$endDate])->get();




                $revenue = $payments->where('status','credit');
                $expenses = $payments->where('status','debit');

                $totalRevenue = $revenue->sum('amount');
                $totalExpenses = $expenses->sum('amount');





                $totalSalary = $salary->sum('amount');

                $totalLabTest = $labTests->sum('lab_test_amount');

                //$totalDrugSale = LabTest::sum('total_amount');
                $totalDrugSale = $drugSales->sum('total_amount');

                $totalConsultation = $consultation->sum('amount');
                $totalEnrollment = $payments->where('status','credit')->where('payment_type','enrollment')->sum('amount');

                $drugExpenses = $payments->where('status','debit')->where('payment_type','drugStock');
                $labExpenses = $payments->where('status','debit')->where('payment_type','labstock');


                $totalLabExpense = $labExpenses->sum('amount');
                $totalDrugExpense = $drugExpenses->sum('amount');

                $pnl = $payments->sortBy('created_at')->groupBy(function ($item){
                    return $item->created_at->format('d-M');
                })->map(function ($items){
                    return [
                        'revenue' => $items->where('status','credit')->sum('amount'),
                        'expenses' => $items->where('status','debit')->sum('amount')
                    ];
                });

                $drugPnl = $payments->sortBy('created_at')->groupBy(function ($item){
                    return $item->created_at->format('d-M');
                })->map(function ($items){
                    return [
                        'revenue' => $items->where('status','credit')->where('payment_type','drugSales')->sum('amount'),
                        'expenses' => $items->where('status','debit')->where('payment_type','drugStock')->sum('amount')
                    ];
                });

                $labPnl = $payments->sortBy('created_at')->groupBy(function ($item){
                    return $item->created_at->format('d-M');
                })->map(function ($items){
                    return [
                        'revenue' => $items->where('status','credit')->where('payment_type','labTest')->sum('amount'),
                        'expenses' => $items->where('status','debit')->where('payment_type','labstock')->sum('amount')
                    ];
                });

                $areaChat = [];
                $labAreaChat = [];
                $drugAreaChat = [];

                foreach ($pnl as $item => $value){
                    $areaChat = [...$areaChat, [
                        'date' => $item,
                        'expenses' => $value['expenses'],
                        'revenue' => $value['revenue']
                    ]];
                }
                foreach ($labPnl as $item => $value){
                    $labAreaChat = [...$labAreaChat, [
                        'date' => $item,
                        'expenses' => $value['expenses'],
                        'revenue' => $value['revenue']
                    ]];
                }
                foreach ($drugPnl as $item => $value){
                    $drugAreaChat = [...$drugAreaChat, [
                        'date' => $item,
                        'expenses' => $value['expenses'],
                        'revenue' => $value['revenue']
                    ]];
                }

                $departmentalChat = [
                    ['dept'=> 'consultation' , 'amount' => $totalConsultation],
                    ['dept'=> 'pharmacy' , 'amount' => $totalDrugSale],
                    ['dept'=> 'lab test' , 'amount' => $totalLabTest],
                    ['dept'=> 'Enrollment' , 'amount' => $totalEnrollment],
                ];


                if ($totalRevenue == 0){
                    $grossMargin = 0;
                }else{
                    $grossMargin = (($totalRevenue - $totalExpenses) / $totalRevenue) * 100;

                }
                $netProfit = $totalRevenue - $totalExpenses;


                $all_expenses = $payments->where('status','debit')->groupBy('payment_type')
                    ->map(function ($items){
                        return[
                            'value' =>   $items->sum('amount')
                        ];
                    })
                ;

                $expensesChart = [];

                foreach ($all_expenses as $key => $expense){
                    $expensesChart = [...$expensesChart, [
                        'name'=> $key,
                        'value'=> $expense['value'],
                    ]];
                }

                $pendingPayments = $payments->where('completion_status','pending')->where('outStanding_balance','>',0);




                $rankingDrugs = $drugStock;
                /* $rankingDrugs = $drugStock->flatMap(function($drug) {
                    return $drug->sales;
                });*/
                $drugSalesCount= [];
                $drugRevenue = [];
                $drugLeft = [];
                $drugQuantity = [];


                foreach ($rankingDrugs as $drug){
                    $drugSalesCount = [...$drugSalesCount, [
                        'name' => $drug['name'],
                        'no_of_sales' => $drug->sales->count(),

                    ]];
                }
                foreach ($rankingDrugs as $drug){
                    $drugRevenue = [...$drugRevenue, [
                        'name' => $drug['name'],
                        /*'revenue' => $drug->sales[0]->pivot->sum(function ($sale){
                            return $sale->unit_price * $sale->quantity;
                        }),*/
                        'revenue' => $drug->sales->sum(function ($sale){
                            return $sale->pivot->unit_Price * $sale->pivot->quantity;
                        })

                    ]];
                }
                foreach ($rankingDrugs as $drug){
                    $drugLeft = [...$drugLeft, [
                        'name' => $drug['name'],
                        'stock' => $drug->quantity,

                    ]];
                }
                foreach ($rankingDrugs as $drug){
                    $drugQuantity = [...$drugQuantity, [
                        'name' => $drug['name'],
                        'quantity_sold' => $drug->sales->sum('pivot.quantity'),
                    ]];
                }

                $drugSalesCollection = collect($drugSalesCount);
                $drugRevenueCollection = collect($drugRevenue);
                $drugLeftCollection = collect($drugLeft);
                $drugQuantityCollection = collect($drugQuantity);

                $sortedNumberOfSales = $drugSalesCollection->sortByDesc('value')->take(5);
                $sortedRevenue = $drugRevenueCollection->sortByDesc('revenue')->take(5);
                $sortedLeft = $drugLeftCollection->sortBy('stock')->take(5);
                $sortedQuantity = $drugQuantityCollection->sortByDesc('quantity_sold')->take(5);




                $labTestCount = [];
                $labTestRevenue = [];

                return response($rankingLabTest);

                foreach ($rankingLabTest as $test){

                    $labTestCount = [...$labTestCount, [
                        'name'=> $test['title'],
                        'no_of_test' => count($test?->labTests) > 0 ? $test?->labTests[0]?->count() : 0
                    ]];
                }

                foreach ($rankingLabTest as $test){
                    $labTestRevenue = [...$labTestRevenue, [
                        'name'=> $test['title'],
                        'revenue' =>count($test?->labTests) > 0 ?  $test?->labTests[0]?->sum('lab_test_amount') : 0
                        /*'no_of_test' =>count($test?->labTests) ?  $test?->labTests[0]?->payment?->sum('amount') : 0*/
                    ]];
                }





                $data = [
                    'users' => $allUsers,
                    'doctors' => $allDoctors,
                    'clerk' => $allClerk,
                    'nurses' => $allNurses,
                    'patient' => $allPatients,
                    'pharmasist' => $allPharmasist,
                    'accountant' => $allAccountant,
                    'labScientist' => $allLabAttendant,
                    'allStaff' => $allStaff,
                    'noOfStaff' => $allStaff->count(),

                    'drugStock' => $drugStock,
                    'pendingDrugRequest' => $pendingDrugStock,

                    'payments' => $payments,
                    'drugSales' => $drugSales,
                    'labTests' => $labTests,
                    'consultation' => $consultation,
                    'stockRequest' => $stockRequest,
                    'salary' => $salary,
                    'unitReport' => $unitReport,
                    'hospitalRates' => $hospitalRates,
                    'consultationRates' => $consultationRate,
                    'enrollmentRates' => $enrollmentRate,
                    'labRates' => $labTestRate,
                    'revenue' => $revenue,
                    'expenses' => $expenses,
                    'totalRevenue' => $totalRevenue,
                    'totalExpenses' => $totalExpenses,

                    'salaryAllowance' => $salaryAllowances,


                    'leaveApplication' => $leaveApplication,
                    'pendingLeaveApplication' => $leaveApplication->where('status', 'requested'),
                    'deniedLeaveApplication' => $leaveApplication->where('status', 'denied'),
                    'approvedLeaveApplication' => $leaveApplication->where('status', 'approved'),

                    'approvedDrugStockRequest' => $stockRequest->where('status','approved')->where('drug_stock_id','!=' , null),
                    'pendingDrugStockRequest' => $stockRequest->where('status','pending')->where('drug_stock_id','!=' , null),

                    'approvedLabStockRequest' => $stockRequest->where('status','approved')->where('lab_stock_id','!=' , null),
                    'pendingLabStockRequest' => $stockRequest->where('status','pending')->where('lab_stock_id','!=' , null),

                    'approvedLabStock' => $labStock->where('status', '!=','pending'),
                    'pendingLabStock' => $labStock->where('status','pending'),

                    'approvedDrugStock' => $drugStock->where('status','approved'),
                    'pendingDrugStock' => $drugStock->where('status','pending'),

                    'outOfStock' => $drugStock->where('status','outOfStock'),
                    'lowStock' => $drugStock->where('status','lowStock'),


                    'outPatientDiagnosis' => $diagnosis->where('ward_status','outPatient'),
                    'inwardPatientDiagnosis' => $diagnosis->where('ward_status','inPatient'),

                    'outPatient' => $outPatient,
                    'inPatient' => $inPatient,

                    'paidDrugSalesCount' => $drugSales->where('payment_status','paid')->count(),
                    'paidDrugSales' => $drugSales->where('payment_status','paid'),
                    'unpaidDrugSales' => $drugSales->where('payment_status','unpaid'),
                    'unpaidDrugSalesCount' => $drugSales->where('payment_status','unpaid')->count(),

                    'paidConsultationCount' => $consultation->where('payment_status','paid')->count(),
                    'paidConsultation' => $consultation->where('payment_status','paid'),
                    'unpaidConsultation' => $consultation->where('payment_status','unpaid'),
                    'unpaidConsultationCount' => $consultation->where('payment_status','unpaid')->count(),

                    'paidLabTestsCount' => $labTests->where('lab_test_payment_status','paid')->count(),
                    'paidLabTests' => $labTests->where('lab_test_payment_status','paid'),
                    'unpaidLabTests' => $labTests->where('lab_test_payment_status','unpaid'),
                    'unpaidLabTestsCount' => $labTests->where('lab_test_payment_status','unpaid')->count(),


                    'totalConsultation' => $totalConsultation,
                    'labPnlChart' => $labAreaChat,
                    'drugPnlChart' => $drugAreaChat,
                    'labExpense' => $labExpenses,
                    'drugExpense' => $drugExpenses,
                    'deptChart' => $departmentalChat,
                    'totalLabExpense'=> $totalLabExpense,
                    'totalDrugExpense'=> $totalDrugExpense,
                    'grossMargin'=> $grossMargin,
                    'netProfit' => $netProfit,
                    'expensesChart' => $expensesChart,
                    'totalEnrollment' => $totalEnrollment,
                    'pendingPayment' => $pendingPayments,
                    'totalLabTest' => $totalLabTest,
                    'totalDrugSale' => $totalDrugSale,
                    'totalSalary' => $totalSalary,


                    'totalDoctor' => $totalDoctors,
                    'totalNurses' => $totalNurses,
                    'totalLabScientist' => $totalLabScientist,
                    'totalPharmasist' => $totalPharmasist,
                    'totalClerk' => $totalClerk,
                    'totalPatient' => $totalPatient,
                    'totalAccountant' => $totalAccountant,


                    'drugSalesChart' => $sortedNumberOfSales,
                    'drugRevenueChart' => $sortedRevenue,
                    'drugRemainingChart' => $sortedLeft,
                    'drugQuantitySoldChart' => $sortedQuantity,

                    'labTestCount'=> $labTestCount,
                    'labTestRevenue'=> $labTestRevenue


                ];




                return response()->json(['message'=>'','data'=> $data],200);

            }else{

                $drugStock = DrugStock::with(['sales' => function($q) {
                    $q->where('payment_status','paid')->whereYear('sales.created_at', now()->year)->whereMonth('sales.created_at', now()->month);
                }])->get();

                $rankingLabTest = Rates::with(['labTests' => function($q) {
                    $q->with(['payment'])->where('lab_test_payment_status','paid')->whereYear('lab_tests.created_at', now()->year)->whereMonth('lab_tests.created_at', now()->month);
                }, ])->where('rate_type','labTest')->get();


                $pendingDrugStock = $drugStock->where('status', 'pendingApproval');


                $payments = Payment::with(['rates','labTest','sales','consultation','salaryAllowance','signedAccountant','patientUser'])->whereYear('created_at', now()->year)->whereMonth('created_at',now()->month)->get();

                $drugSales = Sales::with(['pharmasist'])->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                $labTests = LabTest::with(['labScientist'])->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                $consultation = AwaitingConsultation::with(['doctor'])->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                $stockRequest = StockRequest::with(['user','drugStock','labStock'])->get();
                //$stockRequest = StockRequest::with(['user','drugStock','labStock'])->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();

                $salary = SalaryAllowances::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->get();



                $revenue = $payments->where('status','credit');
                $expenses = $payments->where('status','debit');

                $totalRevenue = $revenue->sum('amount');
                $totalExpenses = $expenses->sum('amount');


                $totalSalary = $salary->sum('amount');

                $totalLabTest = $labTests->sum('lab_test_amount');

                //$totalDrugSale = LabTest::sum('total_amount');
                $totalDrugSale = $drugSales->sum('total_amount');

                $totalConsultation = $consultation->sum('amount');
                $totalEnrollment = $payments->where('status','credit')->where('payment_type','enrollment')->sum('amount');

                $drugExpenses = $payments->where('status','debit')->where('payment_type','drugStock');
                $labExpenses = $payments->where('status','debit')->where('payment_type','labstock');


                $totalLabExpense = $labExpenses->sum('amount');
                $totalDrugExpense = $drugExpenses->sum('amount');

                $pnl = $payments->sortBy('created_at')->groupBy(function ($item){
                    return $item->created_at->format('d-M');
                })->map(function ($items){
                    return [
                        'revenue' => $items->where('status','credit')->sum('amount'),
                        'expenses' => $items->where('status','debit')->sum('amount')
                    ];
                });

                $drugPnl = $payments->sortBy('created_at')->groupBy(function ($item){
                    return $item->created_at->format('d-M');
                })->map(function ($items){
                    return [
                        'revenue' => $items->where('status','credit')->where('payment_type','drugSales')->sum('amount'),
                        'expenses' => $items->where('status','debit')->where('payment_type','drugStock')->sum('amount')
                    ];
                });

                $labPnl = $payments->sortBy('created_at')->groupBy(function ($item){
                    return $item->created_at->format('d-M');
                })->map(function ($items){
                    return [
                        'revenue' => $items->where('status','credit')->where('payment_type','labTest')->sum('amount'),
                        'expenses' => $items->where('status','debit')->where('payment_type','labstock')->sum('amount')
                    ];
                });

                $areaChat = [];
                $labAreaChat = [];
                $drugAreaChat = [];

                foreach ($pnl as $item => $value){
                    $areaChat = [...$areaChat, [
                        'date' => $item,
                        'expenses' => $value['expenses'],
                        'revenue' => $value['revenue']
                    ]];
                }
                foreach ($labPnl as $item => $value){
                    $labAreaChat = [...$labAreaChat, [
                        'date' => $item,
                        'expenses' => $value['expenses'],
                        'revenue' => $value['revenue']
                    ]];
                }
                foreach ($drugPnl as $item => $value){
                    $drugAreaChat = [...$drugAreaChat, [
                        'date' => $item,
                        'expenses' => $value['expenses'],
                        'revenue' => $value['revenue']
                    ]];
                }

                $departmentalChat = [
                    ['dept'=> 'consultation' , 'amount' => $totalConsultation],
                    ['dept'=> 'pharmacy' , 'amount' => $totalDrugSale],
                    ['dept'=> 'lab test' , 'amount' => $totalLabTest],
                    ['dept'=> 'Enrollment' , 'amount' => $totalEnrollment],
                ];


                if ($totalRevenue == 0){
                    $grossMargin = 0;
                }else{
                    $grossMargin = (($totalRevenue - $totalExpenses) / $totalRevenue) * 100;

                }
                $netProfit = $totalRevenue - $totalExpenses;


                $all_expenses = $payments->where('status','debit')->groupBy('payment_type')
                    ->map(function ($items){
                        return[
                            'value' =>   $items->sum('amount')
                        ];
                    })
                ;

                $expensesChart = [];

                foreach ($all_expenses as $key => $expense){
                    $expensesChart = [...$expensesChart, [
                        'name'=> $key,
                        'value'=> $expense['value'],
                    ]];
                }

                $pendingPayments = $payments->where('completion_status','pending')->where('outStanding_balance','>',0);





                $rankingDrugs = $drugStock;
                /* $rankingDrugs = $drugStock->flatMap(function($drug) {
                    return $drug->sales;
                });*/
                $drugSalesCount= [];
                $drugRevenue = [];
                $drugLeft = [];
                $drugQuantity = [];


                foreach ($rankingDrugs as $drug){
                    $drugSalesCount = [...$drugSalesCount, [
                        'name' => $drug['name'],
                        'no_of_sales' => $drug->sales->count(),

                    ]];
                }
                foreach ($rankingDrugs as $drug){
                    $drugRevenue = [...$drugRevenue, [
                        'name' => $drug['name'],
                        /*'revenue' => $drug->sales[0]->pivot->sum(function ($sale){
                            return $sale->unit_price * $sale->quantity;
                        }),*/
                        'revenue' => $drug->sales->sum(function ($sale){
                            return $sale->pivot->unit_Price * $sale->pivot->quantity;
                        })

                    ]];
                }
                foreach ($rankingDrugs as $drug){
                    $drugLeft = [...$drugLeft, [
                        'name' => $drug['name'],
                        'stock' => $drug->quantity,

                    ]];
                }
                foreach ($rankingDrugs as $drug){
                    $drugQuantity = [...$drugQuantity, [
                        'name' => $drug['name'],
                        'quantity_sold' => $drug->sales->sum('pivot.quantity'),
                    ]];
                }

                $drugSalesCollection = collect($drugSalesCount);
                $drugRevenueCollection = collect($drugRevenue);
                $drugLeftCollection = collect($drugLeft);
                $drugQuantityCollection = collect($drugQuantity);

                $sortedNumberOfSales = $drugSalesCollection->sortByDesc('value')->take(5);
                $sortedRevenue = $drugRevenueCollection->sortByDesc('revenue')->take(5);
                $sortedLeft = $drugLeftCollection->sortBy('stock')->take(5);
                $sortedQuantity = $drugQuantityCollection->sortByDesc('quantity_sold')->take(5);


                
                $labTestCount = [];
                $labTestRevenue = [];

                foreach ($rankingLabTest as $test){

                    $labTestCount = [...$labTestCount, [
                        'name'=> $test['title'],
                        'no_of_test' =>  $test?->labTests?->count()
                    ]];
                }

                foreach ($rankingLabTest as $test){
                    $labTestRevenue = [...$labTestRevenue, [
                        'name'=> $test['title'],
                        'revenue' => $test->labTests->sum('lab_test_amount')
                        /*'no_of_test' =>count($test?->labTests) ?  $test?->labTests[0]?->payment?->sum('amount') : 0*/
                    ]];
                }





                $data = [
                    'users' => $allUsers,
                    'doctors' => $allDoctors,
                    'clerk' => $allClerk,
                    'nurses' => $allNurses,
                    'patient' => $allPatients,
                    'pharmasist' => $allPharmasist,
                    'accountant' => $allAccountant,
                    'labScientist' => $allLabAttendant,
                    'allStaff' => $allStaff,
                    'noOfStaff' => $allStaff->count(),

                    'drugStock' => $drugStock,
                    'pendingDrugRequest' => $pendingDrugStock,

                    'payments' => $payments,
                    'drugSales' => $drugSales,
                    'labTests' => $labTests,
                    'consultation' => $consultation,
                    'stockRequest' => $stockRequest,
                    'salary' => $salary,
                    'unitReport' => $unitReport,
                    'hospitalRates' => $hospitalRates,
                    'consultationRates' => $consultationRate,
                    'enrollmentRates' => $enrollmentRate,
                    'labRates' => $labTestRate,
                    'revenue' => $revenue,
                    'expenses' => $expenses,
                    'totalRevenue' => $totalRevenue,
                    'totalExpenses' => $totalExpenses,

                    'salaryAllowance' => $salaryAllowances,


                    'leaveApplication' => $leaveApplication,
                    'pendingLeaveApplication' => $leaveApplication->where('status', 'requested'),
                    'deniedLeaveApplication' => $leaveApplication->where('status', 'denied'),
                    'approvedLeaveApplication' => $leaveApplication->where('status', 'approved'),

                    'approvedDrugStockRequest' => $stockRequest->where('status','approved')->where('drug_stock_id','!=' , null),
                    'pendingDrugStockRequest' => $stockRequest->where('status','pending')->where('drug_stock_id','!=' , null),

                    'approvedLabStockRequest' => $stockRequest->where('status','approved')->where('lab_stock_id','!=' , null),
                    'pendingLabStockRequest' => $stockRequest->where('status','pending')->where('lab_stock_id','!=' , null),

                    'approvedLabStock' => $labStock->where('status', '!=','pending'),
                    'pendingLabStock' => $labStock->where('status','pending'),

                    'outOfStock' => $drugStock->where('status','outOfStock'),
                    'lowStock' => $drugStock->where('status','lowStock'),


                    'approvedDrugStock' => $drugStock->where('status','approved'),
                    'pendingDrugStock' => $drugStock->where('status','pending'),


                    'outPatientDiagnosis' => $diagnosis->where('ward_status','outPatient'),
                    'inwardPatientDiagnosis' => $diagnosis->where('ward_status','inPatient'),

                    'outPatient' => $outPatient,
                    'inPatient' => $inPatient,

                    'paidDrugSalesCount' => $drugSales->where('payment_status','paid')->count(),
                    'paidDrugSales' => $drugSales->where('payment_status','paid'),
                    'unpaidDrugSales' => $drugSales->where('payment_status','unpaid'),
                    'unpaidDrugSalesCount' => $drugSales->where('payment_status','unpaid')->count(),

                    'paidConsultationCount' => $consultation->where('payment_status','paid')->count(),
                    'paidConsultation' => $consultation->where('payment_status','paid'),
                    'unpaidConsultation' => $consultation->where('payment_status','unpaid'),
                    'unpaidConsultationCount' => $consultation->where('payment_status','unpaid')->count(),

                    'paidLabTestsCount' => $labTests->where('lab_test_payment_status','paid')->count(),
                    'paidLabTests' => $labTests->where('lab_test_payment_status','paid'),
                    'unpaidLabTests' => $labTests->where('lab_test_payment_status','unpaid'),
                    'unpaidLabTestsCount' => $labTests->where('lab_test_payment_status','unpaid')->count(),


                    'totalConsultation' => $totalConsultation,
                    'labPnlChart' => $labAreaChat,
                    'drugPnlChart' => $drugAreaChat,
                    'labExpense' => $labExpenses,
                    'drugExpense' => $drugExpenses,
                    'deptChart' => $departmentalChat,
                    'totalLabExpense'=> $totalLabExpense,
                    'totalDrugExpense'=> $totalDrugExpense,
                    'grossMargin'=> $grossMargin,
                    'netProfit' => $netProfit,
                    'expensesChart' => $expensesChart,
                    'totalEnrollment' => $totalEnrollment,
                    'pendingPayment' => $pendingPayments,
                    'totalLabTest' => $totalLabTest,
                    'totalDrugSale' => $totalDrugSale,
                    'totalSalary' => $totalSalary,
                    'totalDoctor' => $totalDoctors,
                    'totalNurses' => $totalNurses,
                    'totalLabScientist' => $totalLabScientist,
                    'totalPharmasist' => $totalPharmasist,
                    'totalClerk' => $totalClerk,
                    'totalPatient' => $totalPatient,
                    'totalAccountant' => $totalAccountant,

                    'drugSalesChart' => $sortedNumberOfSales,
                    'drugRevenueChart' => $sortedRevenue,
                    'drugRemainingChart' => $sortedLeft,
                    'drugQuantitySoldChart' => $sortedQuantity,

                    'labTestCount'=> $labTestCount,
                    'labTestRevenue'=> $labTestRevenue


                ];






                return response()->json(['message'=>'','data'=> $data],200);
            }



        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function getAdminPatientByRegNo(Request $request){

        $req = $request->all();

        /* todo finish up the getpatient labtest function*/
        try {



            //$patientLabTests = LabTest::with(['rates','labScientist.user','patient.user'])->where('patient_id',$req['patient_id'])->get();

            //$patientLabTests = User::with(['patient','patient.labtest.labScientist.user','patient.labtest.rates'])->where('regID',$req['regID'])->first();

            $patientDiagnosis = User::with(['patient.diagnosis' => function($query) {
                $query->with(['labtest.rates', 'consultation.doctor.user','diagnosisReport','sales.drugStock','doctor.user'])
                    ->orderBy('created_at', 'desc'); // Latest diagnosis first
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


    public function updateLeaveApplication (Request $request){

            $req = $request->all();

            try {

                $pendingLeaveApplication = LeaveApplication::where('id', $req['leaveApplication_id'])->first();

                $pendingLeaveApplication->update([
                   'remark' => $req['remark'] ?? '',
                   'status' => $req['status'],
                ]);
                $pendingLeaveApplication->refresh();

                return response()->json(['message'=>'Leave Stauts Updated Successfully','data'=> $pendingLeaveApplication],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

    public function approveDrugStock (Request $request){

        $req = $request->all();

        try {

            $pendingDrugStock = DrugStock::where('id', $req['id'])->first();

            if ($pendingDrugStock->status != 'pending'){
                return response()->json(['message'=>'DrugStock has already been Approved'],200);

            }

            if ($req['quantity'] == 0){
                $req['status'] = 'outOfStock';
            }
            else if ($req['quantity'] <= 30){
                $req['status'] = 'lowStock';
            }else if ($req['quantity'] > 30){
                $req['status'] = 'inStock';
            }



            $pendingDrugStock->update($req);
            $pendingDrugStock->refresh();

            return response()->json(['message'=>'DrugStock has been Approved','data'=> $pendingDrugStock],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }
    public function approveLabStock (Request $request){

        $req = $request->all();

        try {

            $pendingLabStock = LabStock::where('id', $req['id'])->first();

            if ($pendingLabStock->status != 'pending'){
                return response()->json(['message'=>'DrugStock has already been Approved'],200);

            }

            if ($req['quantity'] == 0){
                $req['status'] = 'outOfStock';
            }
            else if ($req['quantity'] <= 30){
                $req['status'] = 'lowStock';
            }else if ($req['quantity'] > 30){
                $req['status'] = 'inStock';
            }



            $pendingLabStock->update($req);
            $pendingLabStock->refresh();

            return response()->json(['message'=>'Lab Item has been Approved','data'=> $pendingLabStock],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }
    public function updateDrugStock (Request $request){

        $req = $request->all();

        try {

            $pendingDrugStock = DrugStock::where('id', $req['id'])->first();

            if ($req['quantity'] == 0){
               $req['status'] = 'outOfStock';
            }
            else if ($req['quantity'] <= 30){
               $req['status'] = 'lowStock';
            }else if ($req['quantity'] > 30){
              $req['status'] = 'inStock';
            }

            /*$pendingDrugStock->update([
                'amount' => $req['amount'],
                'status' => $req['status'] ?? $pendingDrugStock->status,
                'quantity' => $req['quantity'] ?? $pendingDrugStock->quantity,
            ]);*/



            $pendingDrugStock->update($req);
            $pendingDrugStock->refresh();

            return response()->json(['message'=>'DrugStock has been Updated Successfully','data'=> $pendingDrugStock],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }
    public function updatePayment (Request $request){

        $req = $request->all();

        try {

            $payment = Payment::where('id', $req['id'])->first();

            if ($req['outStanding_balance'] == 0){
               $req['completion_status'] = 'completed';
            }
            else if ($req['outStanding_balance'] > 0){
                $req['completion_status'] = 'pending';
            }




            $payment->update($req);
            $payment->refresh();

            return response()->json(['message'=>'DrugStock has been Updated Successfully','data'=> $payment],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }
    public function updateRates (Request $request){

        $req = $request->all();

        try {

            $payment = Rates::where('id', $req['id'])->first();


            $payment->update($req);
            $payment->refresh();

            return response()->json(['message'=>'Rate has been Updated Successfully','data'=> $payment],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }
    public function addRate (Request $request){

        $req = $request->all();

        try {

            $rate = Rates::create($req);


            return response()->json(['message'=>'Rate has been created Successfully','data'=> $rate],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }
    public function updateLabStock (Request $request){

        $req = $request->all();

        try {

            $pendingDrugStock = LabStock::where('id', $req['id'])->first();

            if ($req['quantity'] == 0){
               $req['status'] = 'outOfStock';
            }
            else if ($req['quantity'] <= 30){
               $req['status'] = 'lowStock';
            }else if ($req['quantity'] > 30){
              $req['status'] = 'inStock';
            }

            /*$pendingDrugStock->update([
                'amount' => $req['amount'],
                'status' => $req['status'] ?? $pendingDrugStock->status,
                'quantity' => $req['quantity'] ?? $pendingDrugStock->quantity,
            ]);*/



            $pendingDrugStock->update($req);
            $pendingDrugStock->refresh();

            return response()->json(['message'=>'Lab Item has been Updated Successfully','data'=> $pendingDrugStock],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function addAdminDrugStock(Request $request){

        $req = $request->all();



        try {
            $drugPayload = [
                'category_id' => $req['category_id'],
                'name' => $req['name'],
                'generic' => $req['generic'],
                'status' => 'approved',
                'quantity' => $req['quantity'],
                'description' => $req['description'],
                'amount' => $req['amount']

            ];

            $newDrugStock = DrugStock::create($drugPayload);

            return response()->json(['message'=>'New Drug Request has been created ','data'=> $newDrugStock],201);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }
    public function getDrugSales(Request $request){

        $req = $request->all();



        try {
           $drugStock = DrugStock::with(['sales','sales.pharmasist.user','sales.patient.user','sales.doctor.user','sales.payment','sales.drugStock'])->where('name','LIKE',"%".$req['name']."%")->get();

           if (!$drugStock){
               return response()->json(['message'=>'No drug Found'],201);

           }

            return response()->json(['message'=>'New Drug Request has been Fetched ','data'=> $drugStock],201);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }
    public function getLabTest(Request $request){

        $req = $request->all();



        try {
           $labTest = Rates::with(['labTests','labTests.labScientist.user','labTests.patient.user','labTests.doctor.user','labTests.payment','labTests.rates'])->where('title','LIKE',"%".$req['name']."%")->where('rate_type','labTest')->get();

           if (!$labTest){
               return response()->json(['message'=>'No Lab Test Found'],201);

           }

            return response()->json(['message'=>'New Lab Test has been Fetched ','data'=> $labTest],201);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }

/*    public function updateLabStock (Request $request){

        $req = $request->all();

        try {

            $pendingLabStock = LabStock::where('id', $req['labStock_id'])->first();

            if (!$pendingLabStock){
                return response()->json(['message'=>'No Lab Stock Record found'],200);

            }

            $pendingLabStock->update([
                'amount' => $req['amount'],
                'status' => $req['status'],
                'quantity' => $req['quantity'] ?? $pendingLabStock->quantity,
            ]);
            $pendingLabStock->refresh();

            return response()->json(['message'=>'Leave Stauts Updated Successfully','data'=> $pendingLabStock],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }*/

    public function addAdminLabStock(Request $request){

        $req = $request->all();



        try {
            $labPayload = [
                'category_id' => $req['category_id'],
                'name' => $req['name'],
                'generic' => $req['generic'],
                'status' => 'approved',
                'quantity' => $req['quantity'],
                'description' => $req['description'],

            ];

            $newLabStock = LabStock::create($labPayload);

            return response()->json(['message'=>'New Lab-Stock has been created ','data'=> $newLabStock],201);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }


    public function updateRestockRequest (Request $request){

        $req = $request->all();

        try {

            return DB::transaction(function () use($req){
                $stockRequest = StockRequest::where('id',$req['id'])->lockForUpdate()->first();

                if (!$stockRequest ){
                    return response()->json(['message'=>'No Request Found'],200);

                }

               /* if ($stockRequest->status == 'approved'){
                    return response()->json(['message'=>'Request has already been Approved'],200);

                }*/

                $newQuantity = $req['quantity'] ?? $stockRequest->quantity;

                $stockRequest->update([
                    'status' => 'approved',
                    'quantity' => $newQuantity
                ]);

                if ($stockRequest->lab_stock_id){

                    $pendingLabStock = LabStock::where('id', $stockRequest->lab_stock_id)->lockForUpdate()->first();


                    if ($pendingLabStock->quantity + $newQuantity == 0){
                        $req['status'] = 'outOfStock';
                    }
                    else if ($pendingLabStock->quantity + $newQuantity <= 30){
                        $req['status'] = 'lowStock';
                    }else if ($pendingLabStock->quantity + $newQuantity > 30){
                        $req['status'] = 'inStock';
                    }
                    $pendingLabStock->update([
                        'quantity' => $pendingLabStock->quantity + $newQuantity,
                        'status' => $req['status'],
                        'amount' => $req['lab_stock']['amount']
                    ]);

                    $pendingLabStock->refresh();

                    return response()->json(['message'=>'LabStock quantity Updated Successfully','data'=> $pendingLabStock],200);



                }elseif ($stockRequest->drug_stock_id){
                    $pendingDrugStock = DrugStock::where('id', $stockRequest->drug_stock_id)->lockForUpdate()->first();



                    if ($pendingDrugStock->quantity + $newQuantity == 0){
                        $req['status'] = 'outOfStock';
                    }
                    else if ($pendingDrugStock->quantity + $newQuantity <= 30){
                        $req['status'] = 'lowStock';
                    }else if ($pendingDrugStock->quantity + $newQuantity > 30){
                        $req['status'] = 'inStock';
                    }

                    $pendingDrugStock->update([
                        'quantity' => $pendingDrugStock->quantity + $newQuantity,
                        'status' => $req['status'],
                        'amount' => $req['drug_stock']['amount']
                    ]);

                    $pendingDrugStock->refresh();

                    return response()->json(['message'=>'DrugStock quantity Updated Successfully','data'=> $pendingDrugStock],200);

                }

            });





        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    /*todo modify the update user to check the userrole and update accordingly, same as with delete*/

    public function updateUser (Request $request){

            $req = $request->all();

            try {

                $editUser = User::with(['doctor','nurse','clerk','accountant','labScientist','pharmasist','patient'])->where('id', $req['user_id'])->first();

                if (!$editUser){
                    return response()->json(['message'=>'No user record Found'],400);
                }

                if ($editUser){

                    switch ($editUser['user_role']){
                        case 'doctor':
                            $editUser->doctor()->update($req['doctor'][0]);
                            break;

                        /*  case 'patient':
                              $patientPayload = [
                                  'user_id' => $user['id'],
                                  'blood_group' => $req['blood_group'],
                                  'genotype' => $req['genotype'],
                                  'nos_name' => $req['nos_name'],
                                  'nos_address' => $req['nos_address'],
                                  'nos_phone_no' => $req['nos_phone_no'],
                                  'insurance_id' => $req['insurance_id'],
                              ];
                              $attach = Patient::create($patientPayload);
                              break;*/

                        /*todo: continue to add other roles Nurses, pharmasist,labscientist,clerk,accountant*/
                        case 'nurse':
                            $editUser->nurse()->update($req['nurse'][0]);
                            break;

                        case 'pharmasist':
                            $editUser->pharmasist()->update($req['pharmasist'][0]);
                            break;

                        case 'labScientist':
                            $editUser->labScientist()->update($req['lab_scientist'][0]);
                            break;

                        case 'clerk':
                            $editUser->clerk()->update($req['clerk'][0]);
                            break;

                        case 'accountant':
                            $editUser->accountant()->update($req['accountant'][0]);
                            break;

                        case 'patient':
                            $editUser->patient()->update($req['patient']);
                            break;

                        default:
                            return response()->json(['message'=>"Failed to create role: {$editUser['user_role']} "],501);

                    }



                    $editUser->update($req);
                    $editUser->refresh();

                    return response()->json(['message'=>'User Updated Successfully','data'=> $editUser],200);



                    //return response()->json(['message' => 'Deleted Successful '],200);

                }
                else{
                    return response()->json(['message'=>'Invalid User TO delete'],401);
                }




            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }
    public function updateSuspension (Request $request){

            $req = $request->all();

            try {

                $editUser = User::with(['doctor','nurse','clerk','accountant','labScientist','pharmasist'])->where('id', $req['user_id'])->first();

                if (!$editUser){
                    return response()->json(['message'=>'No user record Found'],400);
                }

                if ($editUser){

                    $editUser->update($req);
                    $editUser->refresh();

                    return response()->json(['message'=>'User Updated Successfully','data'=> $editUser],200);



                    //return response()->json(['message' => 'Deleted Successful '],200);

                }
                else{
                    return response()->json(['message'=>'Invalid User TO delete'],401);
                }




            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }


    public function deleteUser (Request $request){

        $req = $request->all();

        try {

            $editUser = User::where('id', $req['user_id'])->first();

            if (!$editUser){
                return response()->json(['message'=>'No user record Found'],400);
            }
            if ($editUser){

                switch ($editUser['user_role']){
                    case 'doctor':
                       $editUser->doctor()->delete();
                        break;

                  /*  case 'patient':
                        $patientPayload = [
                            'user_id' => $user['id'],
                            'blood_group' => $req['blood_group'],
                            'genotype' => $req['genotype'],
                            'nos_name' => $req['nos_name'],
                            'nos_address' => $req['nos_address'],
                            'nos_phone_no' => $req['nos_phone_no'],
                            'insurance_id' => $req['insurance_id'],
                        ];
                        $attach = Patient::create($patientPayload);
                        break;*/

                    /*todo: continue to add other roles Nurses, pharmasist,labscientist,clerk,accountant*/
                    case 'nurse':
                        $editUser->nurse()->delete();
                        break;

                    case 'pharmasist':
                        $editUser->pharmasist()->delete();
                        break;

                    case 'labScientist':
                        $editUser->labScientist()->delete();
                        break;

                    case 'clerk':
                        $editUser->clerk()->delete();
                        break;

                    case 'patient':
                        $editUser->patient()->delete();
                        break;




                    default:
                        return response()->json(['message'=>"Failed to create role: {$editUser['user_role']} "],501);

                }

                $editUser->delete();

                return response()->json(['message'=>'User record has been deleted successfully'],200);



                //return response()->json(['message' => 'Deleted Successful '],200);

            }
            else{
                return response()->json(['message'=>'Invalid User TO delete'],401);
            }



        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


    /*perodical fetching of data*/

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
    public function getPayment (Request $request){

        $req = $request->all();

        try {



            $payments = Payment::with(['rates','labTest','sales','consultation','salaryAllowance','signedAccountant','patientUser'])->whereAny(['invoice_id','id'],$req['detail'])->first();




            $data = [
                'payments' => $payments,
            ];

            return response()->json(['message'=>'payment fetched successfully','data'=> $data],200);

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



}
