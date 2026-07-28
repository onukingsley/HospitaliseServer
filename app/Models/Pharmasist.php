<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pharmasist extends Model
{
    protected $fillable = ['user_id', 'license_id', 'specialization','leave_days'];


    public function user(){
        return $this->belongsTo(User::class);
    }
    public function sales(){
        return $this->hasMany(Sales::class);
    }



}
