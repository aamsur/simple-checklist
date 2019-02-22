<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    /**
     * @param  string $value
     * @return void
     */
    public function setDueAttribute($value)
    {
        $this->attributes['due'] = Carbon::parse($value);
    }
    
    //
    protected $table    = 'checklist';
    protected $fillable = ['object_domain', 'object_id', 'description', 'completed_at', 'urgency', 'due', 'created_by', 'updated_by'];
}
