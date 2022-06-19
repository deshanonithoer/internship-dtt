<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;

class IndexController extends BaseController
{
    public function index()
    {
        return (new Status\Ok(['message' => "Welcome to my Rest API. Start by using: /facility"]))->send();
    }
}