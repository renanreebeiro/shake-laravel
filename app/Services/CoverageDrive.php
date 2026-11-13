<?php

namespace App\Services;

use App\Models\CoverageDriveConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CoverageDrive
{
    private const FOLDER = 'application/vnd.google-apps.folder';
    private const SCOPE = 'https://www.googleapis.com/auth/drive';

    public function configured(): bool
    {
        return config('shake-coverage.client_id') && config('shake-coverage.client_secret');
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('shake-coverage.client_id'),
            'redirect_uri' => $this->callback(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
            'state' => $state,
        ]);
    }

    public function authorize(string $code): string
    {
        $token = $this->token([
            'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $this->callback(),
        ]);
        if (empty($token['refresh_token']) || !in_array(self::SCOPE, explode(' ', $token['scope'] ?? ''), true)) {
            throw new RuntimeException('O Google não concedeu acesso contínuo ao Drive.');
        }

        return $token['refresh_token'];
    }

    public function client(CoverageDriveConnection $connection): PendingRequest
    {
        $token = $this->token(['grant_type' => 'refresh_token', 'refresh_token' => $connection->refresh_token]);

        return Http::baseUrl('https://www.googleapis.com/drive/v3')->withToken($token['access_token'])
            ->connectTimeout(3)->timeout(8)->acceptJson();
    }

    public function privateParent(PendingRequest $drive, string $id): array
    {
        $folder = $drive->get('files/'.$id, ['fields' => 'id,name,mimeType,trashed,ownedByMe,capabilities(canAddChildren)'])->throw()->json();
        if (($folder['mimeType'] ?? null) !== self::FOLDER || ($folder['trashed'] ?? false)
            || empty($folder['ownedByMe']) || empty($folder['capabilities']['canAddChildren'])) {
            throw new RuntimeException('Escolha uma pasta própria e ativa no Meu Drive.');
        }

        foreach ($this->permissions($drive, $id) as $permission) {
            if ($permission['role'] !== 'owner') {
                throw new RuntimeException('Escolha uma pasta principal privada para que cada profissional receba acesso apenas à sua função.');
            }
        }

        return $folder;
    }

    public function reserveId(PendingRequest $drive): string
    {
        return $drive->get('files/generateIds', ['count' => 1, 'space' => 'drive'])->throw()->json('ids.0');
    }

    public function createFolder(PendingRequest $drive, string $id, string $parent, string $name): void
    {
        $response = $drive->post('files', [
            'id' => $id, 'name' => $name, 'mimeType' => self::FOLDER, 'parents' => [$parent],
        ]);
        if ($response->status() !== 409) {
            $response->throw();

            return;
        }

        $folder = $drive->get('files/'.$id, ['fields' => 'mimeType,parents,trashed'])->throw()->json();
        if ($folder['mimeType'] !== self::FOLDER || $folder['trashed'] || !in_array($parent, $folder['parents'], true)) {
            throw new RuntimeException('A pasta vinculada não está disponível no local esperado.');
        }
    }

    public function rename(PendingRequest $drive, string $id, string $name): void
    {
        $drive->patch('files/'.$id, ['name' => $name])->throw();
    }

    public function grant(PendingRequest $drive, string $id, string $email): ?string
    {
        foreach ($this->permissions($drive, $id) as $permission) {
            if (strcasecmp($permission['emailAddress'] ?? '', $email) !== 0) {
                continue;
            }
            if ($permission['role'] === 'owner') {
                return null;
            }
            if ($permission['role'] !== 'writer') {
                $drive->patch('files/'.$id.'/permissions/'.$permission['id'], ['role' => 'writer'])->throw();
            }

            return $permission['id'];
        }

        return $drive->post('files/'.$id.'/permissions?sendNotificationEmail=false', [
            'type' => 'user', 'role' => 'writer', 'emailAddress' => $email,
        ])->throw()->json('id');
    }

    public function revoke(PendingRequest $drive, string $folder, string $permission): void
    {
        $response = $drive->delete('files/'.$folder.'/permissions/'.$permission);
        if ($response->status() !== 404) {
            $response->throw();
        }
    }

    private function permissions(PendingRequest $drive, string $folder): iterable
    {
        $pageToken = null;
        do {
            $page = $drive->get('files/'.$folder.'/permissions', array_filter([
                'fields' => 'nextPageToken,permissions(id,type,role,emailAddress)', 'pageToken' => $pageToken,
            ]))->throw()->json();
            yield from $page['permissions'] ?? [];
            $pageToken = $page['nextPageToken'] ?? null;
        } while ($pageToken);
    }

    private function callback(): string
    {
        return rtrim(config('app.url'), '/').route('coverage.drive.callback', absolute: false);
    }

    private function token(array $data): array
    {
        $response = Http::asForm()->connectTimeout(3)->timeout(8)->post('https://oauth2.googleapis.com/token', $data + [
            'client_id' => config('shake-coverage.client_id'), 'client_secret' => config('shake-coverage.client_secret'),
        ]);
        if (!$response->successful() || !$response->json('access_token')) {
            throw new RuntimeException('A autorização do Drive falhou. Confira as credenciais ou reconecte a conta.');
        }

        return $response->json();
    }
}
