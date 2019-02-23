<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TemplateItem extends Model
{
    //
    protected $table    = 'template_item';
    protected $fillable = ['template_id', 'description', 'urgency', 'due_interval', 'due_unit', 'created_by', 'updated_by'];
}
