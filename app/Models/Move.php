<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    protected $fillable = [
        'dialog_id',
        'micro_task_id',
        'sequence_id',
        'transaction_id',
        'participant_id',
        'move_level_1_id',
        'move_level_2_id',
        'move_level_3_id',
        'non_verbal_action_id',
        'begin',
        'end',
        'annotation',
    ];

    public function microTask()
    {
        return $this->belongsTo(MicroTask::class);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function moveLevel1()
    {
        return $this->belongsTo(MoveLevel1::class, 'move_level_1_id');
    }

    public function moveLevel2()
    {
        return $this->belongsTo(MoveLevel2::class, 'move_level_2_id');
    }

    public function moveLevel3()
    {
        return $this->belongsTo(MoveLevel3::class, 'move_level_3_id');
    }

    public function nonVerbalAction()
    {
        return $this->belongsTo(NonVerbalAction::class, 'non_verbal_action_id');
    }

    public function dialog()
    {
        return $this->belongsTo(Dialog::class);
    }
}
