<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    protected $table    = 'checklist';
    protected $fillable = ['object_domain', 'object_id', 'description', 'completed_at', 'urgency', 'due', 'created_by', 'updated_by'];
    protected $casts    = [
        'created_at'   => 'date:Y-m-d\TH:i:sP',
        'updated_at'   => 'datetime:Y-m-d\TH:i:sP',
        'completed_at' => 'datetime:Y-m-d\TH:i:sP',
    ];
    
    /**
     * @param  string $value
     * @return void
     */
    public function setDueAttribute($value)
    {
        $this->attributes['due'] = Carbon::parse($value);
    }
    
    public function items()
    {
        return $this->hasMany('App\ChecklistItem', 'checklist_id');
    }
}
