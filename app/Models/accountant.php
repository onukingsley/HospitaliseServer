<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class accountant extends Model
{
    protected $fillable = ['user_id','leave_days','level'];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
