<?php

namespace App\Http\Controllers;

use App\Models\AwaitingConsultation;
use App\Models\Diagnosis;
use App\Models\DiagnosisReport;
use App\Models\Doctor;
use App\Models\DrugStock;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Rates;
use App\Models\Reports;
use App\Models\Sales;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DoctorController extends Controller
{
    public function getDoctorOverview(Request $request){


        /*todo remeber to include unit report*/
        try {
            $req = $request->all();

            $rates = Rates::where('rate_type','labTest')->get();

            $drugs = DrugStock::with(['stockRequest'])->get();
            $unitReport  = Reports::where('unit','doctor')->get();
            $doctor = Doctor::with(['diagnosis.patient.user','diagnosis.labtest.rates','diagnosis.sales.drugStock','diagnosis.patient.labtest.rates','diagnosis.patient.sales.drugStock','diagnosis.patient.consultation','user.diagnosisReport.diagnosis','user.salaryAllowances','user.leaveApplication','consultation.patient.user','consultation.diagnosis.diagnosisReport','consultation.diagnosis.sales.drugStock','consultation.diagnosis.labtest.rates',])->where('user_id',$request->user()->id)->first();

            $pendingConsultation = AwaitingConsultation::with(['patient.user'])->where('attendance_status','unseen')->whereDate('created_at',today())->get();
            $allConsultation = AwaitingConsultation::with(['patient.user','diagnosis.diagnosisReport','diagnosis.sales.drugStock','diagnosis.labtest.rates'])->whereDate('created_at',today())->get();

            $diagnosis = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->get();

            $patients = User::with(['patient.labtest.rates','patient.sales.drugStock','patient.consultation','patient.diagnosis' => function($query) use ($doctor) {
                $query->with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('doctor_id', $doctor->id)->orderBy('created_at','desc');
            }])->whereHas('patient.diagnosis', function($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })->get();


            /*  $patients = Patient::with(['user','diagnosis' => function($query) use ($doctor) {
                  $query->where('doctor_id', $doctor->id);
              }])->whereHas('diagnosis', function($query) use ($doctor) {
                  $query->where('doctor_id', $doctor->id);
              })->get();*/




            return response()->json(['data' => [
                'doctor' => $doctor,
                'noOfPendingConsultation'=>$pendingConsultation->count(),
                'noOfDailyConsultation'=>$allConsultation->count(),
                'dailyConsultation'=>$allConsultation,
                'patients' => $patients,
                'drugs' => $drugs,
                'rates' => $rates,
                'outPatient' => $diagnosis->where('ward_status','outPatient'),
                'inwardPatient' => $diagnosis->where('ward_status','inPatient'),
                'unitReport' => $unitReport, 'dailyPendingConsultation' => $pendingConsultation],

                'message'=>'User has been fetched successfully']);



        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }
    public function addPrescription (Request $request){

            $req = $request->all();

            try {


                $sales = Sales::with(['drugStock'])->where('id', $req['sales_id'])->first();

                if ($sales->doctor_id != $request->user()->doctor[0]['id']){
                    return response()->json(['message' => 'You did not prescribe this set of drugs hence you cannot add to it'],401);

                }

                if ($sales->payment_status == 'paid'){
                    return response()->json(['message' => 'This prescription has been paid for hence you cannot add to it'],401);

                }

                $sales->drugStock()->attach($req['drug_item']['drug_stock_id'],$req['drug_item']);


                return response()->json(['message'=>"Drug Added to sales {$req['sales_id']}",'data'=> $sales],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

    public function addLabtest (Request $request){

        $req = $request->all();

        try {


            $labTest = LabTest::with(['rates'])->where('id', $req['labTest_id'])->first();

            if ($labTest->doctor_id != $request->user()->doctor[0]['id']){
                return response()->json(['message' => 'You did not recomment this set of tests hence you cannot add to it'],401);

            }

            if ($labTest->lab_test_payment_status == 'paid'){
                return response()->json(['message' => 'This labTest has been paid for hence you cannot add to it'],401);

            }

            $labTest->rates()->attach($req['lab_item']['rates_id'],$req['lab_item']);


            return response()->json(['message'=>"Test Added to LabTest {$req['labTest_id']}",'data'=> $labTest],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function removePrescription (Request $request){

            $req = $request->all();

            try {


                $sales = Sales::with(['drugStock'])->where('id', $req['sales_id'])->first();

                if ($sales->doctor_id != $request->user()->doctor[0]['id']){
                    return response()->json(['message' => 'You did not prescribe this set of drugs hence you cannot add to it']);

                }

                if ($sales->payment_status == 'paid'){
                    return response()->json(['message' => 'This prescription has been paid for hence you cannot add to it']);

                }

                $sales->drugStock()->detach($req['drug_stock_id']);


                return response()->json(['message'=>"Drug removed from sales {$req['sales_id']}",'data'=> $sales],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }


    public function removeLabTest (Request $request){

            $req = $request->all();

            try {


                $test = LabTest::with(['rates'])->where('id', $req['labTest_id'])->first();

                if ($test->doctor_id != $request->user()->doctor[0]['id']){
                    return response()->json(['message' => 'You did not prescribe this set of drugs hence you cannot add to it']);

                }

                if ($test->lab_test_payment_status == 'paid'){
                    return response()->json(['message' => 'This prescription has been paid for hence you cannot remove from it']);

                }

                $test->rates()->detach($req['rates_id']);


                return response()->json(['message'=>"Test removed from sales {$req['labTest_id']}",'data'=> $test],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

    public function addDiagnosis(Request $request){

        try {
            $req = $request->all();



            if ( !Hash::check($req['password'],$request->user()->password)){
                return response()->json(['message'=>'invalid Authentication to create Diagnosis'],401);
            }



             return DB::transaction(function ()use ($req){
                 $diagnosisPayload = [
                     'patient_id' => $req['patient_id'],
                     'doctor_id' => $req['doctor_id'],
                     'description' => $req['description'],
                     'body_vitals' => $req['body_vitals'],
                     'ward_status' => $req['ward_status'],
                     'patients_complain' => $req['patients_complain'],
                     'initial_diagnosis' => $req['initial_diagnosis']?? '',
                     'final_diagnosis' => $req['final_diagnosis']?? '',

                 ];

                 /* Add record to the Diagnosis table*/
                 $diagnosis = Diagnosis::create($diagnosisPayload);

                 if($req['lab_test']){
                     $labtestPayload = [
                         'diagnosis_id' => $diagnosis['id'],
                         'lab_test_name' => $req['lab_test_name'],
                         'lab_test_description' => $req['lab_test_description'],
                         'patient_id' => $req['patient_id'],
                         'lab_test_amount' => $req['lab_test_amount'],
                         'doctor_id' => $req['doctor_id']
                         //'lab_test_result' => $req['lab_test_result'],

                     ];

                     /*attach( {rate_id: {amount,status,remark})*/

                     $labtest = LabTest::create($labtestPayload);

                    /* $attachData = [];
                     foreach ($req['test_lists'] as $test) {
                         $attachData[$test['rates_id']] = [
                             'amount' => $test['amount'],
                             'remark' => $test['remark'],
                             'status' => 'undone'  // You might want to add this
                         ];
                     }

// Now attach with the correct format
                     $labtest->rates()->attach($attachData);*/


                     $labtest->rates()->attach($req['test_list']);
                 }

                 /*todo if there is presctiptionName then addd to database*/

                 /* Note: The payload has to send prescription as true or false*/
                 if ($req['prescription']){
                     $salesPayload = [
                         'diagnosis_id' => $diagnosis['id'],
                         'total_amount' => $req['drug_amount'],
                         'patient_id' => $req['patient_id'],
                         'doctor_id' => $req['doctor_id']

                     ];

                     /*attach(drugs {id: {amount,quantity,unit_price})*/
                     $drugSales = Sales::create($salesPayload);
                     $drugSales->drugStock()->attach($req['drug_items']);

                 }
                 if (isset($req['consultation_id'])){
                     $consultation = AwaitingConsultation::where('id', $req['consultation_id'])
                         ->update(
                             [
                                 'doctor_id'=>$req['doctor_id'],
                                 'rates_id'=>$req['rate_id']?? null,
                                 'diagnosis_id' => $diagnosis->id,
                                 'attendance_status' => 'seen'
                             ]);

                 }


                 $diagnosisOverview = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('id',$diagnosis->id)->first();

                 return response()->json([
                     'success' => true,
                     'message' => 'Diagnosis created successfully',
                     'data' => [
                         'diagnosis' => $diagnosis,
                         'lab_test' => $labtest ?? null,
                         'sales' => $drugSales ?? null,
                         'consultation' => $consultation?? null,
                         'fullDiagnosis' => $diagnosisOverview
                     ]
                 ], 201);
            });


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()],500);
        }
    }


    /*this function updates the status to the prescribed drug ; change the status to inactive */

    public function updatePrescribedStatus (Request $request){

        $req = $request->all();

        try {

            $drugSales = Sales::with(['drugStock'])->where('id',$req['sales_id'])->first();

            if (!$drugSales){
                return response()->json(['message'=>'No Drug record found for this diagnosis'],203);

            }

            foreach ($req['drugPayload'] as $drugItem) {
                $drugSales->drugStock()->updateExistingPivot(
                    $drugItem['drug_stock_id'], $drugItem

                );
            }

            $drugSales->refresh();

            return response()->json(['message'=>'Drug status has been cancelled','data'=> $drugSales],201);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


    public function updateSinglePrescribedStatus (Request $request){

        $req = $request->all();

        try {

            $drugSales = Sales::with(['drugStock'])->where('id',$req['sales_id'])->first();

            if (!$drugSales){
                return response()->json(['message'=>'No Drug record found for this diagnosis'],203);

            }

            $drugSales->drugStock()->updateExistingPivot($req['drugPayload']['drug_stock_id'], $req['drugPayload']);


            $drugSales->refresh();

            return response()->json(['message'=>'Drug status has been cancelled','data'=> $drugSales],201);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function updateDiagnosis(Request $request){
        $req = $request->all();
        try {
           return DB::transaction(function ()use ($req,$request){

               $diagnosis = Diagnosis::with(['patient','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('id',$req['diagnosis_id'])->first();


               if ( !Hash::check($req['password'],$request->user()->password)){
                   return response()->json(['message'=>'invalid Authentication to create Diagnosis'],401);
               }


               if ($diagnosis->created_at->diffInHours(now()) > 27 ){
                   return response()->json(['message'=>"This diagnosis cannot be ultered any more, 27 hours time lime exceeded."],201);

               }

               /*ensure the same doctor that created the diagnosis can edit it*/
               if ( $request->user()->id !=  $diagnosis['doctor']['user']['id']){
                   return response()->json(['message'=>"This diagnosis was created by Dr. {$diagnosis['doctor']['user']['name']}, hence you cannot edit"],201);
               }

               $diagnosis->update($req['editPayload']);

               if (isset($req['sales_id'])){
                   $prescribedDrugs = $diagnosis->sales()->where('id',$req['sales_id'])->first();


                   if (!$prescribedDrugs){
                       return response()->json(['message'=>'No Drug Sales has been found']);
                   }



                   if ($prescribedDrugs['payment_status']=='paid'){
                       return response()->json(['message'=>"This drugs has already been paid for, you cannot edit it"],503);

                   }
                   if (isset($req['bulkPrescription'])){
                       foreach ($req['bulkPrescription'] as $drugItem) {
                           $prescribedDrugs->drugStock()->updateExistingPivot(
                               $drugItem['drug_stock_id'], $drugItem

                           );
                       }
                   }else if(isset($req['drug_id'])){
                       //$prescribedDrugs->drugStock()->syncWithoutDetaching($req['drug_id'],[
                       $prescribedDrugs->drugStock()->updateExistingPivot($req['drug_id'],[
                           'quantity' => $req['drug_quantity'],
                           'dosage' => $req['drug_dosage'],
                           'unit_price' => $req['unit_price']
                       ]);
                   }



               }

               /*todo continue the labtest and also make it a transaction*/
               if (isset($req['lab_test_id'])){
                   $labTests  = $diagnosis->labTest()->where('id',$req['lab_test_id'])->first();

                   /* check if labtest has been paid for*/
                   if (!$labTests ){
                       return response()->json(['message'=>'No lab Test found']);

                   }

                   if ($labTests['lab_test_payment_status'] == 'paid'){
                       return response()->json(['message'=>"This Lab Test has already been paid for, you cannot edit it"],503);

                   }

                   /*might later have to use sync */
                   $labTests->rates()->updateExistingPivot($req['rates_id'],[
                       'remark' => $req['remark'],
                       'amount' => $req['amount'],
                       'status' => $req['status']
                   ]);


               }
               $responseData = [
                   'success' => true,
                   'message' => 'Diagnosis updated successfully'
               ];


               $responseData['data']['diagnosis'] = $diagnosis;

               if (isset($req['sales_id']) && isset($prescribedDrugs)) {
                   $responseData['data']['prescription'] = [
                       'details' => $prescribedDrugs,
                       'drugs' => $prescribedDrugs->drugStock()->get()
                   ];
               }

               if (isset($req['lab_test_id']) && isset($labTests)) {
                   $responseData['data']['lab_test'] = [
                       'details' => $labTests,
                       'rates' => $labTests->rates()->get()
                   ];
               }

               $diagnosis->refresh();
               $fullDiagnosis = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('id',$req['diagnosis_id'])->first();


               return response()->json(['message'=>'data updated Successfully', 'data'=>$responseData,'fullDiagnosis'=>$fullDiagnosis], 200);

           }) ;
        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()],500);
        }

    }

    /*usually a nurse or another doctor can follow up on a diagnosis*/
    public function addDiagnosisReport(Request $request){
        $req = $request->all();

        try {
            /* The user would have to input his password to make record entry*/
            if ( !Hash::check($req['password'],$request->user()->password)){
                return response()->json(['message'=>'invalid Authentication to create Diagnosis'],401);
            }

            return Db::transaction(function ()use ($req,$request){

                $diagnosisPayload = [
                    'diagnosis_id' => $req['diagnosis_id'],
                    'user_id' => $request->user()->id,
                    'diagnosis_report' => $req['diagnosis_report'],
                ];

                $diagnosisReport = DiagnosisReport::create($diagnosisPayload);

                if($req['lab_test']){
                    $labtestPayload = [
                        'diagnosis_id' => $req['diagnosis_id'],
                        'lab_test_name' => $req['lab_test_name'],
                        'lab_test_description' => $req['lab_test_description'],
                        'patient_id' => $req['patient_id'],
                        'lab_test_amount' => $req['lab_test_amount'],
                        'doctor_id' => $req['doctor_id'],
                        'diagnosis_report_id' => $diagnosisReport->id

                    ];

                    /*attach( {rate_id: {amount,status,remark})*/

                    if (isset($req['ward_status'])){
                        $diagnosis = Diagnosis::where('id', $req['diagnosis_id'])->first();

                        $diagnosis->update([
                            'ward_status'=> $req['ward_status']
                        ]);
                    }




                    $labtest = LabTest::create($labtestPayload);
                    $labtest->rates()->attach($req['test_list']);
                }


                /* Note: The payload has to send prescription as true or false*/
                if (($req['prescription'])){
                    $salesPayload = [
                        'diagnosis_id' => $req['diagnosis_id'],
                        'total_amount' => $req['drug_amount'],
                        'patient_id' => $req['patient_id'],
                        'doctor_id' => $req['doctor_id'],
                        'diagnosis_report_id' => $diagnosisReport->id

                    ];


                    /*attach(drugs {id: {amount,quantity,unit_price})*/
                    $drugSales = Sales::create($salesPayload);
                    $drugSales->drugStock()->attach($req['drug_items']);



                }

                if (isset($req['consultation_id'])){
                    $consultation = AwaitingConsultation::where('id', $req['consultation_id'])
                        ->update(
                            [
                                'doctor_id'=>$req['doctor_id'],
                                /*'rates_id'=>$req['rate_id'],*/
                                'diagnosis_id' => $req['diagnosis_id'],
                                'attendance_status' => 'seen'
                            ]);

                }

                $diagnosisOverview = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('id',$req['diagnosis_id'])->first();



                return response()->json([
                    'success' => true,
                    'message' => 'Diagnosis created successfully',
                    'data' => [
                        'fullDiagnosis'=> $diagnosisOverview,
                        'diagnosisReport' => $diagnosisReport,
                        'lab_test' => $labtest ?? null,
                        'sales' => $drugSales ?? null,
                        'consultation' => $consultation ?? null
                    ]
                ], 201);

            });
        }catch (Exception $exception){
            return response()->json(['message'=>$exception->getMessage()],401);

        }



    }

    public function updateDiagnosisReport(Request $request){

        $req = $request->all();

        try {
            return DB::transaction(function ()use ($req,$request){

                $diagnosis = DiagnosisReport::with(['user','diagnosis.doctor.user','sales.drugStock','labTest.rates'])->where('id',$req['diagnosisReport_id'])->first();


                /*ensure the same doctor that created the diagnosis can edit it*/
                if ( $request->user()->id !== $diagnosis->user_id){
                    return response()->json(['message'=>"This diagnosis Report was created by Dr. {$diagnosis['diagnosis']['doctor']['user']['name']}, hence you cannot edit"],201);
                }

                $diagnosis->update($req['editPayload']);

                if (isset($req['sales_id'])){
                    $prescribedDrugs = $diagnosis->diagnosis->sales()->where('id',$req['sales_id'])->first();

                    if ($prescribedDrugs['payment_status']=='paid'){
                        return response()->json(['message'=>"This drugs has already been paid for, you cannot edit it"],503);

                    }

                    if (isset($req['bulkPrescription'])){
                        foreach ($req['bulkPrescription'] as $drugItem) {
                            $prescribedDrugs->drugStock()->updateExistingPivot(
                                $drugItem['drug_stock_id'], $drugItem

                            );
                        }
                    }else if(isset($req['drug_id'])){
                        //$prescribedDrugs->drugStock()->syncWithoutDetaching($req['drug_id'],[
                        $prescribedDrugs->drugStock()->updateExistingPivot($req['drug_id'],[
                            'quantity' => $req['drug_quantity'],
                            'dosage' => $req['drug_dosage'],
                            'unit_price' => $req['unit_price']
                        ]);
                    }


                }

                if (isset($req['ward_status'])){
                    $diagnosis = Diagnosis::where('id', $diagnosis->diagnosis->id)->first();

                    $diagnosis->update([
                        'ward_status'=> $req['ward_status']
                    ]);
                }

                /*todo continue the labtest and also make it a transaction*/
                if (isset($req['lab_test_id'])){
                    $labTests  = $diagnosis->diagnosis->labTest()->where('id',$req['lab_test_id'])->first();

                    /* check if labtest has been paid for*/

                    if ($labTests['lab_test_payment_status'] == 'paid'){
                        return response()->json(['message'=>"This Lab Test has already been paid for, you cannot edit it"],503);

                    }

                    if (isset($req['bulkLabTests'])){
                        foreach ($req['bulkLabTests'] as $labItem) {
                            $labTests->rates()->updateExistingPivot(
                                $labItem['rates_id'], $labItem

                            );
                        }
                    }else if(isset($req['drug_id'])){
                        $labTests->rates()->updateExistingPivot($req['rates_id'],[
                            'remark' => $req['remark'],
                            'amount' => $req['amount'],
                            'status' => $req['status']
                        ]);
                    }

                    /*might later have to use sync */
                    /*$labTests->rates()->updateExistingPivot($req['rates_id'],[
                        'remark' => $req['remark'],
                        'amount' => $req['amount'],
                        'status' => $req['status']
                    ]);*/

                }


                $responseData = [
                    'success' => true,
                    'message' => 'Diagnosis updated successfully'
                ];


                $responseData['data']['diagnosis'] = $diagnosis;

                if (isset($req['sales_id']) && isset($prescribedDrugs)) {
                    $responseData['data']['prescription'] = [
                        'details' => $prescribedDrugs,
                        'drugs' => $prescribedDrugs->drugStock()->get()
                    ];
                }

                if (isset($req['lab_test_id']) && isset($labTests)) {
                    $responseData['data']['lab_test'] = [
                        'details' => $labTests,
                        'rates' => $labTests->rates()->get()
                    ];
                }

                $diagnosisOverview = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('id',$diagnosis['diagnosis_id'])->first();



                return response()->json(['data'=> ['response' =>$responseData, 'overviewDiagnosis'=> $diagnosisOverview]], 200);


            }) ;
        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()],500);
        }

    }

    public function getDiagnosis(Request $request){

        $req = $request->all();

        /* todo finish up the getpatient labtest function*/
        try {



            //$patientLabTests = LabTest::with(['rates','labScientist.user','patient.user'])->where('patient_id',$req['patient_id'])->get();

            //$patientLabTests = User::with(['patient','patient.labtest.labScientist.user','patient.labtest.rates'])->where('regID',$req['regID'])->first();

            $patientDiagnosis = null;

            if (isset($req['diagnosis_id'])){
                $patientDiagnosis = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','sales.doctor.user','labTest.rates','labTest.doctor.user','consultation.doctor.user','diagnosisReport.user','diagnosisReport.sales.drugStock','diagnosisReport.sales.patient.user','diagnosisReport.sales.payment','diagnosisReport.labTest.rates','diagnosisReport.labTest.patient.user','diagnosisReport.labTest.payment'])
                    ->where('id', $req['diagnosis_id'])
                    ->first();
            }elseif( isset($req['details'])){
                $patientDiagnosis = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport.user'])
                    ->whereHas('patient.user', function ($query) use($req){
                        $query->whereAny(['regID','phone_no','email'],'=', $req['details']);
                    })
                    ->get();
            }



            if (!$patientDiagnosis){
                return response()->json(['message' => "No Record found for this Patient"],203);
            }

            return response()->json(['data' => $patientDiagnosis,'message'=>'User has been fetched successfully']);





        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


    public function getDiagnosisReport(Request $request){

        $req = $request->all();

        /* todo finish up the getpatient labtest function*/
        try {



            //$patientLabTests = LabTest::with(['rates','labScientist.user','patient.user'])->where('patient_id',$req['patient_id'])->get();

            //$patientLabTests = User::with(['patient','patient.labtest.labScientist.user','patient.labtest.rates'])->where('regID',$req['regID'])->first();

            $patientDiagnosis = null;

            if (isset($req['diagnosisReport_id'])){
                $patientDiagnosis = DiagnosisReport::with(['user','diagnosis.patient.user','diagnosis.doctor.user','sales.drugStock','sales.doctor.user','labTest.rates','labTest.doctor.user','sales.patient.user','sales.payment','labTest.patient.user','labTest.payment','diagnosis.consultation.doctor.user'])
                    ->where('id', $req['diagnosisReport_id'])
                    ->first();
            }elseif( isset($req['details'])){
                $patientDiagnosis = DiagnosisReport::with(['diagnosis.patient.user','diagnosis.doctor.user','sales.drugStock','sales.doctor.user','labTest.rates','labTest.doctor.user','diagnosis.consultation.doctor.user'])
                    ->whereHas('diagnosis.patient.user', function ($query) use($req){
                        $query->whereAny(['regID','phone_no','email'],'=', $req['details']);
                    })
                    ->get();
            }elseif( isset($req['diagnosis_id'])){
                $patientDiagnosis = DiagnosisReport::with(['diagnosis.patient.user','diagnosis.doctor.user','sales.drugStock','sales.doctor.user','labTest.rates','labTest.doctor.user','diagnosis.consultation.doctor.user'])
                    ->whereHas('diagnosis', function ($query) use($req){
                        $query->where('id', $req['diagnosis_id']);
                    })
                    ->get();
            }



            if (!$patientDiagnosis){
                return response()->json(['message' => "No Record found for this Patient"],203);
            }

            return response()->json(['data' => $patientDiagnosis,'message'=>'User has been fetched successfully']);





        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


    public function getPatientByRegNo(Request $request){

        $req = $request->all();

        /* todo finish up the getpatient labtest function*/
        try {



            //$patientLabTests = LabTest::with(['rates','labScientist.user','patient.user'])->where('patient_id',$req['patient_id'])->get();

            //$patientLabTests = User::with(['patient','patient.labtest.labScientist.user','patient.labtest.rates'])->where('regID',$req['regID'])->first();

            $patientDiagnosis = User::with(['patient.labtest.rates','patient.sales.drugStock','patient.sales.patient.user','patient.sales.doctor.user','patient.sales.payment','patient.labtest.doctor.user','patient.labtest.patient.user','patient.labtest.payment','patient.consultation','patient.diagnosis' => function($query) {
                $query->with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])
                    ->orderBy('created_at', 'desc'); // Latest diagnosis first
            }])
                ->whereAny(['regID','email','phone_no'], $req['regID'])
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

                /*if ($consultation->attendance_status === 'seen') {
                    return response()->json(['message' => 'Patient has already been seen by a consultant'], 400);
                }*/


                if ($consultation->payment_status == 'unpaid'){
                    return response()->json(['message'=>'Consultation rejected because Payment was not made'],201);

                }

                $consultation->update([
                    'attendance_status' => 'seen',
                    'doctor_id' => $req['doctor_id']

                ]);

                $consultation->refresh();

                $data =[
                    'consultation' => $consultation,

                ];


                return response()->json(['message'=>'Consultation successfully accepted','data'=> $data],201);

            });




        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


}




//public function updateDiagnosisReport(Request $request){
//
//    $req = $request->all();
//
//    try {
//        return DB::transaction(function ()use ($req,$request){
//
//            $diagnosis = Diagnosis::with(['patient','diagnosisReport','doctor.user','sales.drugStock','labTest'])->where('id',$req['diagnosis_id'])->first();
//
//
//            /*ensure the same doctor that created the diagnosis can edit it*/
//            if ( $request->user()->id !== $diagnosis['doctor_id']){
//                return response()->json(['message'=>"This diagnosis was created by Dr. {$diagnosis['doctor']['user']['name']}, hence you cannot edit"],201);
//            }
//
//            $diagnosis->diagnosisReport()->update($req['editPayload']);
//
//            if ($req['sales_id']){
//                $prescribedDrugs = $diagnosis->sales()->where('id',$req['sales_id'])->first();
//
//                if ($prescribedDrugs['payment_status']=='paid'){
//                    return response()->json(['message'=>"This drugs has already been paid for, you cannot edit it"],503);
//
//                }
//
//                $prescribedDrugs->drugStock()->updateExistingPivot($req['drug_id'],[
//                    'quantity' => $req['drug_quantity'],
//                    'dosage' => $req['drug_dosage'],
//                    'unit_price' => $req['unit_price']
//                ]);
//
//            }
//
//            /*todo continue the labtest and also make it a transaction*/
//            if ($req['lab_test_id']){
//                $labTests  = $diagnosis->labTests()->where('id',$req['lab_test_id'])->first();
//
//                /* check if labtest has been paid for*/
//
//                if ($labTests['lab_test_payment_status'] == 'paid'){
//                    return response()->json(['message'=>"This Lab Test has already been paid for, you cannot edit it"],503);
//
//                }
//
//                /*might later have to use sync */
//                $labTests->rates()->updateExistingPivot($req['rates_id'],[
//                    'remark' => $req['remark'],
//                    'amount' => $req['amount'],
//                    'status' => $req['status']
//                ]);
//
//            }
//
//
//            $responseData = [
//                'success' => true,
//                'message' => 'Diagnosis updated successfully'
//            ];
//
//
//            $responseData['data']['diagnosis'] = $diagnosis;
//
//            if (isset($req['sales_id']) && isset($prescribedDrugs)) {
//                $responseData['data']['prescription'] = [
//                    'details' => $prescribedDrugs,
//                    'drugs' => $prescribedDrugs->drugStock()->get()
//                ];
//            }
//
//            if (isset($req['lab_test_id']) && isset($labTests)) {
//                $responseData['data']['lab_test'] = [
//                    'details' => $labTests,
//                    'rates' => $labTests->rates()->get()
//                ];
//            }
//
//            return response()->json($responseData, 200);
//
//
//        }) ;
//    }catch (Exception $exception){
//        return response()->json(['message' => $exception->getMessage()],500);
//    }
//
//}
