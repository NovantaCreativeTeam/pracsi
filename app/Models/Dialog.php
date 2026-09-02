<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dialog extends Model
{
    protected $fillable = [
        'corpus_id',
        'reference',
        'date',
        'title',
        'description',
        'genre',
        'subgenre',
        'topic',
        'subject_languages',
        'working_languages',
        'city',
        'region',
        'country',
        'continent',
        'researcher_involvement',
        'planning_type',
        'social_context',
        'customer_type',
        'customer_profile',
        'customer_n',
        'speaking_customer_n',
        'speakers_features',
        'restaurant_title',
        'restaurant_features',
        'menu_type',
        'meal',
        'audio_path',
        'eaf_path',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'date' => 'date',
    ];

    public function corpus()
    {
        return $this->belongsTo(Corpus::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function interactionalSegments()
    {
        return $this->hasMany(InteractionalSegment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
