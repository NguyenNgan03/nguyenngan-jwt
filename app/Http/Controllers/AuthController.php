<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\Factory;
use PhpParser\Node\Stmt\Return_;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Exception;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh']]);
    }
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $refreshToken = $this-> createRefreshToken();
    }
    private function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'refresh_token' = $refreshToken,
            'token_type' => 'bearer',
            // 'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
    private function createRefreshToken(){
        $data = [
            'sub' => auth()->user()->id,
             'random' => rand() . time(),
             'exp' => time() +  config('jwt.refresh_ttl')
         ];
         $refreshToken = JWTAuth::getJWTProvider()->encode($data);
         return  $refreshToken;
    }
    public function profile()
    {
        try {
            return response()->json(auth()->user());
        } 
        catch (JWTException $expection)
        {
            return response()->json(['error' => 'Unauthorized'],401);
        }

    }
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
    public function refresh()
    {
        $refreshToken = request()->refresh_token;
        try {
          $decoded = JWTAuth::getJWTProvider()->decode($refreshToken);
          $user = User::find($decoded['sub']);
          if(!$user){
            return response()->json(['error' =>'User no found'], 404);
          }
          $token = auth()->login($user);
          $refreshToken = $this->createRefreshToken();
           return $this->respondWithToken($token, $refreshToken);
        } catch (JWTException $expection) {
            return response()->json(['error' => 'Refresh Token Invalid'], 500);
        }
        // return $this->respondWithToken(auth()->refresh());
    }
}
