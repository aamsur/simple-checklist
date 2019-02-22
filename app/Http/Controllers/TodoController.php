<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodoCollection;
use App\Todo;
use Auth;
use Illuminate\Http\Request;

class TodoController extends Controller
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $todo = Auth::user()->todo();
        
        
        if ($sort = $request->input('sort')) {
            $todo = $todo->orderby($sort);
        }
        
        if ($fields = $request->input('fields')) {
            $todo = $todo->select(explode('|', $fields));
        }
        
        if ($data = $todo->get()) {
            $data->transform(function($d){
                return new TodoCollection($d);
            });

            $total = $todo->count();
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'todo'        => 'required',
            'description' => 'required',
            'category'    => 'required'
        ]);
        if (Auth::user()->todo()->Create($request->all())) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail']);
        }
        
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $todo = Todo::where('id', $id)->get();
        
        return response()->json($todo);
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $todo = Todo::where('id', $id)->get();
        
        return view('todo.edittodo', ['todos' => $todo]);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'todo'        => 'filled',
            'description' => 'filled',
            'category'    => 'filled'
        ]);
        $todo = Todo::find($id);
        if ($todo->fill($request->all())->save()) {
            return response()->json(['status' => 'success']);
        }
        
        return response()->json(['status' => 'failed']);
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Todo::destroy($id)) {
            return response()->json(['status' => 'success']);
        }
    }
}
