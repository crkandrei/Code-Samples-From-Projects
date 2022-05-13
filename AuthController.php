<?php

namespace App\Http\Controllers\Api;


use Hash;
use App\User;
use App\SocialLogin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client as OClient;


class AuthController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Login Route.
     * Parameters: email,password
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        if (!request('email') || !request('password')) {
            return response()->json(['error' => 'Bad Request.'], 400);
        }

        if (!filter_var(request('email'), FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Adresa de email nu este valida.'], 400);
        }

        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $oClient = OClient::where('password_client', 1)->first();

            return $this->getTokenAndRefreshToken($oClient, request('email'), request('password'));
        } else {
            return response()->json(['error' => 'Wrong Credentials'], 400);
        }
    }

    /**
     * Change Password Route.
     *z
     * @return \Illuminate\Http\JsonResponse
     */
    public function change_password()
    {
        $user = Auth::user();

        if (!request('old_password')) {
            return response()->json(['error' => 'Parola veche incorecta'], 400);
        }

        if (!request('new_password')) {
            return response()->json(['error' => 'Parola trebuie sa contina minim 8 caractere'], 400);
        }

        if (strlen(request('new_password')) < 8) {
            return response()->json(['error' => 'Parola trebuie sa contina minim 8 caractere'], 400);
        }

        if (Hash::check(request('old_password'), $user->password)) {
            $user->password = bcrypt(request('new_password'));
            $user->save();

            return response()->json(['success' => 'Parola schimbata!'], 200);
        } else {
            return response()->json(['error' => 'Parola incorecta'], 400);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$request->device_os) {
                return response()->json(['error' => 'Parametri incorecti'], 400);
            }
            if ($request->device_os == 'android') {
                $user->device_token_android = null;
                $user->save();
            } else {
                // $user->device_token_ios = null;
                // $user->save();
            }

            // Revoke access token
            // => Set public.oauth_access_tokens.revoked to TRUE (t)
            $request->user()->token()->revoke();

            // Revoke all of the token's refresh tokens
            // => Set public.oauth_refresh_tokens.revoked to TRUE (t)
            $refreshTokenRepository = app('Laravel\Passport\RefreshTokenRepository');
            $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($request->user()->token()->id);

            return response()->json(['success' => 'logout_success'], 200);
        } else {
            return response()->json(['error' => 'api.something_went_wrong'], 500);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $client = new \GuzzleHttp\Client();

        $oClient = OClient::where('password_client', 1)->first();
        try {
            $response = $client->post(env('URL_SHORT') . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $request->refresh_token,
                    'client_id' => $oClient->id,
                    'client_secret' => $oClient->secret,
                ],
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            /**
             * Here we actually catch the instance of GuzzleHttp\Psr7\Response
             * (find it in ./vendor/guzzlehttp/psr7/src/Response.php) with all
             * its own and its 'Message' trait's methods. See more explanations below.
             *
             * So you can have: HTTP status code, message, headers and body.
             * Just check the exception object has the response before.
             */
            if ($e->hasResponse()) {
                $response = $e->getResponse();

                return response()->json(json_decode((string)$response->getBody(), true), $response->getStatusCode());
            }
        }

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Register route
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        if (!$request->name || !$request->email || !$request->password) {
            return response()->json(['error' => 'Bad Request.'], 400);
        }

        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Adresa de email nu este valida.'], 400);
        }

        if (strlen($request->password) < 8) {
            return response()->json(['error' => 'Parola trebuie sa aiba lungimea de minim 8 caractere.'], 400);
        }

        if (User::where('email', $request->email)->first()) {
            return response()->json(['error' => 'Emailul trebuie sa fie unic.'], 400);
        }

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->save();

        Auth::attempt(['email' => $request->email, 'password' => $request->password]);

        $oClient = OClient::where('password_client', 1)->first();

        return $this->getTokenAndRefreshToken($oClient, $request->email, $request->password);
    }

    public function facebookLogin(Request $request)
    {
        $account = SocialLogin::whereProvider('facebook')
            ->whereProviderUserId($request->facebook_id)
            ->first();

        if ($account) {
            $userFb = User::find($account->user_id);

            Auth::login($userFb);

            $oClient = OClient::where('password_client', 1)->first();

            return $this->getTokenAndRefreshTokenWithoutPassword($oClient, $request->facebook_id);
        } else {
            if ($request->email) {
                $email = $request->email;
            } else {
                $email = $request->facebook_id . '@facebook.com';
            }

            $user = User::whereEmail($email)->first();

            if (!$user) {
                $user = User::create([
                    'email' => $email,
                    'name' => $request->name,
                    'password' => Hash::make($request->facebook_id . '1308'),
                ]);
            } else {
                $accountSocialOld = SocialLogin::whereProvider('facebook')
                    ->where('user_id', $user->id)
                    ->first();

                if ($accountSocialOld) {
                    return response()->json(['error' => 'Nu s-a putut realiza logarea !'], 400);
                }
            }

            $account = new SocialLogin([
                'provider_user_id' => $request->facebook_id,
                'provider' => 'facebook',
            ]);

            $account->user()->associate($user);
            $account->save();

            Auth::attempt(['email' => $user->email, 'password' => $request->facebook_id . '1308']);

            $oClient = OClient::where('password_client', 1)->first();

            return $this->getTokenAndRefreshTokenWithoutPassword($oClient, $request->facebook_id);
        }
    }

    public function getTokenAndRefreshTokenWithoutPassword(OClient $oClient, $facebook_id)
    {
        $oClient = OClient::where('password_client', 1)->first();

        $client = new \GuzzleHttp\Client();

        $response = $client->request('POST', env('URL_SHORT') . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'social',
                'client_id' => $oClient->id,
                'client_secret' => $oClient->secret,
                'provider' => 'facebook',
                'provider_user_id' => $facebook_id,
                'scope' => '',
            ],
        ]);

        $user = Auth::user();

        $result = json_decode((string)$response->getBody(), true);

        $result['id'] = $user->id;
        $result['name'] = $user->name;
        $result['email'] = $user->email;
        $result['telefon'] = $user->telefon;

        return response()->json($result, 200);
    }

    public function getTokenAndRefreshToken(OClient $oClient, $email, $password)
    {
        $oClient = OClient::where('password_client', 1)->first();

        $client = new \GuzzleHttp\Client();

        $response = $client->request('POST', env('URL_SHORT') . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $oClient->id,
                'client_secret' => $oClient->secret,
                'username' => $email,
                'password' => $password,
            ],
        ]);

        $user = Auth::user();

        $result = json_decode((string)$response->getBody(), true);

        $result['id'] = $user->id;
        $result['name'] = $user->name;
        $result['email'] = $user->email;
        $result['telefon'] = $user->telefon;

        return response()->json($result, 200);
    }

}

?>