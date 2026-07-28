<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['user_id','blood_group','insurance_provider','allergies','genotype','nos_name','nos_address','nos_phone_no','insurance_id'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function labtest(){
        return $this->hasMany(LabTest::class);
    }

    public function diagnosis(){
        return $this->hasMany(Diagnosis::class);
    }

    public function sales(){
        return $this->hasMany(Sales::class);
    }
    public function consultation(){
        return $this->hasMany(AwaitingConsultation::class);
    }

    public function patientComplain(){
        return $this->hasMany(PatientComplain::class);
    }
}
