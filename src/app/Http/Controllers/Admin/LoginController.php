<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class LoginController extends Controller
{
    /**
     * Display the admin login page.
     */
    public function __invoke(): View
    {
        return view('admin.auth.login');
    }
}
