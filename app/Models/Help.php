<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Help extends Model
{
  

    protected $fillable = [
        'last_name',
        'email',
        'subject',
        'message',
    ];
}
