<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateItemCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'description'  => $this->description,
            'urgency'      => $this->urgency,
            'due_interval' => $this->due_interval,
            'due_unit'     => $this->due_unit,
        ];
        
        return $data;
    }
}