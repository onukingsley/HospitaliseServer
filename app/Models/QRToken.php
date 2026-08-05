<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QRToken extends Model
{
    protected $fillable = ['patient_id','status','expired_at','token'];



    public function patient (){
        return $this->belongsTo(Patient::class);
    }

    public function isExpired (){
        if (now()->greaterThan($this->expired_at)){
            return true;
        }else{
            return false;
        }
    }

}
