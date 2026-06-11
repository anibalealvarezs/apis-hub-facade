<?php

namespace App\Http\Controllers;

use App\Models\ProjectTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    public function review(Request $request, $token)
    {
        $transfer = ProjectTransfer::where('token', $token)->firstOrFail();

        if ($transfer->status !== 'pending' || $transfer->expires_at->isPast()) {
            if ($transfer->status === 'pending' && $transfer->expires_at->isPast()) {
                $transfer->update(['status' => 'expired']);
            }
            return redirect('/')->withErrors(['transfer' => 'Este enlace de transferencia ya no es válido o ha expirado.']);
        }

        if (!Auth::check()) {
            $request->session()->put('url.intended', url()->current());
            return redirect()->route('filament.app.auth.login')
                ->with('warning', 'Por favor, inicia sesión para revisar la transferencia.');
        }

        $user = Auth::user();

        if ($user->id !== $transfer->to_user_id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.app.auth.login')
                ->with('warning', 'Has iniciado sesión con una cuenta distinta a la solicitada para la transferencia.');
        }

        $userBillingProfiles = \App\Models\BillingProfile::where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing'])
            ->get();

        return view('app.transfers.review', [
            'transfer' => $transfer,
            'project' => $transfer->project,
            'userBillingProfiles' => $userBillingProfiles,
        ]);
    }

    public function process(Request $request, $token)
    {
        $transfer = ProjectTransfer::where('token', $token)->where('status', 'pending')->firstOrFail();
        $user = Auth::user();

        if ($user->id !== $transfer->to_user_id || $transfer->expires_at->isPast()) {
            return redirect('/')->withErrors(['transfer' => 'Transferencia inválida o expirada.']);
        }

        $project = $transfer->project;
        $oldOwnerId = $project->user_id;
        $oldOwnerUser = \App\Models\User::find($oldOwnerId);

        // 1. Validar Facturación y Cuotas
        $lifecycleService = app(\App\Services\BillingLifecycleService::class);

        if ($transfer->billing_action === 'remove_bp') {
            $request->validate([
                'billing_profile_id' => 'required|exists:billing_profiles,id',
            ]);

            $selectedBp = \App\Models\BillingProfile::where('id', $request->billing_profile_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Check limit
            $maxProjects = $lifecycleService->getMaxProjectsForTier($selectedBp->tier);
            $activeProjectsCount = $selectedBp->projects()->where('billing_status', 'active')->count();

            if ($activeProjectsCount >= $maxProjects) {
                return back()->withErrors(['billing_profile_id' => "El perfil de facturación seleccionado ha alcanzado su límite de $maxProjects proyectos. Por favor, selecciona otro perfil o mejora tu plan."]);
            }

            $project->billing_profile_id = $selectedBp->id;
        }

        // 2. Ejecutar la transferencia técnica
        $project->user_id = $user->id;
        $project->save();

        // Retener acceso
        if ($transfer->retain_access && $oldOwnerUser) {
            $project->users()->syncWithoutDetaching([$oldOwnerId]);
            setPermissionsTeamId($project->id);
            $oldOwnerUser->removeRole('project_owner');
            $oldOwnerUser->assignRole('project_editor');
        }

        // Asignar rol al nuevo dueño
        setPermissionsTeamId($project->id);
        $user->removeRole('project_editor');
        $user->removeRole('project_viewer');
        $user->assignRole('project_owner');

        // Manejar facturación adicional
        if ($transfer->billing_action === 'share_sender_bp' && $project->billing_profile_id) {
            \Illuminate\Support\Facades\DB::table('billing_profile_user')->updateOrInsert([
                'billing_profile_id' => $project->billing_profile_id,
                'user_id' => $user->id,
            ]);
        }

        $transfer->update(['status' => 'accepted']);
        $transfer->delete(); // Opcional, pero lo mantendremos para limpiar la DB o podríamos dejarlo en SoftDeletes. Por ahora lo borramos como estaba, pero guardando el estado antes para logs si hay un observer.

        // Notificar al antiguo dueño que se aceptó (TODO: Crear Mailable)

        return redirect()->route('filament.app.pages.dashboard', ['tenant' => $project->subdomain])
            ->with('success', '¡Has aceptado la propiedad del proyecto exitosamente!');
    }

    public function reject(Request $request, $token)
    {
        $transfer = ProjectTransfer::where('token', $token)->where('status', 'pending')->firstOrFail();
        $user = Auth::user();

        if ($user->id !== $transfer->to_user_id) {
            abort(403);
        }

        $transfer->update(['status' => 'rejected']);
        $transfer->delete();

        // Notificar al antiguo dueño que se rechazó (TODO: Crear Mailable)

        return redirect()->route('filament.app.pages.dashboard')->with('info', 'Has rechazado la transferencia del proyecto.');
    }
}
