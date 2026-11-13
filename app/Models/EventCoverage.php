<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCoverage extends Model
{
    public const TYPES = [
        'pending' => 'A definir', 'none' => 'Sem cobertura', 'photo' => 'Foto',
        'video' => 'Vídeo', 'both' => 'Foto e vídeo',
    ];

    public const ROLES = ['photo' => 'Fotógrafo', 'video' => 'Filmmaker'];
    public const FOLDERS = ['photo' => 'Fotos', 'video' => 'Vídeos'];
    public const CHANNELS = ['email' => 'E-mail', 'whatsapp' => 'WhatsApp'];

    protected $primaryKey = 'shake_event_id';
    public $incrementing = false;
    protected $fillable = ['type', 'contacts', 'starts_at', 'ends_at', 'deadline'];
    protected $casts = ['contacts' => 'array', 'folders' => 'array', 'grants' => 'array', 'deadline' => 'immutable_date'];

    public function roles(): array
    {
        return match ($this->type) {
            'both' => array_keys(self::ROLES),
            'photo', 'video' => [$this->type],
            default => [],
        };
    }

    public function recipients(): array
    {
        $recipients = [];
        foreach ($this->roles() as $role) {
            $contact = $this->contacts[$role] ?? [];
            if (!empty($contact['email'])) {
                $recipients[$contact['email']] ??= $contact + ['roles' => []];
                $recipients[$contact['email']]['roles'][] = $role;
            }
        }

        return $recipients;
    }

    public function fingerprint(array $contact): string
    {
        $channel = $contact['channel'] ?? '';
        $destination = $channel === 'email' ? $contact['email'] : ($contact['whatsapp'] ?? '');

        return hash('sha256', json_encode([$contact['email'], $channel, $destination, $contact['roles']], JSON_THROW_ON_ERROR));
    }

    public function deliveryStatus(ShakeEvent $event, string $role): string
    {
        $email = $this->contacts[$role]['email'] ?? '';
        $contact = $this->recipients()[$email] ?? null;
        if ($contact) {
            $fingerprint = $this->fingerprint($contact);
            $latest = $event->coverageDeliveries->sortByDesc('id')->firstWhere('fingerprint', $fingerprint);
            if ($latest) {
                return EventCoverageDelivery::STATUSES[$latest->status];
            }
        }

        return 'Envio pendente';
    }

    public function folderUrl(string $role = 'event'): ?string
    {
        return !empty($this->folders[$role]['ready'])
            ? 'https://drive.google.com/drive/folders/'.rawurlencode($this->folders[$role]['id'])
            : null;
    }
}
