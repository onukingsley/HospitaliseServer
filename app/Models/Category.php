<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name','description','type'];

    public function labStock(){
        return $this->hasMany(LabStock::class);
    }

    public function drugStock(){
        return $this->hasMany(DrugStock::class);
    }
}
