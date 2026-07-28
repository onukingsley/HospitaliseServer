<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image',
        'suspend',
        'user_role',
        'phone_no',
        'gender',
        'date_of_birth',
        'address',
        'regID'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function patient(){
        return $this->hasOne(Patient::class);
    }

    public function patientUserpayment(){
        return $this->hasMany(Payment::class, 'patient_user_id');
    }
    public function signedAccountantpayment(){
        return $this->hasMany(Payment::class, 'signed_accountant_id');
    }




    public function pharmasist(){
        return $this->hasMany(Pharmasist::class);
    }

    public function labScientist(){
        return $this->hasMany(LabScientist::class);
    }

    public function clerk(){
        return $this->hasMany(Clerk::class);
    }


    public function doctor(){
        return $this->hasMany(Doctor::class);
    }

    public function accountant(){
        return $this->hasMany(accountant::class);
    }

    public function leaveApplication(){
        return $this->hasMany(LeaveApplication::class);
    }

    public function nurse(){
        return $this->hasMany(Nurses::class);
    }

    public function salaryAllowances(){
        return $this->hasMany(SalaryAllowances::class);
    }
    public function stockRequest(){
        return $this->hasMany(StockRequest::class);
    }

    public function diagnosisReport(){
        return $this->hasMany(DiagnosisReport::class);
    }


}
