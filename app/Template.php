<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    //
    protected $table    = 'template';
    protected $fillable = ['name', 'description', 'due_interval', 'due_unit', 'created_by', 'updated_by'];
    protected $casts    = [
        'created_at'   => 'date:Y-m-d\TH:i:sP',
        'updated_at'   => 'datetime:Y-m-d\TH:i:sP',
        'completed_at' => 'datetime:Y-m-d\TH:i:sP',
    ];
    
    public function items()
    {
        return $this->hasMany('App\TemplateItem', 'template_id');
    }
}
