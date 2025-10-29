<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','show']]);
        $this->middleware('permission:user-create', ['only' => ['store']]);
        $this->middleware('permission:user-edit', ['only' => ['update', 'updateStatus', 'updateAvatar']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $users = User::with('roles')->paginate(10);
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array']
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->roles);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array']
        ]);

        $user->update($request->except('password'));

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        $user->syncRoles($request->roles);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate(['status' => 'required|in:active,inactive,suspended']);
        $user->status = $request->status;
        $user->save();
        return response()->json($user);
    }

    public function updateAvatar(Request $request, User $user)
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
            $user->save();
        }

        return response()->json($user);
    }
}
