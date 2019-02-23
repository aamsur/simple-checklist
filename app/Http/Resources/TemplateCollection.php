<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $this->items->transform(function ($i) {
            return new TemplateItemCollection($i);
        });
        
        $data = [
            'type'       => 'checklists',
            'id'         => $this->id,
            'attributes' => [
                'name'       => $this->name,
                'checklist'  => [
                    'description'  => $this->description,
                    'due_interval' => $this->due_interval,
                    'due_unit'     => $this->due_unit,
                ],
                'updated_by' => $this->updated_by,
                'created_by' => $this->created_by,
                'updated_at' => $this->updated_at ? $this->updated_at->format(\DateTime::RFC3339) : null,
                'created_at' => $this->created_at->format(\DateTime::RFC3339),
                'items'      => $this->items,
            ],
            'links'      => ['self' => route('checklists.template.detail', ['template_id' => $this->id])]
        ];
        
        return $data;
    }
}