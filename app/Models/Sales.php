<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    protected $fillable = ['diagnosis_id','altered_by_pharmasist','diagnosis_report_id','pharmasist_id','doctor_id','payment_id','patient_id','total_amount','payment_status','delivery_status','delivery_date'];

    public function drugStock(){
        return $this->belongsToMany(DrugStock::class,'drug_sales','sales_id','drug_stock_id')
            ->withPivot('quantity','unit_price','dosage','status','duration','route','instruction')->withTimestamps();
    }

    public function diagnosis(){
        return $this->belongsTo(Diagnosis::class);
    }

    public function pharmasist(){
        return $this->belongsTo(Pharmasist::class);
    }

    public function patient(){
        return $this->belongsTo(Patient::class);
    }
    public function diagnosisReport(){
        return $this->belongsTo(DiagnosisReport::class);
    }

    public function doctor(){
        return $this->belongsTo(Doctor::class);
    }

    public function payment(){
        return $this->belongsTo(Payment::class);
    }

    public function getTotalAttribute()
    {
        return $this->drugStock->sum(function ($drug) {
            return $drug->pivot->amount;
        });
    }
}
