<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeamUserController
{
    public function index(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        return view('users', ['users' => User::orderBy('name')->simplePaginate(20)]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $request->merge(['email' => strtolower(trim($request->string('email')->value()))]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:254|unique:users,email',
            'password' => [
                'required', 'string', 'min:12', 'confirmed',
                function (string $attribute, string $value, \Closure $fail): void {
                    if (strlen($value) > 72 || str_contains($value, "\0")) {
                        $fail('Use uma senha de até 72 bytes sem caracteres nulos.');
                    }
                },
            ],
        ]);

        try {
            User::create($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Esse e-mail já está cadastrado.']);
        }

        return redirect()->route('users.index')->with('message', 'Conta da equipe criada.');
    }
}
