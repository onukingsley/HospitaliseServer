<?php

namespace App\Http\Controllers;

use App\Models\accountant;
use App\Models\Clerk;
use App\Models\Doctor;
use App\Models\LabScientist;
use App\Models\Nurses;
use App\Models\Patient;
use App\Models\Pharmasist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request){
        $req = $request->all();

        $user = User::whereAny(['email','phone_no','regID'],$req['details'])->first();

        if ($user && Hash::check($req['password'],$user['password'])){

            if ($user['suspend'] == 1){
                return response()->json([
                   'message' => 'User Currently Deactivated'
                ],200);
            }

            //$user->load($user->user_role); //the user_role should be the names not numbers

            auth()->login($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Login Successful'
            ]);

        } else {
            return response()->json([
                'message' => 'Invalid User Credential',
            ],401);
        }
    }

    public function register (Request $request){
        $req = $request->all();

        $idPrefix = [
          'patient'  => 'PID',
          'doctor'  => 'DID',
          'pharmasist'  => 'PHID',
          'labScientist'  => 'LID',
          'clerk'  => 'CID',
          'Accountant'  => 'AID',
          'Nurse'  => 'NID',
        ];

        $payload = [
            'name' => $req['name'],
            'email' => $req['email'],
            'password' => Hash::make( $req['password']),
            'user_role' => $req['user_role'],
            'regID' => $idPrefix[$req['user_role']].Str::random(4),
            'phone_no'=> $req['phone_no']


        ];

        if ($request->hasFile('image')){
            $image = $request->file('image')->store('userImage','public');

            $payload['profile_image'] = $image;

        }
        try {
            $user = User::create($payload);

            $attach = null;

            if ($user){
                auth()->login($user);
                switch ($user['user_role']){
                    case 'doctor':
                        $doctorPayload = [
                            'user_id' => $user['id'],
                            'license_id' => $req['license_id'],
                            'level' => $req['level'],
                            'leave_days' => $req['leave_days'],
                            'specialization' => $req['specialization'],
                        ];
                        $attach = Doctor::create($doctorPayload);
                        break;

                    case 'patient':
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
                        break;

                        /*todo: continue to add other roles Nurses, pharmasist,labscientist,clerk,accountant*/
                    case 'nurse':
                        $nursePayload = [
                            'user_id' => $user['id'],
                            'license_id' => $req['license_id'],
                            'level' => $req['level'],
                            'leave_days' => $req['leave_days'],
                            'specialization' => $req['department'],
                        ];
                        $attach = Nurses::create($nursePayload);
                        break;

                    case 'pharmasist':
                        $pharmPayload = [
                            'user_id' => $user['id'],
                            'license_id' => $req['license_id'],
                            'level' => $req['level'],
                            'leave_days' => $req['leave_days'],
                            'specialization' => $req['specialization']
                        ];
                        $attach = Pharmasist::create($pharmPayload);
                        break;

                    case 'labScientist':
                        $labScientistPayload = [
                            'user_id' => $user['id'],
                            'license_id' => $req['license_id'],
                            'level' => $req['level'],
                            'leave_days' => $req['leave_days'],
                            'specialization' => $req['specialization']
                        ];
                        $attach = LabScientist::create($labScientistPayload);
                        break;

                    case 'clerk':
                        $clerkPayload = [
                            'user_id' => $user['id'],
                            'leave_days' => $req['leave_days'],

                        ];
                        $attach = Clerk::create($clerkPayload);
                        break;

                    case 'accountant':
                        $accountantPayload = [
                            'user_id' => $user['id'],
                            'level' => $req['level'],
                            'leave_days' => $req['leave_days'],

                        ];
                        $attach = accountant::create($accountantPayload);
                        break;

                    default:
                        return response()->json(['message'=>"Failed to create role: {$user['user_role']} "],501);

                }


                $token = $user->createToken($user['email'])->plainTextToken;

                return response()->json(['message' => 'Registration Successful ', 'user' => $user, 'user_role'=>$attach, 'token'=>$token],200);

            }
            else{
                return response()->json(['message'=>'Invalid Registration credential'],401);
            }
        }catch (\Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }

    }

    public function logout(Request $request){
        try {
            $user = $request->user();

            $user->currentAccessToken()->delete();



            return response()->json(['message' => 'Logged out Successfully']);


        }catch (Exception $exception){
            return response()->json(['message' => $exception->getMessage()]);
        }
    }



}
