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

Artisan::command('shake:calendar-sync', function () {
    $calendar = app(\App\Services\ShakeGoogleCalendar::class);
    if (!$calendar->connected()) {
        $this->error('A conexão com a agenda da Shake Burger ainda não foi configurada.');

        return 1;
    }

    $lock = \Illuminate\Support\Facades\Cache::lock('shake-google-calendar-sync', 3600);
    if (!$lock->get()) {
        $this->info('Já existe uma sincronização em andamento.');

        return 0;
    }

    try {
        $counts = app(\App\Services\SyncShakeCalendar::class)->run($calendar->connection(), $calendar->calendarKey());
        foreach ($counts as $type => $count) {
            $this->line(ucfirst($type).': '.$count);
        }
    } catch (\Illuminate\Http\Client\RequestException $e) {
        $this->error('A API da agenda retornou HTTP '.$e->response->status().'. Verifique o compartilhamento e tente novamente.');

        return 1;
    } catch (\Illuminate\Http\Client\ConnectionException) {
        $this->error('O servidor não conseguiu se conectar ao Google Agenda. Tente novamente.');

        return 1;
    } catch (\JsonException) {
        $this->error('Não foi possível interpretar os dados da conexão com o Google Agenda.');

        return 1;
    } catch (\RuntimeException $e) {
        if ($e::class !== \RuntimeException::class) {
            throw $e;
        }
        $this->error($e->getMessage());

        return 1;
    } finally {
        $lock->release();
    }

    return $counts['conflitos'] > 0 ? 1 : 0;
})->purpose('Sincronizar eventos com a agenda da Shake Burger');

if (app(\App\Services\ShakeGoogleCalendar::class)->connected()) {
    \Illuminate\Support\Facades\Schedule::command('shake:calendar-sync')->everyFiveMinutes();
}
