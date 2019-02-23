<?php

namespace App\Http\Controllers;

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
        if ($sort = $request->input('sort')) {
            $model = $model->orderby($sort);
        }
        
        if ($fields = $request->input('fields')) {
            $model = $model->select(explode('|', $fields));
        }
        $data = $model->get();
        if ($total = $model->count()) {
            $data->transform(function ($d) {
                return new TemplateCollection($d);
            });
            
            $count = count($data);
            
            $links = [];
            
            $result = array(
                'meta'  => array(
                    'total' => $total,
                    'count' => $count,
                ),
                'links' => $links,
                'data'  => $data,
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
}
