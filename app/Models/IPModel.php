<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IPModel extends Model
{
    protected $fillable = ['ip_address','label'];
}
