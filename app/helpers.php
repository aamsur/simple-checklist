<?php

use Illuminate\Support\Facades\Auth;

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
 * Get current user logged in
 *
 * @return mixed
 */
function current_user()
{
    return Auth::user();
}