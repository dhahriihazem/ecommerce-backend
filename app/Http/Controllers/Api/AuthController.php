<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
/**
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     title="Register Request",
 *     required={"name", "email", "password", "password_confirmation"},
 *     properties={
 *         @OA\Property(property="name", type="string", example="Hazem"),
 *         @OA\Property(property="email", type="string", format="email", example="hazem@example.com"),
 *         @OA\Property(property="password", type="string", format="password", example="password"),
 *         @OA\Property(property="password_confirmation", type="string", format="password", example="password")
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     title="Login Request",
 *     required={"email", "password"},
 *     properties={
 *         @OA\Property(property="email", type="string", format="email", example="hazem@example.com"),
 *         @OA\Property(property="password", type="string", format="password", example="password")
 *     }
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/register",
      *      operationId="registerUser",
     *      tags={"Authentication"},
     *      summary="Register a new user",
     *      description="Creates a new user account.",
     *      @OA\RequestBody(
     *          required=true,
     *          description="User registration data",
     *          @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="User registered successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="User registered successfully"),
     *              @OA\Property(property="user", ref="#/components/schemas/User")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      )
     * )
     *
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }

    /**
     * @OA\Post(
     *      path="/api/login",
     *      operationId="loginUser",
     *      tags={"Authentication"},
     *      summary="Authenticate user and get token",
     *      description="Logs in a user and returns an access token.",
     *      @OA\RequestBody(
     *          required=true,
     *          description="User login data",
     *          @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Login successful",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Login successful"),
     *              @OA\Property(property="access_token", type="string", example="1|aBcDeFgHiJkLmNoPqRsTuVwXyZ..."),
     *              @OA\Property(property="token_type", type="string", example="Bearer"),
     *              @OA\Property(property="user", ref="#/components/schemas/User")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Invalid login details"
     *      )
     * )
     *
     * Authenticate the user and return a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/logout",
     *      operationId="logoutUser",
     *      tags={"Authentication"},
     *      summary="Log out the current user",
     *      description="Invalidates the current user's access token.",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Logged out successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Logged out successfully")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     *
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke all tokens for the user
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}