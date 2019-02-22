<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistItemCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type'       => 'items',
            'id'         => $this->id,
            'attributes' => [
                'description'  => $this->description,
                'is_completed' => (bool) $this->is_completed,
                'completed_at' => $this->completed_at,
                'due'          => $this->due,
                'urgency'      => $this->urgency,
                'updated_by'   => $this->updated_by,
                'created_by'   => $this->created_by,
                'updated_at'   => $this->updated_at->format(\DateTime::RFC3339),
                'created_at'   => $this->created_at->format(\DateTime::RFC3339),
            ],
            'links'      => ['self' => route('checklists.detail', ['id' => $this->checklist_id])]
        ];
    }
}