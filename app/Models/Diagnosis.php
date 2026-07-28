<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    protected $fillable = ['patient_id','doctor_id','body_vitals','description','patients_complain','initial_diagnosis','final_diagnosis','ward_status'];


    public function patient(){
        return $this->belongsTo(Patient::class);
    }


    public function doctor(){
        return $this->belongsTo(Doctor::class);
    }

    public function sales(){
        return $this->hasMany(Sales::class);
    }

    public function labTest(){
        return $this->hasMany(labTest::class);
    }

    public function consultation(){
        return $this->hasMany(AwaitingConsultation::class);
    }

    public function diagnosisReport(){
        return $this->hasMany(DiagnosisReport::class);
    }



}
