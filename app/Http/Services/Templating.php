<?php

namespace App\Http\Services;

use App\Template;
use App\TemplateItem;

class Templating
{
    public static function CreateTemplate($data, $template_id = null)
    {
        $input = $data['checklist'];
        $input['name']       = $data['name'];
        $input['created_by'] = current_user()->id;
        
        if ($template_id) {
            TemplateItem::where('template_id', $template_id)->delete();
            $t = Template::find($template_id);
            $t->Save($input);
        }else{
            $t = Template::Create($input);
        }
        
        foreach ($data['items'] as $item) {
            $item['template_id'] = $t->id;
            $item['created_by']  = current_user()->id;
            
            TemplateItem::Create($item);
        }
        
        return $t;
    }
}
