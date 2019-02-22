<?php

namespace App\Http\Services;

use App\Checklist;
use App\ChecklistItem;
use Carbon\Carbon;

class Completion
{
    public static function ChecklistCompletionChecker($checklist_id)
    {
        $items = ChecklistItem::where('checklist_id', $checklist_id)
            ->where('is_completed', false)
            ->get();
    
        $c = Checklist::find($checklist_id)->first();
    
        if ($items->count() == 0) {
            $c->is_completed = true;
            $c->completed_at = Carbon::now();
        }else{
            $c->is_completed = false;
            $c->completed_at = null;
        }
    
        $c->save();
    }
}
