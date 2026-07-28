<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabScientist extends Model
{
    protected $fillable = ['user_id','license_id','leave_days','specialization'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function labTest(){
    return $this->hasMany(LabTest::class);
}
}
