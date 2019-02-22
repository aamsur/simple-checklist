<?php

namespace App\Http\Controllers;

use App\Checklist;
use App\ChecklistItem;
use App\Http\Resources\ChecklistCollection;
use Auth;
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
        }
        
        return response()->json($result);
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
        $result = $model->where('id', $id)->first();
        
        return new ChecklistCollection($result);
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
            
            return response()->json(['status' => 'success']);
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
        if ($model->destroy($id)) {
            return response()->json(['status' => 'success']);
        }
        
        return response()->json(['status' => 'fail'], 500);
    }
    
    /**
     * Remove the specified resource from storage.
     *
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
            'data.attributes.object_domain' => 'required',
            'data.attributes.object_id'     => 'required',
            'data.attributes.description'   => 'required',
            'data.links'                    => 'required',
            'data.links.self'               => 'required',
        ]);
        
        if ($d = $model->find($id)) {
            if ($d->fill($request->input('data.attributes'))->save()) {
                return response()->json(['status' => '200']);
            }
            
            return response()->json(['status' => 500, 'error' => 'Server Error'], 500);
        }
        
        return response()->json(['status' => 404, 'error' => 'Not Found'], 404);
    }
}
