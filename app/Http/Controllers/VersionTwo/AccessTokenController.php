<?php

namespace App\Http\Controllers\VersionTwo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AccessTokenController extends Controller
{
    public function createAccessToken(Request $request)
    {
        $inputs = $request->all();

        //Set default scope with full access
        if (!isset($inputs['scope']) || empty($inputs['scope'])) {
            $inputs['scope'] = "*";
        }

        $tokenRequest = $request->create('/oauth/token', 'post', $inputs);
        // forward the request to the oauth token request endpoint
        return app()->dispatch($tokenRequest);
    }
}
