<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InteractionalSegment extends Model
{
    protected $fillable = [
        'dialog_id',
        'begin',
        'end',
    ];

    public function dialog()
    {
        return $this->belongsTo(Dialog::class);
    }

    public function sequences()
    {
        return $this->hasMany(Sequence::class);
    }
}
