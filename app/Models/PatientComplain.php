<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientComplain extends Model
{
    protected $fillable = ['patient_id','diagnosis_id','complaint'];

    public function patient(){
        return $this->belongsTo(Patient::class);
    }

    public function diagnosis(){
        return $this->belongsTo(Diagnosis::class);
    }
}
