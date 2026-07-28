<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwaitingConsultation extends Model
{
    protected $fillable = ['patient_id','doctor_id','payment_status','diagnosis_id','attendance_status','rates_id','payment_id','amount'];

    public function patient(){
        return $this->belongsTo(Patient::class);
    }

    public function rates(){
        return $this->belongsTo(Rates::class);
    }

    public function payment(){
        return $this->belongsTo(Payment::class);
    }
    public function doctor(){
        return $this->belongsTo(Doctor::class);
    }

    public function diagnosis(){
        return $this->belongsTo(Diagnosis::class);
    }
}
