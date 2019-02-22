<?php

namespace App\Http\Controllers;

use App\Todo;
use Auth;
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page_limit  = ($request->input('page_limit')) ? $request->input('page_limit') : 10;
        $page_limit  = ($page_limit == 'infinity') ? 100000 : $page_limit;
        $offset = $request->input('offset');
        
        $todo = Todo::offset($offset * $page_limit)->take($page_limit)->get();
    
        return response()->json($todo);
    }
    
}
