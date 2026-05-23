<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'dialog_id',
        'begin',
        'end',
        'content',
    ];

    public function dialog()
    {
        return $this->belongsTo(Dialog::class);
    }
}
