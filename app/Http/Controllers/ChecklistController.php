<?php

namespace App\Http\Controllers;

use App\Checklist;
use App\ChecklistItem;
use App\Http\Resources\ChecklistCollection;
use App\Http\Services\Completion;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChecklistController extends Controller
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
     * @param Request   $request
     * @param Checklist $model
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Checklist $model)
    {
        if ($sort = $request->input('sort')) {
            $model = $model->orderby($sort);
        }
        
        if ($fields = $request->input('fields')) {
            $model = $model->select(explode('|', $fields));
        }
        
        if ($data = $model->get()) {
            $data->transform(function ($d) {
                return new ChecklistCollection($d);
            });
            
            $total = $model->count();
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
     * @param  int      $id
     * @param Checklist $model
     * @return \Illuminate\Http\Response
     */
    public function show($id, Checklist $model)
    {
        if ($result = $model->where('id', $id)->first()) {
            return new ChecklistCollection($result);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Checklist                 $model
     * @param ChecklistItem             $ModelItem
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Checklist $model, ChecklistItem $ModelItem)
    {
        $this->validate($request, [
            'data'                          => 'required',
            'data.attributes'               => 'required',
            'data.attributes.object_domain' => 'required',
            'data.attributes.object_id'     => 'required',
            'data.attributes.description'   => 'required',
        ]);
        
        $data               = $request->input('data.attributes');
        $data['created_by'] = current_user()->id;
        
        if ($c = $model->Create($data)) {
            foreach ($request->input('data.attributes.items') as $i) {
                $data_item = array(
                    'checklist_id' => $c['id'],
                    'description'  => $i,
                    'created_by'   => current_user()->id,
                );
                $ModelItem->Create($data_item);
            }
            
            return new ChecklistCollection($c);
        }
        
        return response()->json(['status' => 'fail'], 500);
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int      $id
     * @param Checklist $model
     * @return \Illuminate\Http\Response
     */
    public function delete($id, Checklist $model)
    {
        if ($d = $model->find($id)) {
            if ($model->destroy($id)) {
                return response()->json(['status' => '204']);
            }
            
            return response()->json(['status' => 500, 'error' => 'Server Error'], 500);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * @param  int      $id
     * @param Request   $request
     * @param Checklist $model
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request, Checklist $model)
    {
        $this->validate($request, [
            'data'                          => 'required',
            'data.type'                     => 'required',
            'data.id'                       => 'required',
            'data.attributes'               => 'required',
            'data.attributes.object_domain' => 'required',
            'data.attributes.object_id'     => 'required',
            'data.attributes.description'   => 'required',
            'data.links'                    => 'required',
            'data.links.self'               => 'required',
        ]);
        
        if ($c = $model->find($id)) {
            $data               = $request->input('data.attributes');
            $data['updated_by'] = current_user()->id;
            if ($c->fill($data)->save()) {
                return new ChecklistCollection($c);
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
        
        $result        = [];
        $checklist_ids = [];
        
        foreach ($request->input('data') as $id) {
            if ($d = ChecklistItem::find($id)) {
                $d = $d->first();
                
                $d->is_completed = true;
                $d->completed_at = Carbon::now();
                $d->save();
                
                $result[]        = $d;
                $checklist_ids[] = $d->checklist_id;
            }
        }
        
        foreach ($checklist_ids as $checklist_id) {
            //TODO : use event listener better
            Completion::ChecklistCompletionChecker($checklist_id);
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
        
        $result        = [];
        $checklist_ids = [];
        
        foreach ($request->input('data') as $id) {
            if ($d = ChecklistItem::find($id)) {
                $d = $d->first();
                
                $d->is_completed = false;
                $d->completed_at = null;
                $d->save();
                
                $result[]        = $d;
                $checklist_ids[] = $d->checklist_id;
            }
        }
        
        foreach ($checklist_ids as $checklist_id) {
            //TODO : use event listener better
            Completion::ChecklistCompletionChecker($checklist_id);
        }
        
        return response()->json(['data' => $result], 200);
    }
}
