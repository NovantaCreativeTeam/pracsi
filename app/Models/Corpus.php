<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Corpus extends Model
{
    protected $table = 'corpora';

    protected $fillable = [
        'project_reference',
        'subject_language',
        'working_language',
        'location',
        'region',
        'country',
        'continent',
        'title',
        'depositor',
        'contact',
        'description',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function dialogs()
    {
        return $this->hasMany(Dialog::class);
    }
}
