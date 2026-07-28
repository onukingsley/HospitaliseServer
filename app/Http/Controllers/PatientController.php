<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientComplain;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function getPatientOverview(Request $request){
        try {
            $patient = Patient::with(['diagnosis','labtest','patientComplain','sales'])->where('user_id',$request['user_id'])->first();

            return response()->json(['data'=> $patient],200);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }


    }

    public function addComplain(Request $request){


        try {
            $req = $request->all();

            $addedComplaint = PatientComplain::create($req);

            return response()->json(['data' => $addedComplaint,'message'=>'Complaint has been logged Successfully']);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function editComplain(Request $request){


        try {
            $req = $request->all();

            $editedComplain = PatientComplain::where('id',$req['complain_id'])->update([
                'complaint' => $req['complaint']
            ]);
            return response()->json(['data' => $editedComplain,'message'=>'Complaint has been Edited Successfully']);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }

    public function deleteComplain(Request $request){


        try {
            $req = $request->all();

            $editedComplain = PatientComplain::where('id',$req['complain_id'])->delete();
            return response()->json(['data' => $editedComplain,'message'=>'Complaint has been deleted Successfully']);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }


}
