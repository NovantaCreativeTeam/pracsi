<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'dialog_id',
        'participant_id',
        'begin',
        'end',
        'content',
    ];

    public function dialog()
    {
        return $this->belongsTo(Dialog::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
