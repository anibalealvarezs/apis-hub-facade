<?php

namespace App\Http\Controllers;

use App\Models\ProjectTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    public function accept(Request $request, $token)
    {
        $transfer = ProjectTransfer::where('token', $token)->firstOrFail();

        // 1. Validar expiración
        if ($transfer->expires_at->isPast()) {
            return redirect('/')->withErrors(['transfer' => 'El enlace de transferencia ha expirado.']);
        }

        // 2. Si no está logueado, forzar login
        if (!Auth::check()) {
            $request->session()->put('url.intended', url()->current());
            return redirect()->route('filament.app.auth.login')
                ->with('warning', 'Por favor, inicia sesión para aceptar la transferencia.');
        }

        $user = Auth::user();

        // 3. Validar que el usuario logueado es el destinatario
        if ($user->id !== $transfer->to_user_id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.app.auth.login')
                ->with('warning', 'Has iniciado sesión con una cuenta distinta a la solicitada para la transferencia.');
        }

        // 4. Ejecutar la transferencia
        $project = $transfer->project;
        
        // Cambiar el ownership verdadero (trueOwner)
        $project->user_id = $user->id;
        $project->save();

        // Eliminar la transferencia
        $transfer->delete();

        // Redirigir al proyecto con éxito
        return redirect()->route('filament.app.pages.dashboard', ['tenant' => $project->subdomain])
            ->with('success', '¡Has aceptado la propiedad del proyecto!');
    }
}
