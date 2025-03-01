<?php

namespace App\Modules\Layout\Http\Controllers;

use App\Http\Controllers\Controller;


class LayoutController extends Controller
{

    public function testHome()
    {
        return view('layout::test-home');
    }

    public function testFrontend()
    {
        return view('layout::test-frontend');
    }

    public function testAdmin()
    {
        return view('layout::test-admin');
    }
}
