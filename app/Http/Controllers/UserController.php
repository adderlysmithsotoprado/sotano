<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Usuarios/Index', [
            'users' => User::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,cajero,mozo,cocina,bar'],
        ]);

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = $data['role'];
        $user->save();

        return redirect()->route('usuarios');
    }
    public function update(Request $request, User $user)
    {
    $data = $request->validate([
        'role' => ['required', 'in:admin,cajero,mozo,cocina,bar'],
    ]);

    $user->role = $data['role'];
    $user->save();

    return back();
    }

    public function destroy(User $user)
    {
    if (auth()->id() === $user->id) {
        return back();
    }

    $user->delete();

    return back();
    }
}
