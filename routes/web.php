<?php
use App\Http\Controllers\ShakeEventController;
use App\Http\Controllers\TeamUserController;
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

    Route::get('/', function () {
        return view('catalog', ['templates' => DocumentTemplate::where('active', true)->orderBy('title')->get()]);
    });

    Route::resource('eventos', ShakeEventController::class)
        ->parameters(['eventos' => 'event'])
        ->names('events')
        ->except('destroy');
    Route::patch('/eventos/{event}/status', [ShakeEventController::class, 'status'])->name('events.status');

    Route::get('/documentos/{template:slug}', function (DocumentTemplate $template) {
        abort_unless($template->active, 404);
        preg_match_all('/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/', $template->body, $matches);
        return response()->view('issue', ['template' => $template, 'fields' => array_unique($matches[1])])
            ->header('Cache-Control', 'private, no-store');
    })->name('documents.show');

    Route::get('/usuarios', [TeamUserController::class, 'index'])->name('users.index');
    Route::post('/usuarios', [TeamUserController::class, 'store'])->name('users.store');

    Route::get('/modelos', function (Request $request) {
        abort_unless($request->user()->is_admin, 403);
        $templates = DocumentTemplate::orderBy('title')->get();
        $selected = $request->filled('editar') ? $templates->firstWhere('id', $request->integer('editar')) : null;
        return view('templates', compact('templates', 'selected'));
    })->name('templates.index');

    Route::post('/modelos/{template?}', function (Request $request, ?DocumentTemplate $template = null) {
        abort_unless($request->user()->is_admin, 403);
        $data = $request->validate(['title' => 'required|string|max:120', 'body' => 'required|string|max:20000']);
        $data['active'] = $request->boolean('active');
        if ($template?->exists) {
            $template->update($data);
        } else {
            $data['slug'] = Str::uuid()->toString();
            DocumentTemplate::create($data);
        }
        return redirect()->route('templates.index')->with('message', 'Modelo salvo.');
    })->name('templates.save');
});
