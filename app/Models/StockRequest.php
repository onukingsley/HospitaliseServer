<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    protected $fillable = ['drug_stock_id','payment_id','user_id','lab_stock_id','quantity','unit_price','title','status','notes'];

    public function drugStock(){
        return $this->belongsTo(DrugStock::class);
    }

    public function labStock(){
        return $this->belongsTo(LabStock::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function payment(){
        return $this->belongsTo(Payment::class);
    }
}
