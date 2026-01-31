<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::query()->orderByDesc('created_at')->get();

        return view('users', [
            'users' => $users,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'role' => ['required', 'string', 'max:50', 'in:encoder,admin,collector,agent,manager'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->role = $data['role'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return Redirect::route('users')->with('status', 'User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return Redirect::route('users')->with('status', 'User deleted.');
    }
}
