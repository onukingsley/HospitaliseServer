<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['rates_id','signed_accountant_id','patient_user_id','payment_type','title','description','invoice_id','amount','status','completion_status','outStanding_balance'];

    public function salaryAllowance(){
        return $this->hasMany(SalaryAllowances::class);
    }
    public function rates(){
        return $this->belongsTo(Rates::class);
    }




    public function labTest(){
        return $this->hasMany(LabTest::class);
    }

    public function sales(){
        return $this->hasMany(Sales::class);
    }

    public function consultation(){
        return $this->hasMany(AwaitingConsultation::class);
    }

    public function requestStock(){
        return $this->hasMany(StockRequest::class);
    }
    public function signedAccountant(){
        return $this->belongsTo(User::class, 'signed_accountant_id');
    }
    public function patientUser(){
        return $this->belongsTo(User::class, 'patient_user_id');
    }




}
