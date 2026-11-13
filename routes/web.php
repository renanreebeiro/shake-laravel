<?php
use App\Http\Controllers\ShakeEventController;
use App\Http\Controllers\CoverageDriveController;
use App\Http\Controllers\ShakeCalendarConnectionController;
use App\Http\Controllers\TeamUserController;
use App\Http\Middleware\RequireAreaAccess;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::view('/entrar', 'login')->middleware('guest')->name('login');
Route::post('/entrar', function (Request $request) {
    $credentials = $request->validate(['email' => 'required|email|max:254', 'password' => 'required|string|max:255']);
    $credentials['email'] = strtolower($credentials['email']);
    if (!Auth::attempt($credentials)) {
        return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
    }
    $request->session()->regenerate();
    return redirect()->intended('/');
})->middleware(['guest', 'throttle:8,1']);

Route::middleware('auth')->group(function () {
    Route::post('/sair', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    });

    Route::get('/', function (Request $request) {
        if (!$request->user()->hasAreaAccess('documents')) {
            if ($request->user()->hasAreaAccess('events')) {
                return redirect()->route('events.index');
            }
            if ($request->user()->hasAreaAccess('templates')) {
                return redirect()->route('templates.index');
            }
            return view('no-area');
        }
        return view('catalog', ['templates' => DocumentTemplate::where('active', true)->orderBy('title')->get()]);
    });

    Route::post('/integracoes/google-agenda', [ShakeCalendarConnectionController::class, 'start'])->name('calendar.connect');
    Route::get('/integracoes/google-agenda/retorno', [ShakeCalendarConnectionController::class, 'callback'])->name('calendar.callback');

    Route::middleware(RequireAreaAccess::class.':events')->group(function () {
        Route::get('/eventos/cobertura/drive', [CoverageDriveController::class, 'show'])->name('coverage.drive');
        Route::post('/eventos/cobertura/drive', [CoverageDriveController::class, 'start'])->name('coverage.drive.connect');
        Route::get('/eventos/cobertura/drive/retorno', [CoverageDriveController::class, 'callback'])->name('coverage.drive.callback');
        Route::post('/eventos/cobertura/pasta', [CoverageDriveController::class, 'folder'])->name('coverage.drive.folder');
        Route::post('/eventos/{event}/cobertura/tentar', [ShakeEventController::class, 'retryCoverage'])->name('coverage.retry');
        Route::resource('eventos', ShakeEventController::class)
            ->parameters(['eventos' => 'event'])
            ->names('events')
            ->except('destroy');
        Route::patch('/eventos/{event}/status', [ShakeEventController::class, 'status'])->name('events.status');
    });

    Route::get('/documentos/{template:slug}', function (DocumentTemplate $template) {
        abort_unless($template->active, 404);
        preg_match_all('/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/', $template->body, $matches);
        return response()->view('issue', ['template' => $template, 'fields' => array_unique($matches[1])])
            ->header('Cache-Control', 'private, no-store');
    })->middleware(RequireAreaAccess::class.':documents')->name('documents.show');

    Route::get('/usuarios', [TeamUserController::class, 'index'])->name('users.index');
    Route::post('/usuarios', [TeamUserController::class, 'store'])->name('users.store');
    Route::patch('/usuarios/{user}/acessos', [TeamUserController::class, 'updateAccess'])->name('users.access');

    Route::get('/modelos', function (Request $request) {
        $templates = DocumentTemplate::orderBy('title')->get();
        $selected = $request->filled('editar') ? $templates->firstWhere('id', $request->integer('editar')) : null;
        return view('templates', compact('templates', 'selected'));
    })->middleware(RequireAreaAccess::class.':templates')->name('templates.index');

    Route::post('/modelos/{template?}', function (Request $request, ?DocumentTemplate $template = null) {
        $data = $request->validate(['title' => 'required|string|max:120', 'body' => 'required|string|max:20000']);
        $data['active'] = $request->boolean('active');
        if ($template?->exists) {
            $template->update($data);
        } else {
            $data['slug'] = Str::uuid()->toString();
            DocumentTemplate::create($data);
        }
        return redirect()->route('templates.index')->with('message', 'Modelo salvo.');
    })->middleware(RequireAreaAccess::class.':templates')->name('templates.save');
});
