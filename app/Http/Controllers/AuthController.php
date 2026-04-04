<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $token = Str::random(60);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'player',
            'api_token' => $token
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,setter,player'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'api_token' => null
        ]);

        return response()->json([
            'message' => 'User created',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = Str::random(60);
        $user->api_token = $token;
        $user->save();

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        $token = $request->bearerToken();
        $user = User::where('api_token', $token)->first();

        return response()->json(['user' => $user]);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        $user = User::where('api_token', $token)->first();

        if ($user) {
            $user->api_token = null;
            $user->save();
        }

        return response()->json(['message' => 'Logged out']);
    }

    public function allUsers()
    {
        $users = User::all()->map(function ($user) {
            return [
                '_id' => isset($user->_id) ? (string) $user->_id : null,
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'role' => $user->role ?? 'player',
            ];
        });

        return response()->json($users);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,setter,player'
        ]);

        $user = User::where('_id', new ObjectId($id))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json(['message' => 'Role updated']);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::where('_id', new ObjectId($id))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->_id . ',_id'
        ]);

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;

        $user->save();

        return response()->json(['message' => 'User updated']);
    }

    public function deleteUser($id)
    {
        $user = User::where('_id', new ObjectId($id))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
