<?php

namespace App\Http\Controllers;

use App\Models\CoverageDriveConnection;
use App\Models\EventCoverage;
use App\Services\CoverageDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CoverageDriveController
{
    public function show(Request $request, CoverageDrive $drive)
    {
        abort_unless($request->user()->is_admin, 403);

        return view('events.coverage-drive', [
            'connection' => CoverageDriveConnection::find(1), 'configured' => $drive->configured(),
        ]);
    }

    public function start(Request $request, CoverageDrive $drive)
    {
        abort_unless($request->user()->is_admin, 403);
        abort_unless($drive->configured(), 404);
        $state = Str::random(64);
        $request->session()->put('coverage_drive_oauth', ['state' => $state, 'expires' => time() + 600]);

        return redirect()->away($drive->authorizationUrl($state));
    }

    public function callback(Request $request, CoverageDrive $drive)
    {
        abort_unless($request->user()->is_admin, 403);
        $state = $request->session()->pull('coverage_drive_oauth');
        abort_unless($state && $state['expires'] >= time()
            && hash_equals($state['state'], $request->query->getString('state')), 403);

        try {
            $refresh = $drive->authorize($request->query->getString('code'));
            $candidate = new CoverageDriveConnection(['refresh_token' => $refresh]);
            $connection = CoverageDriveConnection::find(1);
            if ($connection?->parent_id) {
                $drive->privateParent($drive->client($candidate), $connection->parent_id);
            }
            CoverageDriveConnection::updateOrCreate(['id' => 1], ['refresh_token' => $refresh]);
        } catch (Throwable) {
            return redirect()->route('coverage.drive')->withErrors(['drive' => 'Não foi possível conectar o Drive. Confira a autorização e se a conta é dona da pasta atual.']);
        }

        return redirect()->route('coverage.drive')->with('message', 'Drive conectado. Escolha a pasta principal.');
    }

    public function folder(Request $request, CoverageDrive $drive)
    {
        abort_unless($request->user()->is_admin, 403);
        $data = $request->validate(['folder' => 'required|string|max:500']);
        $id = $data['folder'];
        if (preg_match('~^https://drive\.google\.com/(?:drive/(?:u/\d+/)?folders)/([a-zA-Z0-9_-]+)(?:[/?#].*)?$~D', $id, $matches)) {
            $id = $matches[1];
        }
        $connection = CoverageDriveConnection::find(1);
        if (!$connection || !preg_match('/^[a-zA-Z0-9_-]{10,200}$/D', $id)) {
            return back()->withErrors(['folder' => 'Conecte o Drive e informe o link ou ID de uma pasta.']);
        }
        try {
            $folder = $drive->privateParent($drive->client($connection), $id);
        } catch (Throwable) {
            return back()->withErrors(['folder' => 'Escolha uma pasta privada, de sua propriedade, no Meu Drive.']);
        }

        $connection->update(['parent_id' => $folder['id'], 'parent_name' => $folder['name']]);

        EventCoverage::where('failure_stage', 'Pasta no Drive')->update(['pending' => true]);

        return back()->with('message', 'Pasta principal salva.');
    }
}
