<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['user_id', 'title', 'content'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}