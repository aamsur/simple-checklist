<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistCollection extends JsonResource
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
            'type'       => 'checklists',
            'id'         => $this->id,
            'attributes' => [
                'object_domain' => $this->object_domain,
                'object_id'     => $this->object_id,
                'description'   => $this->description,
                'is_completed'  => (bool) $this->is_completed,
                'completed_at'  => $this->completed_at,
                'updated_by'    => $this->updated_by,
                'created_by'    => $this->created_by,
                'updated_at'    => $this->updated_at->format(\DateTime::RFC3339),
                'created_at'    => $this->created_at->format(\DateTime::RFC3339),
                'due'           => $this->due,
                'urgency'       => $this->urgency,
            ],
            'links'      => ['self' => route('checklists.detail', ['id' => $this->id])]
        ];
        
        if ($include = $request->input('include')) {
            if ($include == 'items') {
                $data['attributes']['items'] = $this->items;
            }
        }
        
        return $data;
    }
}