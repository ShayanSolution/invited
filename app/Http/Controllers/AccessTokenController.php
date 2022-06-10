<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\Auth;

class AccessTokenController extends Controller
{
    /**
     * Instance of UserRepository
     *
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;

        parent::__construct();
    }

    /**
     * Since, with Laravel|Lumen passport doesn't restrict
     * a client requesting any scope. we have to restrict it.
     * http://stackoverflow.com/questions/39436509/laravel-passport-scopes
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function createAccessToken(Request $request)
    {
        $phone = $request->input('username');
        $user = User::where('phone','=',$phone)->first();
        if ($user){
            $isActive = $user->is_active;
            if ($isActive == 1) {
                $inputs = $request->all();
                //Set default scope with full access
                if (!isset($inputs['scope']) || empty($inputs['scope'])) {
                    $inputs['scope'] = "*";
                }
                $tokenRequest = $request->create('/oauth/token', 'post', $inputs);
            } else {
                return response()->json(['error' => 'Unauthorized', 'message' => 'Account has been blocked. Please contact with your service provider'],403);
            }
            // forward the request to the oauth token request endpoint
            //@todo have to push into git[userID enroll]
            $response = app()->dispatch($tokenRequest);
            $json = (array) json_decode($response->getContent());
            $json['userId'] = (string) $user->id;
            $response->setContent(json_encode($json));
            return $response;
        } else {
            return response()->json(['error' => 'Unauthorized', 'message' => 'The user credentials were incorrect.'],401);
        }

    }
}
