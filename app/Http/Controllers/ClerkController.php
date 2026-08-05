<?php

namespace App\Http\Controllers;

use App\Models\AwaitingConsultation;
use App\Models\Clerk;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\QRToken;
use App\Models\Rates;
use App\Models\Reports;
use App\Models\User;
use Exception;
use Faker\Core\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClerkController extends Controller
{

    public function getClerkOverview (Request $request){

            $req = $request->all();

            try {

                $clerk = Clerk::with(['user.leaveApplication','user.salaryAllowances'])->where('user_id', $request->user()->id)->first();

                $unitReport = Reports::where('unit','clerk')->get();

                $rates = Rates::all();

                $pendingConsultation = AwaitingConsultation::with(['patient.user'])->where('attendance_status','unseen')->whereDate('created_at',today())->get();
                $allConsultation = AwaitingConsultation::with(['patient.user','diagnosis.diagnosisReport','diagnosis.sales.drugStock','diagnosis.labtest.rates'])->whereDate('created_at',today())->get();


                $diagnosis = Diagnosis::with(['patient.user','doctor.user','sales.drugStock','labTest.rates','consultation.doctor.user','diagnosisReport'])->get();

                $dailyPatient = Patient::with(['user'])->whereDate('created_at', today())->get();
                $allPatient = Patient::with(['user'])->get();



                return response()->json(['message'=>'','data'=> ['clerk'=> $clerk, 'unitReport'=> $unitReport,
                    'noOfPendingConsultation'=>$pendingConsultation->count(),
                    'pendingConsultation'=>$pendingConsultation,
                    'noOfDailyConsultation'=>$allConsultation->count(),
                    'totalPatient' => $allPatient->count(),
                    'allPatient' => $allPatient,
                    'outPatient' => $diagnosis->where('ward_status','outPatient'),
                    'inwardPatient' => $diagnosis->where('ward_status','inPatient'),
                    'dailyPatient' => $dailyPatient,
                    'dailyConsultation'=>$allConsultation , 'rates'=>$rates]],200);

            }catch (Exception $exception){
                return response()->json(['message' => $exception->getMessage()]);
            }
    }

   public function setConsultation (Request $request){

           $req = $request->all();

           try {
                $consultationPayload = [
                      'patient_id' => $req['patient_id'],
                    'rates_id' => $req['rates_id'],
                    'amount' => $req['amount']
                ];

                $consultationPayload = AwaitingConsultation::create($consultationPayload);



                /*todo set event when a new cosultation is set*/

               return response()->json(['message'=>'New Consultation has been set','data'=> $consultationPayload],200);

           }catch (Exception $exception){
               return response()->json(['message' => $exception->getMessage()]);
           }
   }
 public function cancelConsultation (Request $request){

           $req = $request->all();

           try {



                $consultation = AwaitingConsultation::with(['patient.user'])->where('id', $req['consultation_id'])->first();

                if (!$consultation){
                    return response()->json(['message'=>'Consultation successfully cancelled'],201);
                }

                $consultation->update([
                    'attendance_status' => 'cancelled'
                ]);



                /*todo set event when a new cosultation is set*/

               return response()->json(['message'=>'This consultation has been cancelled','data'=> $consultation],200);

           }catch (Exception $exception){
               return response()->json(['message' => $exception->getMessage()]);
           }
   }

   public function getPatient (Request $request){

           $req = $request->all();

           try {

               $user = User::with(['patient.user','patient.labtest','patient.diagnosis','patient.sales'])->whereAny([
                   'email',
                   'phone_no',
                   'regID'
               ], '=', $req['payload'])->first();


               return response()->json(['message'=>'Patient record fetched successfully','data'=> $user],200);

           }catch (Exception $exception){
               return response()->json(['message' => $exception->getMessage()]);
           }
   }

   public function createPatient (Request $request){

           $req = $request->all();



           try {

              return DB::transaction(function () use($req, $request){





                   $payload = [
                       'name' => $req['name'],
                       'email' => $req['email'],
                       'password' => Hash::make( isset($req['password']) ? $req['password'] : 'password'),
                       'user_role' => 'patient',
                       'regID' => 'PID'.Str::random(4),
                       'phone_no' => $req['phone_no'],
                       'address' => $req['address'],
                       'gender' => $req['gender'],
                       'date_of_birth' => $req['dateOfBirth']


                   ];

                   if ($request->hasFile('image')){
                       $image = $request->file('image')->store('userImage','public');

                       $payload['profile_image'] = $image;

                   }

                   $user = User::create($payload);

                   if (!$user) {
                       throw new \Exception('Failed to create user');
                   }

                   if ($user){
                       $patientPayload = [
                           'user_id' => $user->id,
                           'blood_group' => $req['blood_group'],
                           'genotype' => $req['genotype'],
                           'nos_name' => $req['nos_name'],
                           'nos_address' => $req['nos_address'],
                           'nos_phone_no' => $req['nos_phone_no'],
                           'insurance_id' => $req['insurance_id'],
                           'insurance_provider' => $req['insurance_provider'],
                           'allergies' => $req['allergies'],
                       ];
                       $patient = Patient::create($patientPayload);
                   }

                   $paymentPayload  = [
                       'patient_user_id' => $user->id,
                       'signed_accountant_id' => $request->user()->id,
                       'payment_type' => 'enrollment',
                       'title' => "patient Enrollment $user->name",
                       'invoice_id' => 'Reg'.Str::random(7),
                       'amount' => $req['amount'],
                       'rates_id' => $req['rates_id'],
                       'status' => 'credit'
                   ];


                   $payment = Payment::create($paymentPayload);


                   return response()->json(['message'=>'','data'=> ['user'=> $user,'patient'=> $patient, 'payment' => $payment]],200);

               });

           }catch (Exception $exception){
               return response()->json(['message' => $exception->getMessage()]);
           }
   }

   public function generateTokenUrl (Request $request){

           $req = $request->all();

           try {

               $payload = [
                 'token' => Str::random(4),
                 'expired_at' => now()->addMinutes(10)
               ];

               $token = QRToken::create($payload);

               $url = env('FRONT_END_URL')."/qrEnrollment/$token->token";
               //$url = "/qrEnrollment/$token->token";

               return response()->json(['message'=>'','data'=> ['url' => $url, 'token'=>$token]],200);

           }catch (Exception $exception){
               return response()->json(['message' => $exception->getMessage()]);
           }
   }

   public function patientQrRegistration (Request $request){

           $req = $request->all();

       try {

           return DB::transaction(function () use($req, $request){

               $isToken = QRToken::where('token', $req['token'])->first();

               if (!$isToken){
                   return response()->json(['message'=>'Invalid QrToken or URL.'],200);
               }
               if ($isToken->isExpired()){
                   $isToken->update([
                      'status' => 'expired'
                   ]);
                   return response()->json(['message'=>'Token has Expired Please meet the Clerk'],200);
               }




               $payload = [
                   'name' => $req['name'],
                   'email' => $req['email'],
                   'password' => Hash::make( isset($req['password']) ? $req['password'] : 'password'),
                   'user_role' => 'patient',
                   'regID' => 'PID'.$req['token'],
                   'phone_no' => $req['phone_no'],
                   'address' => $req['address'],
                   'gender' => $req['gender'],
                   'date_of_birth' => $req['dateOfBirth']


               ];

               if ($request->hasFile('image')){
                   $image = $request->file('image')->store('userImage','public');

                   $payload['profile_image'] = $image;

               }

               $user = User::create($payload);

               if (!$user) {
                   throw new \Exception('Failed to create user');
               }

               if ($user){
                   $patientPayload = [
                       'user_id' => $user->id,
                       'blood_group' => $req['blood_group'],
                       'genotype' => $req['genotype'],
                       'nos_name' => $req['nos_name'],
                       'nos_address' => $req['nos_address'],
                       'nos_phone_no' => $req['nos_phone_no'],
                       'insurance_id' => $req['insurance_id'],
                       'insurance_provider' => $req['insurance_provider'],

                   ];
                   if (isset($req['allergies'])){
                       $patientPayload['allergies'] = $req['allergies'];
                   }
                   $patient = Patient::create($patientPayload);
               }

               //todo move the payment creation to the account endpoint patientEnrollment

             /*  $paymentPayload  = [
                   'patient_user_id' => $user->id,
                   'signed_accountant_id' => $request->user()->id,
                   'payment_type' => 'enrollment',
                   'title' => "patient Enrollment $user->name",
                   'invoice_id' => 'Reg'.Str::random(7),
                   'amount' => $req['amount'],
                   'rates_id' => $req['rates_id'],
                   'status' => 'credit'
               ];


               $payment = Payment::create($paymentPayload);
*/

               return response()->json(['message'=>"Your Registration is Successful. RegID: $user->regID ",'data'=> ['user'=> $user,'patient'=> $patient]],200);

           });

       }catch (Exception $exception){
           return response()->json(['message' => $exception->getMessage()]);
       }
   }
}
