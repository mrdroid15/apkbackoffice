<?php

namespace App\Http\Controllers;

use Exception;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use App\Models\Apkads;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function index(Request $request)
    {
        $apk = Apkads::where('packagename',$request->package)->first();
        if($apk){
            $arr = array("name"=>$apk->name,"link"=>$apk->link,"image"=>$apk->image);
            echo json_encode($arr);
        }
    }

}