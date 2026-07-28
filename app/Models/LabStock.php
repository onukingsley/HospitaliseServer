<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabStock extends Model
{
    protected $fillable = ['category_id','name','amount','status','quantity','description'];

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function stockRequest(){
        return $this->hasMany(StockRequest::class);
    }
}
