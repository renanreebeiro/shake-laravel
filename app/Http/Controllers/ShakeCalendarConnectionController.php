<?php

namespace App\Http\Controllers;

use App\Services\ShakeGoogleCalendar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShakeCalendarConnectionController
{
    public function start(Request $request, ShakeGoogleCalendar $calendar)
    {
        abort_unless($request->user()->is_admin, 403);
        abort_unless($calendar->configured(), 404);

        $state = Str::random(64);
        $request->session()->put('shake_calendar_oauth', ['state' => $state, 'expires' => time() + 600]);

        return redirect()->away($calendar->authorizationUrl($state));
    }

    public function callback(Request $request, ShakeGoogleCalendar $calendar)
    {
        abort_unless($request->user()->is_admin, 403);
        abort_unless($calendar->configured(), 404);

        $saved = $request->session()->pull('shake_calendar_oauth');
        abort_unless($saved && $saved['expires'] >= time()
            && hash_equals($saved['state'], $request->query->getString('state')), 403);

        $code = $request->query->getString('code');
        try {
            $connected = $code !== '' && $calendar->authorize($code);
        } catch (ConnectionException) {
            $connected = false;
        }

        return redirect()->route('events.index')->with('message', $connected
            ? 'Google Agenda autorizado para a Shake Burger.'
            : 'A conexão não foi concluída. Confira se usou a conta da integração e se ela pode editar a agenda EVENTOS SHAKE.');
    }
}
