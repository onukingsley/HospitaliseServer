<?php

use App\Http\Controllers\AccountantController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClerkController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\PharmasistController;
use App\Http\Controllers\UserController;
use App\Models\Pharmasist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*todo all the route should enter here except the admin route*/
Route::middleware(['ip.whitelist'])->group(function (){

});

/*leave and Salary Endpoint*/
Route::post('/addLeaveApplication', [UserController::class, 'addLeaveApplication'])->middleware('auth:sanctum');
Route::post('/addUnitReport', [UserController::class, 'addUnitReport'])->middleware('auth:sanctum');
Route::post('/updateLeaveReport', [UserController::class, 'updateLeaveReport'])->middleware('auth:sanctum');
Route::post('/updateUnitReport', [UserController::class, 'updateUnitReport'])->middleware('auth:sanctum');



/*Authentication endPoints*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/adminRegister', [AuthController::class, 'register']);
Route::post('/registerPatient', [ClerkController::class, 'createPatient'])->middleware('auth:sanctum');
Route::post('/generateQrToken', [ClerkController::class, 'generateTokenUrl'])->middleware('auth:sanctum');
Route::post('/QrPatientEnroll', [ClerkController::class, 'patientQrRegistration']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


// Doctor endPoints

Route::get('/getDoctorOverview', [DoctorController::class, 'getDoctorOverview'])->middleware('auth:sanctum');
Route::get('/getDoctorPatientByRegNo', [DoctorController::class, 'getPatientByRegNo'])->middleware('auth:sanctum');
Route::get('/getDoctorsPatientDiagnosis', [DoctorController::class, 'getDiagnosis'])->middleware('auth:sanctum');
Route::get('/getDoctorsPatientDiagnosisReport', [DoctorController::class, 'getDiagnosisReport'])->middleware('auth:sanctum');
Route::post('/addDiagnosis', [DoctorController::class, 'addDiagnosis'])->middleware('auth:sanctum');
Route::post('/addPrescription', [DoctorController::class, 'addPrescription'])->middleware('auth:sanctum');
Route::post('/addLabtest', [DoctorController::class, 'addLabtest'])->middleware('auth:sanctum');
Route::post('/removeLabtest', [DoctorController::class, 'removeLabtest'])->middleware('auth:sanctum');
Route::post('/removePrescription', [DoctorController::class, 'removePrescription'])->middleware('auth:sanctum');
Route::post('/updatePrescribedStatus', [DoctorController::class, 'updatePrescribedStatus'])->middleware('auth:sanctum');
Route::post('/updateSinglePrescribedStatus', [DoctorController::class, 'updateSinglePrescribedStatus'])->middleware('auth:sanctum');
Route::post('/updateDiagnosis', [DoctorController::class, 'updateDiagnosis'])->middleware('auth:sanctum');
Route::post('/addDiagnosisReport', [DoctorController::class, 'addDiagnosisReport'])->middleware('auth:sanctum');
Route::post('/updateDiagnosisReport', [DoctorController::class, 'updateDiagnosisReport'])->middleware('auth:sanctum');
Route::post('/updateDoctorConsultation', [DoctorController::class, 'updateConsultation'])->middleware('auth:sanctum');




/*pharmasist endpoints*/
Route::get('/getPharmasistOverview', [PharmasistController::class, 'getPharmasistOverview'])->middleware('auth:sanctum');
Route::get('/getPatientPrescriptionByRegID', [PharmasistController::class, 'getPatientPrescriptionByRegID'])->middleware('auth:sanctum');
Route::get('/getSalesDetails', [PharmasistController::class, 'getSalesDetails'])->middleware('auth:sanctum');
Route::post('/updateDelieveryStatus', [PharmasistController::class, 'updateDelieveryStatus'])->middleware('auth:sanctum');
Route::post('/addDrugRequest', [PharmasistController::class, 'addDrugRequest'])->middleware('auth:sanctum');
Route::post('/drugRestockRequest', [PharmasistController::class, 'drugRestockRequest'])->middleware('auth:sanctum');


/*Nurse endpoints*/
Route::get('/getNurseOverview', [NurseController::class, 'getNurseOverview'])->middleware('auth:sanctum');
Route::post('/addNurseDiagnosisReport', [NurseController::class, 'addDiagnosisReport'])->middleware('auth:sanctum');
Route::post('/updateNurseDiagnosisReport', [NurseController::class, 'updateNurseDiagnosisReport'])->middleware('auth:sanctum');
Route::get('/getNursePatientByRegNo', [NurseController::class, 'getPatientByRegNo'])->middleware('auth:sanctum');




/*lab endpoints*/
Route::get('/getLabOverview', [LabController::class, 'getLabOverview'])->middleware('auth:sanctum');
Route::get('/getPatientLabTest', [LabController::class, 'getPatientLabTest'])->middleware('auth:sanctum');
Route::post('/updateLabStatus', [LabController::class, 'updateLabStatus'])->middleware('auth:sanctum');
Route::post('/uploadLabResult', [LabController::class, 'uploadLabResult'])->middleware('auth:sanctum');
Route::post('/updatePivotStatus', [LabController::class, 'updatePivotStatus'])->middleware('auth:sanctum');
Route::post('/updateLabTest', [LabController::class, 'UpdateEvery'])->middleware('auth:sanctum');
Route::post('/addLabStockRequest', [LabController::class, 'addLabStockRequest'])->middleware('auth:sanctum');
Route::post('/editLabResult', [LabController::class, 'editLabResult'])->middleware('auth:sanctum');
Route::post('/restockLabRequest', [LabController::class, 'labrestockLabRequest'])->middleware('auth:sanctum');





/*accountant endpoints*/
Route::get('/getAccountantOverview', [AccountantController::class, 'getAccountantOverview'])->middleware('auth:sanctum');
Route::get('/getPatientPaymentByRegNo', [AccountantController::class, 'getPatientPaymentByRegNo'])->middleware('auth:sanctum');
Route::get('/generateInvoice', [AccountantController::class, 'generateInvoice'])->middleware('auth:sanctum');
Route::get('/getGeneratedBill', [AccountantController::class, 'getGeneratedBill'])->middleware('auth:sanctum');
Route::get('/paymentInvoice', [AccountantController::class, 'getPaymentInvoice'])->middleware('auth:sanctum');
Route::get('/getUnsettledPayment', [AccountantController::class, 'getUnSettledPayment'])->middleware('auth:sanctum');
Route::get('/getPatientEnrollment', [AccountantController::class, 'getPatientEnrollment'])->middleware('auth:sanctum');
Route::get('/getPeriodicalPayment', [AccountantController::class, 'getPeriodicalPayment'])->middleware('auth:sanctum');
Route::get('/getPeriodicalDrugSales', [AccountantController::class, 'getPeriodicalDrugSales'])->middleware('auth:sanctum');
Route::get('/getPeriodicalLabtest', [AccountantController::class, 'getPeriodicalLabtest'])->middleware('auth:sanctum');
Route::get('/getPeriodicalConsultation', [AccountantController::class, 'getPeriodicalConsultation'])->middleware('auth:sanctum');
Route::get('/getPeriodicalStockRequest', [AccountantController::class, 'getPeriodicalStockRequest'])->middleware('auth:sanctum');
Route::get('/getPeriodicalSalary', [AccountantController::class, 'getPeriodicalSalary'])->middleware('auth:sanctum');

Route::post('/updateDrugSales', [AccountantController::class, 'updateDrugSales'])->middleware('auth:sanctum');
Route::post('/settlePayment', [AccountantController::class, 'updatePayment'])->middleware('auth:sanctum');
Route::post('/enrollmentPayment', [AccountantController::class, 'updatePatientEnrollment'])->middleware('auth:sanctum');
Route::post('/updateLabPayment', [AccountantController::class, 'updateLabPayment'])->middleware('auth:sanctum');
Route::post('/updateConsultation', [AccountantController::class, 'updateConsultation'])->middleware('auth:sanctum');

/*for prepaid endpoint*/
/*Route::post('/patientWithdrawal', [AccountantController::class, 'patientWithdrawal'])->middleware('auth:sanctum');
Route::post('/patientDeposit', [AccountantController::class, 'patientDeposit'])->middleware('auth:sanctum');*/



/*clerk endpoint*/
Route::get('/getClerkOverview', [ClerkController::class, 'getClerkOverview'])->middleware('auth:sanctum');
Route::get('/getPatient', [ClerkController::class, 'getPatient'])->middleware('auth:sanctum');
Route::post('/setConsultation', [ClerkController::class, 'setConsultation'])->middleware('auth:sanctum');
Route::post('/cancelConsultation', [ClerkController::class, 'cancelConsultation'])->middleware('auth:sanctum');



/*admin endpoint*/
Route::get('/getAdminOverview', [AdminController::class, 'getAdminOverView'])->middleware('auth:sanctum');
Route::get('/getDrugSales', [AdminController::class, 'getDrugSales'])->middleware('auth:sanctum');
Route::get('/getLabTest', [AdminController::class, 'getLabTest'])->middleware('auth:sanctum');
Route::get('/getAdminPatientByRegNo', [AdminController::class, 'getAdminPatientByRegNo'])->middleware('auth:sanctum');
Route::get('/getAdminPeriodicalPayment', [AdminController::class, 'getPeriodicalPayment'])->middleware('auth:sanctum');
Route::get('/getAdminPeriodicalDrugSales', [AdminController::class, 'getPeriodicalDrugSales'])->middleware('auth:sanctum');
Route::get('/getAdminPayment', [AdminController::class, 'getPayment'])->middleware('auth:sanctum');
Route::get('/getAdminPeriodicalLabtest', [AdminController::class, 'getPeriodicalLabtest'])->middleware('auth:sanctum');
Route::get('/getAdminPeriodicalConsultation', [AdminController::class, 'getPeriodicalConsultation'])->middleware('auth:sanctum');
Route::get('/getAdminPeriodicalStockRequest', [AdminController::class, 'getPeriodicalStockRequest'])->middleware('auth:sanctum');
Route::get('/getAdminPeriodicalSalary', [AdminController::class, 'getPeriodicalSalary'])->middleware('auth:sanctum');

Route::post('/updateLeaveApplication', [AdminController::class, 'updateLeaveApplication'])->middleware('auth:sanctum');
Route::post('/updateDrugStock', [AdminController::class, 'updateDrugStock'])->middleware('auth:sanctum');
Route::post('/updateAdminPayment', [AdminController::class, 'updatePayment'])->middleware('auth:sanctum');
Route::post('/updateAdminRate', [AdminController::class, 'updateRates'])->middleware('auth:sanctum');
Route::post('/addAdminRate', [AdminController::class, 'addRate'])->middleware('auth:sanctum');
Route::post('/approveDrugStock', [AdminController::class, 'approveDrugStock'])->middleware('auth:sanctum');
Route::post('/approveLabStock', [AdminController::class, 'approveLabStock'])->middleware('auth:sanctum');
Route::post('/addAdminDrugStock', [AdminController::class, 'addAdminDrugStock'])->middleware('auth:sanctum');
Route::post('/updateLabStock', [AdminController::class, 'updateLabStock'])->middleware('auth:sanctum');
Route::post('/addAdminLabStock', [AdminController::class, 'addAdminLabStock'])->middleware('auth:sanctum');
Route::post('/updateRestockRequest', [AdminController::class, 'updateRestockRequest'])->middleware('auth:sanctum');
// Admin UserManagement endpoint
Route::post('/updateUser', [AdminController::class, 'updateUser'])->middleware('auth:sanctum');
Route::post('/updateSuspension', [AdminController::class, 'updateSuspension'])->middleware('auth:sanctum');
Route::post('/deleteUser', [AdminController::class, 'deleteUser'])->middleware('auth:sanctum');



