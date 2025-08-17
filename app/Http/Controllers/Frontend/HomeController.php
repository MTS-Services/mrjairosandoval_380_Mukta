<?php

namespace App\Http\Controllers\Frontend;


use App\Models\Home;
use App\Models\Articles;
use App\Models\Services;
use App\Models\memberShip;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use PhpOffice\PhpSpreadsheet\Calculation\Web\Service;

class HomeController extends Controller
{
    public function home()
    {
        $data['home'] = Banner::orderBy('sort_order', 'asc')->active()->latest()->get();
        return view('frontend.pages.home', $data);
    }

    public function login()
    {
        return view('frontend.pages.login');
    }

    public function about()
    {
        return view('frontend.pages.about.about');
    }

    public function service()
    {
        $data['services'] = Services::orderBy('sort_order', 'asc')->active()->latest()->get();

        return view('frontend.pages.service.service', $data);
    }

    public function memberShip()
    {
        $data['memberShips'] = MemberShip::orderBy('sort_order', 'asc')->active()->latest()->get();

        return view('frontend.pages.mamber.memberShip', $data);
    }

    

    public function insight()
    {
        $data['articles'] = Articles::orderBy('sort_order', 'asc')->with('articleCategory')->active()->latest()->get();
        return view('frontend.pages.insights.insights', $data);
    }

    public function privacyPolicy()
    {
        return view('frontend.pages.privacy.privacy');
    }
}
