<?php

namespace App\Services;

use App\Models\ShakeEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class CoverageMessenger
{
    public function configured(string $channel): bool
    {
        if ($channel === 'email') {
            return config('shake-coverage.mail_enabled') && config('mail.default') === 'smtp';
        }

        $settings = config('shake-coverage.whatsapp');

        return preg_match('/^v\d+\.\d+$/D', $settings['version'] ?? '')
            && ctype_digit($settings['phone_id'] ?? '')
            && $settings['token'] && $settings['template'];
    }

    public function send(ShakeEvent $event, array $contact, array $links): bool
    {
        $coverage = $event->coverage;
        $time = substr($coverage->starts_at, 0, 5).' às '.substr($coverage->ends_at, 0, 5);
        $deadline = $coverage->deadline?->format('d/m/Y') ?? 'A combinar';
        $date = $event->event_date->format('d/m/Y');
        $linkText = implode("\n", $links);

        if ($contact['channel'] === 'email') {
            return Mail::raw(
                "Olá, {$contact['name']}!\n\nEvento: {$event->title}\nData: {$date}\nCobertura: {$time}\nEndereço: {$event->address}\nPrazo: {$deadline}\n\nEnvie os arquivos pelo Drive:\n{$linkText}",
                fn ($mail) => $mail->to($contact['email'])->subject('Cobertura audiovisual · '.$event->title)
            ) !== null;
        }

        $settings = config('shake-coverage.whatsapp');
        $parameters = array_map(fn ($text) => [
            'type' => 'text', 'text' => preg_replace('/\s+/u', ' ', trim($text)),
        ], [$contact['name'], $event->title, $date, $time, $event->address, $deadline, implode(' | ', $links)]);

        $response = Http::withToken($settings['token'])->connectTimeout(3)->timeout(12)
            ->post('https://graph.facebook.com/'.$settings['version'].'/'.$settings['phone_id'].'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($contact['whatsapp'], '+'),
                'type' => 'template',
                'template' => [
                    'name' => $settings['template'],
                    'language' => ['code' => 'pt_BR'],
                    'components' => [['type' => 'body', 'parameters' => $parameters]],
                ],
            ]);
        if ($response->serverError() || ($response->successful() && !$response->json('messages.0.id'))) {
            throw new RuntimeException('O WhatsApp não confirmou se aceitou a mensagem.');
        }

        return $response->successful();
    }
}
