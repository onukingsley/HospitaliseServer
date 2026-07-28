<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rates extends Model
{
    protected $fillable = ['title','amount','rate_type'];

    public function payment(){
        return $this->hasMany(Payment::class);
    }

    public function consultation(){
        return $this->hasMany(AwaitingConsultation::class);
    }

    public function labTests(){
        return $this->belongsToMany(
            LabTest::class,
            'patient_test',
            'rates_id',
            'lab_tests_id'
        )->withPivot('remark','amount','status')->withTimestamps();
    }
}
