<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use App\Models\DiagnosisReport;
use App\Models\Nurses;
use App\Models\Reports;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NurseController extends Controller
{

    public function getNurseOverview (Request $request){

        $req = $request->all();

        try {

            $getNurseDetails = Nurses::with(['user.diagnosisReport.diagnosis','user.diagnosisReport.diagnosis.patient.user','user.diagnosisReport.diagnosis.diagnosisReport','user.diagnosisReport.diagnosis.labTest.rates','user.diagnosisReport.diagnosis.sales.drugStock','user.leaveApplication','user.salaryAllowances'])->where('user_id',$request->user()->id)->first();

            if (!$getNurseDetails){
                return response()->json(['message' => 'No Nurse record found']);
            }

            /*still todo  get all diagnosis and split it by inward and outpatient.
             -todo then get unit report
            */

            $unitReport = Reports::where('unit','nurse')->get();

            $diagnosis = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates'])->get();

            $patients = User::with(['patient.labtest.rates','patient.sales.drugStock','patient.consultation','patient.diagnosis' => function($query) use ($getNurseDetails) {
                $query->with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->orderBy('created_at','desc');
            }])->whereHas('patient.diagnosis.diagnosisReport', function($query) use ($getNurseDetails) {
                $query->where('user_id', $getNurseDetails->user->id);
            })->get();

            $data = [
                'outPatient' => $diagnosis->where('ward_status','outPatient'),
                'inwardPatient' => $diagnosis->where('ward_status','inPatient'),
                'nurse' => $getNurseDetails,
                'unitReport' => $unitReport,
                'patient' => $patients

            ];

            return response()->json(['message'=>'Nurse details fetched Successfully','data'=>$data],200);



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

            $patientDiagnosis = User::with(['patient.diagnosis' => function($query) {
                $query->with(['labTest.rates', 'consultation.doctor.user','diagnosisReport','sales.drugStock','doctor.user'])
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


    public function addDiagnosisReport (Request $request){

            $req = $request->all();

            try {

                if ( !Hash::check($req['password'],$request->user()->password)){
                    return response()->json(['message'=>'invalid Authentication to create Diagnosis'],401);
                }

                return Db::transaction(function ()use ($req,$request) {

                    $diagnosisPayload = [
                        'diagnosis_id' => $req['diagnosis_id'],
                        'user_id' => $request->user()->id,
                        'diagnosis_report' => $req['diagnosis_report'],
                    ];

                    $diagnosisReport = DiagnosisReport::create($diagnosisPayload);

                    $diagnosisOverview = Diagnosis::with(['patient','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('id',$diagnosisReport->diagnosis_id)->first();




                    return response()->json(['message'=>'Nurse Report has been added','data'=> ['diagnosisReport' => $diagnosisReport,"diagnosisOverview"=>$diagnosisOverview]],203);


                });



            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

    public function updateNurseDiagnosisReport (Request $request){

                $req = $request->all();

                try {

                    $diagnosis = DiagnosisReport::with(['user','diagnosis.doctor.user','diagnosis.sales','diagnosis.labTest'])->where('id',$req['diagnosisReport_id'])->first();


                    /*ensure the same doctor that created the diagnosis can edit it*/
                    if ( $request->user()->id !== $diagnosis->user_id){
                        return response()->json(['message'=>"This diagnosis Report was created by Dr. {$diagnosis['doctor']['user']['name']}, hence you cannot edit"],201);
                    }

                    $diagnosis->update($req['editPayload']);
                    $diagnosis->refresh();

                    $diagnosisOverview = Diagnosis::with(['patient','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->where('id',$diagnosis->diagnosis_id)->first();


                    return response()->json(['message'=>'diagnosis report updated successfully','data'=> ['diagnosisReport' => $diagnosis,"diagnosisOverview"=>$diagnosisOverview]],203);

                }catch (Exception $exception){
                    return response()->json(['message' => $exception->getMessage()]);
                }
            }
}
