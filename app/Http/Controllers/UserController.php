<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\Reports;
use Illuminate\Http\Request;
use Mosquitto\Exception;

class UserController extends Controller
{
    public function addLeaveApplication(Request $request){
        $req = $request->all();



        try {

            $leaveApplied = LeaveApplication::create($req);

            return response()->json(['message' => 'Staff leave has been applied successfully','data'=>$leaveApplied]);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }

    }

    public function addUnitReport(Request $request){
        $req = $request->all();



        try {

            $leaveApplied = LeaveApplication::create($req);

            return response()->json(['message' => 'Staff leave has been applied successfully','data'=>$leaveApplied]);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }

    }

    public function updateLeaveReport(Request $request){
        $req = $request->all();



        try {

            $leaveApplied = LeaveApplication::where('id',$req['leaveApplication_id'])->first();

            if ($leaveApplied->status !== 'requested'){
                return response()->json(['message' => 'Your Leave Request has been responded to']);
            }

           $leaveApplied->update([
                'days_requested' => $req['days_requested'],
                'resumption_date' => $req['resumption_date'],
                'remark' => $req['remark'],

            ]);

            $leaveApplied->refresh();



            return response()->json(['message' => 'Staff leave has been updated successfully','data'=>$leaveApplied]);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }

    }



    public function updateUnitReport(Request $request){
        $req = $request->all();



        try {

            $unitReport = Reports::where('id',$req['report_id'])->first();



            $unitReport->update([
                'title' => $req['title'],
                'unit' => $req['unit'],
                'description' => $req['description'],

            ]);

            $unitReport->refresh();



            return response()->json(['message' => 'Staff Unit report has been updated successfully','data'=>$unitReport]);

        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }

    }



}
