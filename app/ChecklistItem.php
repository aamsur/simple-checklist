<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    //
    protected $table    = 'checklist_item';
    protected $fillable = ['checklist_id', 'description', 'is_completed', 'completed_at', 'urgency', 'created_by', 'updated_by', 'due'];
    protected $casts    = [
        'created_at'   => 'date:Y-m-d\TH:i:sP',
        'updated_at'   => 'datetime:Y-m-d\TH:i:sP',
        'completed_at' => 'datetime:Y-m-d\TH:i:sP',
    ];
}
