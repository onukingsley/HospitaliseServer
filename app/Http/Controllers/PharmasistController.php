<?php

namespace App\Http\Controllers;

use App\Models\DrugStock;
use App\Models\Payment;
use App\Models\Pharmasist;
use App\Models\Reports;
use App\Models\Sales;
use App\Models\StockRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PharmasistController extends Controller
{
    public function getPharmasistOverview(Request $request){

        $req = $request->all();


        try {
            $pharmasist = Pharmasist::with(['sales.drugStock','sales.patient.user','sales.pharmasist.user','sales.payment','sales.doctor.user','user.leaveApplication','user.salaryAllowances','user.stockRequest.drugStock'])
                ->where('user_id',$request->user()->id)->first();

            $drugStock = DrugStock::all();
            $allDrugSale = Sales::with(['drugStock','patient.user','pharmasist.user','doctor.user','payment'])->whereDay('created_at',now()->day)->get();

            $unitReport  = Reports::where('unit','pharmasist')->get();
            $myRestockRequest  = StockRequest::with(['drugStock','user'])->where('drug_stock_id','!=' ,null)->where('user_id', $request->user()->id)->get();
            $restockRequest  = StockRequest::with(['drugStock','user'])->where('drug_stock_id','!=' ,null)->get();
            $pendingRestock  = StockRequest::with(['drugStock','user'])->where('drug_stock_id','!=' ,null)->where('status','pending')->get();
            $lowStock  = $drugStock->where('quantity', '<' , 30)->where('quantity', '>' , 0);
            $pendingRequest  = $drugStock->where('status', 'pending');
            $outOfStock  = $drugStock->where('quantity', '=' , 0);
            $totalRevenue = $pharmasist->sales()->whereMonth('created_at',now()->month)->sum('total_amount');

            return response()->json(['data' => ['pharmasist' => $pharmasist, 'allDrugSale' => $allDrugSale, 'myDrugRequest' => $myRestockRequest, 'pendingRestockRequest' => $pendingRestock, 'totalRevenue' => $totalRevenue, 'restockRequest' => $restockRequest, 'pendingRequest' => $pendingRequest, 'outOfStock' => $outOfStock, 'lowStock' => $lowStock,'unitReport' => $unitReport,'drugStock'=>$drugStock],'message'=>'User has been fetched successfully']);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }

    }

    public function getPatientPrescriptionByRegID(Request $request){
        $req = $request->all();

        try {

            /*if ($req['patient_id']){
                $patientPrescription = Sales::with(['drugStock','pharmasist.user','patient.user'])->where('patient_id',$req['patient_id'])->get();

            }
            elseif ($req['diagnosis_id']){
                $patientPrescription = Sales::with(['drugStock'])->where('diagnosis_id',$req['diagnosis_id'])->get();
            }

            if ($patientPrescription->count() == 0){
                return response()->json(['message' => "No Prescription found for this Patient"],203);

                        //return response()->json(['message'=>'Patient Prescription fetched successfully', 'data'=>$patientPrescription],200);

            }*/

            //$patientPrescription = User::with(['patient','patient.sales.pharmasist.user','patient.sales.drugStock'])->where('regID',$req['regID'])->first();

            $patientPrescription = User::with(['patient.diagnosis','patient.sales' => function($query) {
                $query->with(['pharmasist.user', 'drugStock','doctor.user','payment'])
                    ->orderBy('created_at', 'desc'); // Latest prescriptions first
            }])
                ->where('regID', $req['regID'])
                ->first();

            if (!$patientPrescription){
                return response()->json(['message' => "No Record found for this Patient"],203);
            }

            return response()->json(['data' => $patientPrescription,'message'=>'User has been fetched successfully']);




        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


    public function getSalesDetails(Request $request){
        $req = $request->all();

        try {


            $patientPrescription = Sales::with(['drugStock','pharmasist.user','patient.user','doctor.user','payment'])->where('id',$req['sales_id'])->first();


            if (!$patientPrescription){
                return response()->json(['message' => "No Record found for this Patient"],203);
            }

            return response()->json(['data' => $patientPrescription,'message'=>'User has been fetched successfully']);




        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    /**
     * @throws Exception
     */
    public function updateDelieveryStatus(Request $request){
        /* for postpaid this method only updates the delivery status after dispensing the drug

            for prepaid, amount is been sent along-side the request and the patient's wallet balance is debited,
            delivery_status is changed, then paid status is changed as well.
        */
        $req = $request->all();

        try {
           return  DB::transaction(function ()use($req,$request){

                    $sales = Sales::with(['drugStock','payment','patient'])->where('id',$req['sales_id'])->first();


                    //for postPaid payment
                    if ($sales->payment_status !== 'paid'){
                        return response()->json(['message'=>'Payment has not been made for this Prescription'],201);
                    }
                   if ($sales->delivery_status == 'delivered'){
                       return response()->json(['message'=>'This prescription has already been delivered'],201);
                   }
                   if ($sales->payment->completion_status !== 'completed'){
                   return response()->json(['message'=>"Please refer Patient to Accounting unit for unsettled Payment of #". $sales->payment->outStanding_balance],201);
                   }



               if (!$req['isSame']){
                        /*sync the drugStock table; update altered_by_pharmasist column to 1; alter the payment*/

                        /*$sales->drugStock()->sync($req['cart']);*/


                        $dataCart = [];
                       foreach ( $req['cart'] as $item){

                           $dataCart[$item['id']] = [
                               'quantity' => $item['pivot']['quantity'],
                               'dosage' => $item['pivot']['dosage'] ,
                               'unit_price' => $item['pivot']['unit_price'] ,
                               'duration' => $item['pivot']['duration'] ,
                               'route' => $item['pivot']['route'] ,
                               'instruction' => $item['pivot']['instruction'] ,
                               'status' => 'active',
                           ];
                       }

                        $sales->drugStock()->sync($dataCart);

                        $sales->update([
                            'altered_by_pharmasist' => '1',
                            'pharmasist_id' => $request->user()->pharmasist[0]['id'],
                            'total_amount' => $req['total_amount'],
                            //'delivery_status' => 'delivered'

                        ]);

                       /* $sales->payment()->update([
                           'amount' => $req['total_amount']
                        ]);*/
                   if ((int)$sales->payment->amount != (int)$req['total_amount']){

                       $balance = ((int)$sales->payment->amount - (int)$req['total_amount']);

                       $sales->payment()->update([
                           'amount' => $req['total_amount'],
                           'completion_status' => 'pending',
                           'outStanding_balance'=> (string) ((int)$sales->payment->amount - (int)$req['total_amount'])
                       ]);
                       return response()->json(['message'=>"Payment has been Altered, with an outstanding balance of $balance. please refer patient to accounting dept"],201);
                   }

                   $sales->refresh();

                        $quantity = $sales->drugStock;



                        foreach ($quantity as $individualDrugStock){
                            $oneSingleDrug = $individualDrugStock->pivot->quantity;
                            $oneDrugStock = $individualDrugStock->quantity;

                            if ($oneDrugStock - $oneSingleDrug < 0){
                                throw new Exception("Insufficient Quantity for $individualDrugStock->name",401);
                            }
                            if ($oneDrugStock - $oneSingleDrug == 0){
                                $individualDrugStock->update([
                                    'status' => 'outOfStock'
                                ]);
                            }
                            else if ($oneDrugStock - $oneSingleDrug <= 30){
                                $individualDrugStock->update([
                                    'status' => 'lowStock'
                                ]);
                            }else if ($oneDrugStock - $oneSingleDrug > 30){
                                $individualDrugStock->update([
                                    'status' => 'inStock'
                                ]);
                            }

                            $individualDrugStock->update([
                                'quantity' => $oneDrugStock - $oneSingleDrug
                            ]);



                        }
                        $sales->refresh();

                        return response()->json(['message'=>'Prescription has been altered and dispensed to the patient','data'=>['sales'=>$sales]],200);



                    }else{
                        $sales->update([
                            'pharmasist_id' => $request->user()->pharmasist[0]['id'],
                            'delivery_status' => 'delivered'
                        ]);
                        $sales->refresh();

                        $quantity = $sales->drugStock;



                        foreach ($quantity as $individualDrugStock){
                            $oneSingleDrug = $individualDrugStock->pivot->quantity;
                            $oneDrugStock = $individualDrugStock->quantity;

                            if ($oneDrugStock - $oneSingleDrug < 0){
                                throw new Exception("Insufficient Quantity for $individualDrugStock->name",401);
                            }
                            if ($oneDrugStock - $oneSingleDrug == 0){
                                $individualDrugStock->update([
                                    'status' => 'outOfStock'
                                ]);
                            }
                            else if ($oneDrugStock - $oneSingleDrug <= 30){
                                $individualDrugStock->update([
                                    'status' => 'lowStock'
                                ]);
                            }else if ($oneDrugStock - $oneSingleDrug > 30){
                                $individualDrugStock->update([
                                    'status' => 'inStock'
                                ]);
                            }

                            $individualDrugStock->update([
                                'quantity' => $oneDrugStock - $oneSingleDrug
                            ]);


                        }

                        $sales->refresh();


                        return response()->json(['message'=>'Prescription has been dispensed to the patient','data'=>['sales'=>$sales]],200);

                    }




               /*for Prepaid: note userTable should have column wallet_balance */

              /* $patientUser = User::where('id',$req['patientUser_id'])->first();

               $balance = $patientUser->wallet_balance;

               $sales = Sales::with('drugStock')->where('id',$req['sales_id'])->first();

               if ($balance < $sales->total ){
                   throw new Exception('Insufficient Balance. Please go to the accountant to make wallet deposit',402);

               }

               $patientUser->update([
                  'wallet_balance' => $balance - $sales->total
               ]);

               $paymentEntry = Payment::create([
                   'patient_user_id' => $req['patientUser_id'],
                   'payment_type' => 'drugSales',
                   'title' => $req['title'],
                   'invoice_id' => 'Drg'.Str::random(7),
                   'amount' => $sales->total,
                   'status' => 'credit'
               ]);

               $sales->update([
                   'pharmasist_id' => $request->user()->id,
                   'delivery_status' => 'delivered',
                   'payment_status' => 'paid'
               ]);
               $sales->refresh();

               $quantity = $sales->drugStock;



               foreach ($quantity as $individualDrugStock){
                   $oneSingleDrug = $individualDrugStock->pivot->quantity;
                   $oneDrugStock = $individualDrugStock->quantity;

                   if ($oneDrugStock - $oneSingleDrug < 0){
                       throw new Exception("Insufficient Quantity for $individualDrugStock->name",401);
                   }

                   $individualDrugStock->update([
                       'quantity' => $oneDrugStock - $oneSingleDrug
                   ]);


               }

                 return response()->json(['message'=>'Prescription has been dispensed to the patient','data'=>''],200);

*/




           });
        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }


    public function addDrugRequest(Request $request){

        $req = $request->all();



        try {
            $drugPayload = [
                'category_id' => $req['category_id'] ?? '1',
                'name' => $req['name'],
                'generic' => $req['generic'],
                'status' => 'pendingApproval',
                'quantity' => $req['quantity'] ,
                'description' => $req['description'],

            ];

            $newDrugStock = DrugStock::create($drugPayload);

             return response()->json(['message'=>'New Drug Request has been Sent for approval ','data'=> $newDrugStock],201);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }


    public function drugRestockRequest(Request $request){

        $req = $request->all();




        try {
            $drugRequestPayload = [
                'drug_stock_id' => $req['drugStock_id'],
                'user_id' => $request->user()->id,
                'quantity' => $req['quantity'],
                'status' => 'pending',
                'title' => $req['title'],
                'notes' => $req['notes'] || null

                /*todo the unitprice would be updated upon approval by the accountant or admin*/

            ];

            $drugRestockRequest = StockRequest::create($drugRequestPayload);

            $requestRestock = StockRequest::with(['drugStock','user'])->where('id', $drugRestockRequest->id)->first();

            return response()->json(['message'=>'New Drug Restock Request has been Sent for approval ','data'=> $requestRestock],203);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }

}
