<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demo extends Model
{
    protected $table = 'demo';
    public $timestamps = false;
    protected $fillable = ['note'];
}