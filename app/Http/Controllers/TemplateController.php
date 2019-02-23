<?php

namespace App\Http\Controllers;

use App\Checklist;
use App\ChecklistItem;
use App\Http\Resources\TemplateCollection;
use App\Http\Services\Completion;
use App\Http\Services\Templating;
use App\Template;
use App\TemplateItem;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @param Request  $request
     * @param Template $model
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Template $model)
    {
        $page_limit  = $request->input('page_limit') ?: 10;
        $page_offset = $request->input('page_offset') ?: 0;
        $page        = ceil($page_offset / $page_limit);
        
        $request->merge(['page' => $page]);
        
        if ($sort = $request->input('sort')) {
            $model = $model->orderby($sort);
        }
        
        if ($fields = $request->input('fields')) {
            $model = $model->select(explode('|', $fields));
        }
        $data = $model->paginate($page_limit);
        if ($total = $model->count()) {
            $data->transform(function ($d) {
                return new TemplateCollection($d);
            });
            
            $data->appends($_GET)->links();
    
            $array_data = $data->toArray();
            $total      = $data->total();
            $count      = $data->count();
            $links      = [
                'first' => $array_data['first_page_url'],
                'last'  => $array_data['last_page_url'],
                'next'  => $array_data['next_page_url'],
                'prev'  => $array_data['prev_page_url'],
            ];
            
            $result = array(
                'meta' => array(
                    'total' => $total,
                    'count' => $count,
                ),
                'links' => $links,
                'data'  => $data->items(),
            );
            
            return response()->json($result);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * Display the specified resource.
     *
     * @param          $template_id
     * @param Template $model
     * @return \Illuminate\Http\Response
     */
    public function show($template_id, Template $model)
    {
        if ($result = $model->where('id', $template_id)->first()) {
            return new TemplateCollection($result);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Template                  $model
     * @param TemplateItem              $ModelItem
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Template $model, TemplateItem $ModelItem)
    {
        $this->validate($request, [
            'data'                       => 'required',
            'data.name'                  => 'required',
            'data.checklist'             => 'required',
            'data.checklist.description' => 'required',
            'data.items'                 => 'required',
            'data.items.*.description'   => 'required',
        ]);
        
        if ($c = Templating::CreateTemplate($request->input('data'))) {
            return new TemplateCollection($c);
        }
        
        return response()->json(['status' => 'fail'], 500);
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int     $template_id
     * @param Template $model
     * @return \Illuminate\Http\Response
     */
    public function delete($template_id, Template $model)
    {
        if ($d = $model->find($template_id)) {
            if ($model->destroy($template_id)) {
                return response()->json(['status' => '204']);
            }
            
            return response()->json(['status' => 500, 'error' => 'Server Error'], 500);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * @param  int     $template_id
     * @param Request  $request
     * @param Template $model
     * @return \Illuminate\Http\Response
     */
    public function update($template_id, Request $request, Template $model)
    {
        $this->validate($request, [
            'data'                       => 'required',
            'data.name'                  => 'required',
            'data.checklist'             => 'required',
            'data.checklist.description' => 'required',
            'data.items'                 => 'required',
            'data.items.*.description'   => 'required',
        ]);
        
        if ($c = $model->find($template_id)) {
            if ($c = Templating::CreateTemplate($request->input('data'), $template_id)) {
                return new TemplateCollection($c);
            }
            
            return response()->json(['status' => 500, 'error' => 'Server Error'], 500);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function complete(Request $request)
    {
        $this->validate($request, [
            'data' => 'required',
        ]);
        
        $result       = [];
        $Template_ids = [];
        
        foreach ($request->input('data') as $id) {
            if ($d = TemplateItem::find($id)) {
                $d = $d->first();
                
                $d->is_completed = true;
                $d->completed_at = Carbon::now();
                $d->save();
                
                $result[]       = $d;
                $Template_ids[] = $d->Template_id;
            }
        }
        
        foreach ($Template_ids as $Template_id) {
            //TODO : use event listener better
            Completion::TemplateCompletionChecker($Template_id);
        }
        
        return response()->json(['data' => $result], 200);
    }
    
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function incomplete(Request $request)
    {
        $this->validate($request, [
            'data' => 'required',
        ]);
        
        $result       = [];
        $Template_ids = [];
        
        foreach ($request->input('data') as $id) {
            if ($d = TemplateItem::find($id)) {
                $d = $d->first();
                
                $d->is_completed = false;
                $d->completed_at = null;
                $d->save();
                
                $result[]       = $d;
                $Template_ids[] = $d->Template_id;
            }
        }
        
        foreach ($Template_ids as $Template_id) {
            //TODO : use event listener better
            Completion::TemplateCompletionChecker($Template_id);
        }
        
        return response()->json(['data' => $result], 200);
    }
    
    /**
     * assign template to a checklist
     *
     * @param  int     $template_id
     * @param Request  $request
     * @param Template $model
     * @return \Illuminate\Http\Response
     */
    public function assign($template_id, Request $request, Template $model)
    {
        $this->validate($request, [
            'data'                            => 'required',
            'data.*.attributes'               => 'required',
            'data.*.attributes.object_id'     => 'required',
            'data.*.attributes.object_domain' => 'required',
        ]);
        
        if ($t = $model->find($template_id)) {
            
            foreach ($request->input('data') as $d) {
                $object_id     = $d['attributes']['object_id'];
                $object_domain = $d['attributes']['object_domain'];
                
                $c = Checklist::where('object_id', $object_id)
                    ->where('object_domain', $object_domain)
                    ->get();
                
                $c = $c->first();
                
                foreach ($t->items as $i) {
                    $new_checklist_item = array(
                        'checklist_id' => $c->id,
                        'description'  => $i->description,
                        'urgency'      => $i->urgency,
                        'created_by'   => current_user()->id,
                    );
                    
                    ChecklistItem::Create($new_checklist_item);
                }
            }
            
            return response()->json(['status' => 200], 200);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
}
