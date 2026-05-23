<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'dialog_id',
        'full_name',
        'nickname',
        'code',
        'birth_year',
        'gender',
        'languages',
        'description',
        'contact',
        'education',
        'occupation',
        'age_range',
        'speaker_language',
        'role',
    ];

    public function dialog()
    {
        return $this->belongsTo(Dialog::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
