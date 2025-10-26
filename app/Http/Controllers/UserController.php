<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('assignedTasks')->get();
        return Response::json($users);
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return Response::json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ], 400);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role ?? 'user',
                'password' => Hash::make($request->password),
            ]);

            $token = JWTAuth::fromUser($user);

            return Response::json([
                'error' => false,
                'message' => 'Registration Successful',
                'accessToken' => $token,
            ], 200);
        } catch (\Exception $e) {
            return Response::json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return Response::json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ], 400);
            }

            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials, ['exp' => '36000m'])) {
                return Response::json([
                    'error' => true,
                    'message' => 'Invalid email or password',
                ], 401);
            }

            return Response::json([
                'message' => 'Login Successful',
                'accessToken' => $token,
            ], 200);
        } catch (JWTException $e) {
            return Response::json([
                'error' => true,
                'message' => 'Could not create token',
            ], 500);
        }
    }

    public function getUser()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found or token invalid'], 401);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return Response::json([
                'message' => 'Logout Successful',
            ], 200);
        } catch (JWTException $e) {
            return Response::json([
                'error' => true,
                'message' => 'Could not invalidate token',
            ], 500);
        }
    }
}
