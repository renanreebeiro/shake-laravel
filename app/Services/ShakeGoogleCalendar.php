<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ShakeGoogleCalendar
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function calendarKey(): string
    {
        return hash('sha256', $this->calendarId());
    }

    public function connection(): PendingRequest
    {
        $id = $this->calendarId();
        if (!$this->connected()) {
            throw new RuntimeException('Autorize o Google Agenda na Central antes de sincronizar.');
        }

        $response = $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => Crypt::decryptString(Storage::disk('local')->get(self::TOKEN_FILE)),
        ]);
        $token = $response->json('access_token');
        if (!$response->successful() || !is_string($token) || $token === '') {
            throw new RuntimeException('O Google recusou a autorização da agenda (HTTP '.$response->status().').');
        }

        return Http::baseUrl('https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($id))
            ->acceptJson()->withToken($token)->connectTimeout(5)->timeout(15);
    }

    private function calendarId(): string
    {
        $id = trim((string) config('shake-calendar.id'));
        if (!preg_match('/^[a-z0-9]+@group\.calendar\.google\.com$/iD', $id)) {
            throw new RuntimeException('Configure o ID da agenda compartilhada da Shake Burger.');
        }

        return strtolower($id);
    }

    private const TOKEN_FILE = 'shake-calendar-token.enc';
    private const SCOPE = 'openid email https://www.googleapis.com/auth/calendar.events';

    public function configured(): bool
    {
        return config('shake-calendar.id') && config('shake-calendar.client_id')
            && config('shake-calendar.client_secret') && config('shake-calendar.email');
    }

    public function connected(): bool
    {
        return $this->configured() && Storage::disk('local')->exists(self::TOKEN_FILE);
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('shake-calendar.client_id'),
            'redirect_uri' => rtrim(config('app.url'), '/').route('calendar.callback', absolute: false),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
            'login_hint' => config('shake-calendar.email'),
            'state' => $state,
        ]);
    }

    public function authorize(string $code): bool
    {
        $response = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'redirect_uri' => rtrim(config('app.url'), '/').route('calendar.callback', absolute: false),
            'code' => $code,
        ]);
        if (!$response->successful() || !is_string($response->json('refresh_token'))
            || !is_string($response->json('access_token'))
            || !in_array('https://www.googleapis.com/auth/calendar.events', explode(' ', (string) $response->json('scope')), true)) {
            return false;
        }

        $google = Http::acceptJson()->withToken($response->json('access_token'))->connectTimeout(5)->timeout(15);
        $identity = $google->get('https://openidconnect.googleapis.com/v1/userinfo');
        if (!$identity->successful() || $identity->json('email_verified') !== true
            || strcasecmp((string) $identity->json('email'), (string) config('shake-calendar.email')) !== 0) {
            return false;
        }

        $events = $google->get('https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($this->calendarId()).'/events', [
            'maxResults' => 1,
            'fields' => 'accessRole',
        ]);
        if (!$events->successful() || !in_array($events->json('accessRole'), ['writer', 'owner', 'writerWithoutPrivateAccess'], true)) {
            return false;
        }

        return Storage::disk('local')->put(self::TOKEN_FILE, Crypt::encryptString($response->json('refresh_token')));
    }

    private function tokenRequest(array $data): \Illuminate\Http\Client\Response
    {
        return Http::asForm()->connectTimeout(5)->timeout(15)->post(self::TOKEN_URL, $data + [
            'client_id' => config('shake-calendar.client_id'),
            'client_secret' => config('shake-calendar.client_secret'),
        ]);
    }
}
