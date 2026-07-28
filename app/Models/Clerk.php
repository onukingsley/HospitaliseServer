<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clerk extends Model
{
    protected $fillable = ['user_id','leave_days'];


    public function user(){
        return $this->belongsTo(User::class);
    }

}
