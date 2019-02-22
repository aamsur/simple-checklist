<?php

namespace App\Http\Controllers;

use App\Checklist;
use App\ChecklistItem;
use App\Http\Resources\ChecklistCollection;
use App\Http\Resources\ChecklistItemCollection;
use Auth;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Display the specified resource.
     *
     * @param           $checklist_id
     * @param Checklist $model
     * @param Request   $request
     * @return \Illuminate\Http\Response
     */
    public function index($checklist_id, Checklist $model, Request $request)
    {
        if ($result = $model->where('id', $checklist_id)->first()) {
            $request->merge(['include' => 'items']);
            
            return new ChecklistCollection($result);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * Display the specified resource.
     *
     * @param               $checklist_id
     * @param               $item_id
     * @param ChecklistItem $model
     * @param Request       $request
     * @return \Illuminate\Http\Response
     */
    public function show($checklist_id, $item_id, ChecklistItem $model, Request $request)
    {
        if ($d = $model->where('id', '=', $item_id)->where('checklist_id', '=', $checklist_id)->first()) {
            return new ChecklistItemCollection($d);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param                           $checklist_id
     * @param  \Illuminate\Http\Request $request
     * @param ChecklistItem             $model
     * @return \Illuminate\Http\Response
     */
    public function create($checklist_id, Request $request, ChecklistItem $model)
    {
        $this->validate($request, [
            'data'                        => 'required',
            'data.attributes'             => 'required',
            'data.attributes.description' => 'required',
        ]);
        
        $data                 = $request->input('data.attributes');
        $data['created_by']   = current_user()->id;
        $data['checklist_id'] = $checklist_id;
        
        if ($c = $model->Create($data)) {
            return new ChecklistItemCollection($c);
        }
        
        return response()->json(['status' => 'fail'], 500);
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param               $checklist_id
     * @param               $item_id
     * @param ChecklistItem $model
     * @return \Illuminate\Http\Response
     */
    public function delete($checklist_id, $item_id, ChecklistItem $model)
    {
        if ($d = $model->where('id', '=', $item_id)->where('checklist_id', '=', $checklist_id)->first()) {
            if ($model->destroy($item_id)) {
                return new ChecklistItemCollection($d);
            }
            
            return response()->json(['status' => 500, 'error' => 'Server Error'], 500);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
    
    /**
     * @param               $checklist_id
     * @param               $item_id
     * @param Request       $request
     * @param ChecklistItem $model
     * @return \Illuminate\Http\Response
     */
    public function update($checklist_id, $item_id, Request $request, ChecklistItem $model)
    {
        $this->validate($request, [
            'data'                        => 'required',
            'data.attributes'             => 'required',
            'data.attributes.description' => 'required',
        ]);
        
        if ($d = $model->where('id', '=', $item_id)->where('checklist_id', '=', $checklist_id)->first()) {
            $data               = $request->input('data.attributes');
            $data['updated_by'] = current_user()->id;
            if ($d->fill($data)->save()) {
                return new ChecklistItemCollection($d);
            }
            
            return response()->json(['status' => 500, 'error' => 'Server Error'], 500);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
}
