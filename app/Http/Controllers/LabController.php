<?php

namespace App\Http\Controllers;

use App\Models\LabScientist;
use App\Models\LabStock;
use App\Models\LabTest;
use App\Models\Payment;
use App\Models\Reports;
use App\Models\Sales;
use App\Models\StockRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LabController extends Controller
{
    public function getLabOverview(Request $request){
        $req = $request->all();

        /*still todo: lab analytics*/

        try {
            $labAttendant = LabScientist::with(['labtest.rates','labtest.patient.user','labtest.labScientist.user','labtest.payment','labtest.doctor.user','user.stockRequest.labStock','user.leaveApplication','user.salaryAllowances'])
                ->where('user_id',$request->user()->id)->first();

            if (!$labAttendant){
                return response()->json(['message' => 'No Lab Attendant record found']);
            }

            $allLabStock = LabStock::all();

            $allLabSale = LabTest::with(['rates','patient.user','labScientist.user','doctor.user','payment'])->whereMonth('created_at', now()->month)->whereDay('created_at',now()->day)->get();
            $myRestockRequest  = StockRequest::with(['labStock','user'])->where('lab_stock_id','!=' ,null)->where('user_id', $request->user()->id)->get();
            $restockRequest  = StockRequest::with(['labStock','user'])->where('lab_stock_id','!=' ,null)->get();
            $pendingRestock  = StockRequest::with(['labStock','user'])->where('lab_stock_id','!=' ,null)->where('status','pending')->get();
            $lowStock  = $allLabStock->where('quantity', '<' , 5)->where('quantity', '>' , 0);
            $pendingRequest  = $allLabStock->where('status', 'pending');
            $outOfStock  = $allLabStock->where('quantity', '=' , 0);
            $totalRevenue = $labAttendant->labtest()->whereMonth('created_at',now()->month)->sum('lab_test_amount');



            $unitReport = Reports::where('unit', 'lab')->get();

            return response()->json(['data' => ['labAttendant' => $labAttendant,'allLabTest'=>$allLabSale,'myLabRequest' => $myRestockRequest, 'pendingRestockRequest' => $pendingRestock, 'totalRevenue' => $totalRevenue, 'restockRequest' => $restockRequest, 'pendingRequest' => $pendingRequest, 'outOfStock' => $outOfStock, 'lowStock' => $lowStock, 'unitReport' => $unitReport,'allLabStock'=>$allLabStock],'message'=>'User has been fetched successfully']);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function getPatientLabTest(Request $request){

        $req = $request->all();

        /* todo finish up the getpatient labtest function*/
        try {



                //$patientLabTests = LabTest::with(['rates','labScientist.user','patient.user'])->where('patient_id',$req['patient_id'])->get();

                //$patientLabTests = User::with(['patient','patient.labtest.labScientist.user','patient.labtest.rates'])->where('regID',$req['regID'])->first();

                    $patientLabTests = User::with(['patient.diagnosis','patient.labtest' => function($query) {
                        $query->with(['labScientist.user', 'rates','doctor.user','payment'])
                            ->orderBy('created_at', 'desc'); // Latest prescriptions first
                    }])
                        ->where('regID', $req['regID'])
                        ->first();


            if (!$patientLabTests){
                return response()->json(['message' => "No Record found for this Patient"],203);
            }

            return response()->json(['data' => $patientLabTests,'message'=>'User has been fetched successfully']);





        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


    public function updateLabStatus(Request $request){
        try {
            $req = $request->all();

            return DB::transaction(function ()use ($req,$request){


                $patientLabTest = LabTest::with(['rates','patient.user','labScientist.user','doctor.user','payment'])->where('id',$req['labtest_id'])->first();

                if (!$patientLabTest){
                    return response()->json(['message'=>'No labTest found please check with your administrator'],200);

                }

                if ($patientLabTest->lab_test_payment_status !== 'paid'){
                    return response()->json(['message'=>'Payment has not been made for this Lab-test'],200);

                }

                if ($patientLabTest->lab_test_progress_status == 'pending' || $patientLabTest->lab_test_progress_status == 'completed'){
                    return response()->json(['message'=>"test is already been done by {$patientLabTest->labScientist['user']['name']}"],200);

                }



                $patientLabTest->update([
                    'lab_test_progress_status' => 'pending',
                    'lab_scientist_id' => $request->user()->labScientist[0]->id
                ]);



                foreach ($patientLabTest->rates as $test){

                    $patientLabTest->rates()->updateExistingPivot($test->id,[
                        'status' => 'pending'
                    ]);

                }

                $patientLabTest->refresh();

                return response()->json(['data'=>$patientLabTest, 'message'=>'LabTest has been Updated'],201);


                /*for a prepaid */

                /*$patientUser = User::where('id',$req['patientUser_id'])->lockForUpdate()->first();

                $balance = $patientUser->wallet_balance;


                $patientLabTest = LabTest::with(['rates'])->where('id',$req['labtest_id'])->first();

                if ($balance < $patientLabTest->lab_test_amount ){
                    throw new Exception('Insufficient Balance. Please go to the accountant to make wallet deposit',402);

                }
                $patientUser->update([
                    'wallet_balance' => $balance - $patientLabTest->lab_test_amount
                ]);

                $paymentEntry = Payment::create([
                     'patient_user_id' => $req['patientUser_id'],
                    'payment_type' => 'labTest',
                    'title' => $req['title'],
                    'invoice_id' => 'lbt'.Str::random(7),
                    'amount' => $patientLabTest->lab_test_amount,
                    'status' => 'credit'
                ]);

                $patientLabTest->update([
                    'lab_test_progress_status' => 'pending',
                    'lab_test_payment_status' => 'paid',
                    'labScientist_id' => $request->user()->id,

                ]);



                foreach ($patientLabTest->rates as $test){

                    $patientLabTest->rates()->updateExistingPivot($test->id,[
                        'status' => 'pending'
                    ]);

                }

                $patientLabTest->refresh();

                return response()->json(['data'=>$patientLabTest, 'message'=>'LabTest has been Updated'],201);
*/


            });





        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function uploadLabResult(Request $request){

        /*get the labtest via labtest_id
            -update the result column,progress_status to completed
            -if image also update the result image column
            - update the progress status in the pivot to completed
        */

        $req = $request->all();





        return DB::transaction(function ()use ($req, $request){

            $labTest = LabTest::with(['rates'])->where('id', $req['lab_test_id'])->first();



            if ($request->user()->labScientist[0]['id'] !== $labTest->lab_scientist_id){

                return response()->json(['message' => "You are not the Authenticated to upload result for this test"]);
            }

            if ($request->hasFile('lab_result_image')){
                $img = $request->file('lab_result_image')->store('result_image','public');

                $req['lab_test_result_image'] = $img;
            }

            $payload = [
              'lab_test_result_image' => $req['lab_test_result_image'] ?? null,
              'lab_test_result' => $req['lab_test_result'],
              'lab_test_progress_status' => "completed",

            ];

            $labTest->update($payload);

            $labTest->refresh();

            return response()->json(['data'  => $labTest, 'message'=>'Lab Test has been completed and results uploaded successfully']);

        });

    }

    public function updatePivotStatus(Request $request){


        /*get the id of the */
        try {

            $req = $request->all();

            $labTest = LabTest::with(['rates'])->where('id', $req['lab_test_id'])->first();

            if ($request->user()->labScientist[0]['id'] !== $labTest->lab_scientist_id){

                return response()->json(['message' => "You are not the Authenticated to upload result for this test"]);
            }

            $labTest->rates()->updateExistingPivot($req['rate_id'],[
                'status' => 'completed',
                'remark' => $req['result']
            ]);
            $labTest->refresh();

            return response()->json(['data'=>$labTest, 'message' => 'Lab-test completed successfully'],);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }

    public function addLabStockRequest (Request $request){

        $req = $request->all();

        try {
            $labPayload = [
                'category_id' => $req['category_id'] ?? 1,
                'name' => $req['name'],
                'status' => 'pending',
                'quantity' => $req['quantity'],
                'description' => $req['description'],

            ];

            $newLabStock = LabStock::create($labPayload);
            return response()->json(['message'=>'New Lab Request has been Sent for approval ','data'=> $newLabStock],203);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function editLabResult (Request $request){

        $req = $request->all();

        try {
            $editLabTest = LabTest::with(['rates'])->where('id', $req['lab_test_id'])->first();

            if ($request->user()->labScientist[0]["id"] !== $editLabTest->lab_scientist_id){

                return response()->json(['message' => "You are not the Authenticated to upload result for this test"]);
            }

            $payload = [];

            if (isset($req['lab_test_result'])){
                $payload['lab_test_result'] = $req['lab_test_result'];
            }


            if ($request->hasFile('lab_test_result_image')){
                $img = $request->file('lab_test_result_image')->store('lab_results','public');

                $payload['lab_test_result_image'] = $img;
            }

            $editLabTest->update($payload);

            $editLabTest->refresh();


            return response()->json(['message'=>' Lab-test has been updated successfully','data'=> $editLabTest],200);



        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function labrestockLabRequest (Request $request){

        $req = $request->all();

        try {

            $restockLabPayload = [
                'lab_stock_id' => $req['labStock_id'],
                'user_id' => $request->user()->id,
                'quantity' => $req['quantity'],
                'status' => 'pending',
                'title' => $req['title'],
                'notes' => $req['notes'] || null
            ];

            $newRestockRequest = StockRequest::create($restockLabPayload);

            return response()->json(['message'=>'You request has been logged successfully','data'=> $newRestockRequest],203);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function UpdateEvery (Request $request){

            $req = $request->all();

            try {
                return DB::transaction(function ()use ($req, $request){

                    $labTest = LabTest::with(['rates'])->where('id', $req['lab_test_id'])->first();



                    if ($request->user()->labScientist[0]['id'] !== $labTest->lab_scientist_id){

                        return response()->json(['message' => "You are not the Authenticated to upload result for this test"]);
                    }

                        if ($labTest->lab_test_payment_status == 'unpaid'){

                       return response()->json(['message' => "Payment has not been done for this labTest "],403);
                   }



                    if ($request->hasFile('lab_result_image')){
                        $img = $request->file('lab_result_image')->store('result_image','public');

                        $req['lab_test_result_image'] = $img;
                    }

                    $payload = [
                        'lab_test_result_image' => $req['lab_test_result_image'] ?? null,
                        'lab_test_result' => $req['lab_test_result'],
                        'lab_test_progress_status' => $req['lab_test_progress_status'],

                    ];

                    $labTest->update($payload);


                    foreach ($req['rates'] as $rate){

                        $labTest->rates()->updateExistingPivot($rate['id'],
                            [
                                'remark' => $rate['pivot']['remark'],
                                'status' => $rate['pivot']['status']
                            ]
                        );
                    }

                    if ($req['lab_test_progress_status'] == 'completed'){
                        foreach ($req['rates'] as $rate){

                            $labTest->rates()->updateExistingPivot($rate['id'],
                                [
                                    'status' => 'completed'
                                ]
                            );
                        }
                    }



                    $labTest->refresh();

                    return response()->json(['data'  => $labTest, 'message'=>'Lab Test has been completed and results uploaded successfully']);

                });

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }




}
