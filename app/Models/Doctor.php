<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = ['user_id','license_id','level','leave_days','specialization'];


    public function user(){
        return $this->belongsTo(User::class);
    }

    public function diagnosis(){
        return $this->hasMany(Diagnosis::class);
    }

    public function consultation(){
        return $this->hasMany(AwaitingConsultation::class);
    }


}
