<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User Model",
 *     properties={
 *         @OA\Property(property="id", type="integer", format="int64", example=1),
 *         @OA\Property(property="name", type="string", example="Hazem"),
 *         @OA\Property(property="email", type="string", format="email", example="hazem@example.com"),
 *         @OA\Property(property="google_id", type="string", nullable=true, example="123456789012345678901"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time"),
 *     }
 * )
 */
class GoogleAuthController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/auth/google/redirect",
     *      operationId="redirectToGoogle",
     *      tags={"Authentication"},
     *      summary="Redirect to Google for authentication",
     *      description="Generates a redirect URL to Google's OAuth consent screen.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="redirect_url", type="string", example="https://accounts.google.com/o/oauth2/v2/auth?...")
     *          )
     *      )
     * )
     *
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirect(): JsonResponse
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/auth/google/callback",
     *      operationId="handleGoogleCallback",
     *      tags={"Authentication"},
     *      summary="Handle Google OAuth callback",
     *      description="Handles the callback from Google, creates/retrieves a user, and returns a Sanctum token.",
     *      @OA\Parameter(
     *          name="code",
     *          in="query",
     *          description="The authorization code from Google",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="state",
     *          in="query",
     *          description="The state parameter from Google",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful authentication",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="user", ref="#/components/schemas/User"),
     *              @OA\Property(property="access_token", type="string", example="1|aBcDeFgHiJkLmNoPqRsTuVwXyZ..."),
     *              @OA\Property(property="token_type", type="string", example="Bearer")
     *          )
     *      )
     * )
     *
     * Obtain the user information from Google and issue a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function callback(): JsonResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::updateOrCreate([
            'email' => $googleUser->getEmail(),
        ], [
            'name' => $googleUser->getName(),
            'google_id' => $googleUser->getId(),
            'password' => Hash::make(str()->random(24)), // Create a random password for socialite users
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}