<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; 
// use Illuminate\Support\Facades\Auth; //for facades support auth
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Validator;
use DB;

use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;



class AuthController extends Controller
{
    //
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['login','register']]);
    // }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);
  
        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
  
        return $this->respondWithToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:4',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(),400);
        }

        try {
            //code...
            DB::beginTransaction();

            $user = User::create(array_merge(
                $validator->validate(),
                ['password' => bcrypt($request->password),]
            ));
    
            $role = Role::create([
                'privilege' => 'user',
                'ref_id' => 2001,
                'user_id' => $user->id,
            ]);

            DB::commit();
    
            return response()->json([
                'message' => '¡Successfully registered user!',
                'user' => $user,
                'privilege' => $role->privilege,
            ], 201);

        } catch (\Throwable $th) {
            
            DB::rollback();
            throw $th;
        }

       
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        // auth()->logout();
  
        // return response()->json(['message' => 'Successfully logged out']);

        try {
            // Invalidate the token so the user cannot use it anymore
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out'
            ], 200);
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token could not be invalidated'
            ], 500);
        }
    }

     /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }



}