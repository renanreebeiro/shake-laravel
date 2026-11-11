<?php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('shake:usuario {email} {--admin}', function () {
    $email = strtolower(trim($this->argument('email')));
    $password = $this->secret('Senha da conta');
    $validation = Validator::make(compact('email', 'password'), [
        'email' => ['required', 'email', 'max:254', 'unique:users,email'],
        'password' => ['required', 'string', 'min:12', 'max:72'],
    ]);
    if ($validation->fails()) {
        $this->error($validation->errors()->first());
        return 1;
    }
    User::create(['name' => $email, 'email' => $email, 'password' => $password, 'is_admin' => $this->option('admin')]);
    $this->info('Conta criada.');
    return 0;
})->purpose('Criar uma conta de equipe ou administração');
