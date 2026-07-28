<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisReport extends Model
{
    protected $fillable = ['diagnosis_id','user_id','diagnosis_report'];

    public function diagnosis(){
        return $this->belongsTo(Diagnosis::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function labTest(){
        return $this->hasMany(LabTest::class);
    }

    public function sales(){
        return $this->hasMany(Sales::class);
    }
}
