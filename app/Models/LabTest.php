<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Comment\Doc;

class LabTest extends Model
{
     protected $fillable = ['diagnosis_id','diagnosis_report_id','doctor_id','payment_id','lab_test_name','lab_test_description','patient_id','lab_test_amount','lab_test_result','lab_test_payment_status','lab_test_progress_status','lab_scientist_id'];

    public function patient(){
        return $this->belongsTo(Patient::class);
    }

    public function doctor(){
        return $this->belongsTo(Doctor::class);
    }

    public function diagnosis(){
        return $this->belongsTo(Diagnosis::class);
    }

    public function diagnosisReport(){
        return $this->belongsTo(DiagnosisReport::class);
    }

    public function payment(){
        return $this->belongsTo(Payment::class);
    }


    public function labScientist(){
        return $this->belongsTo(LabScientist::class);
    }

    //rates is basically the pricing tag of so many things in the hospital including test, consultation,surgeries...etc
    public function rates(){
        return $this->belongsToMany(
            Rates::class,
            'patient_test',
            'lab_tests_id',
            'rates_id'
        )->withPivot('remark','amount','status')->withTimestamps();
    }
}
