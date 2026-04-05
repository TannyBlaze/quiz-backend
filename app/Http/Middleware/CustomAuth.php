<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class CustomAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'No token provided'], 401);
        }

        $session = DB::connection('mongodb')
            ->table('tokens')
            ->where('token', $token)
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if (isset($session->expires_at) && now()->greaterThan($session->expires_at)) {
            DB::connection('mongodb')
                ->table('tokens')
                ->where('token', $token)
                ->delete();

            return response()->json(['message' => 'Token expired'], 401);
        }

        $user = User::where('_id', new ObjectId($session->user_id))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
