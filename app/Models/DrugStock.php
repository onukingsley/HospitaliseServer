<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrugStock extends Model
{
    protected $fillable = ['category_id','name','generic','amount','status','quantity','description','expiry_data_range'];

    public function sales(){
        return $this->belongsToMany(Sales::class,'drug_sales','drug_stock_id','sales_id')
            ->withPivot('quantity','unit_Price','dosage','status','duration','route','instruction');
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }



    public function stockRequest(){
        return $this->hasMany(StockRequest::class);
    }


}
