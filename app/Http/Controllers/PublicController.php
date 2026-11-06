<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        $dentists = Dentist::where('status', 'active')->with('availabilities')->get();
        return view('public.home', compact('dentists'));
    }

    public function about()
    {
        return view('public.about');
    }

    public function features()
    {
        return view('public.features');
    }

    public function contact()
    {
        return view('public.contact');
    }
}
