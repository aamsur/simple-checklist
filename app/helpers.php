<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('generate_links')) {
    /**
     * @param $paginate
     * @return bool|null|string
     */
    function generate_links($paginate)
    {
        
        dd($paginate);
        
        return false;
    }
}

if (!function_exists('ddd')) {
    /**
     * @param $data
     * @return bool|null|string
     */
    function ddd($data)
    {
        print_r($data);
        die();
    }
}

/**
 * Get current use loggedin
 *
 * @return mixed
 */
function current_user()
{
    return Auth::user();
}