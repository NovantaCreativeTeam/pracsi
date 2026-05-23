<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'interactional_segment_id',
        'type_id',
        'begin',
        'end',
    ];

    public function interactionalSegment()
    {
        return $this->belongsTo(InteractionalSegment::class);
    }

    public function type()
    {
        return $this->belongsTo(SequenceType::class, 'type_id');
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
