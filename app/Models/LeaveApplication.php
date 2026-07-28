<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    protected $fillable = ['user_id','days_requested','resumption_data','remark','status'];

    public function user(){
        return $this->hasMany(User::class);
    }
}
