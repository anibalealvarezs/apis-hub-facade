<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revisar Transferencia de Proyecto</title>
    @vite(['resources/css/app.css'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen text-gray-800 font-sans">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 border border-gray-100">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Transferencia de Proyecto</h1>
            <p class="text-sm text-gray-500 mt-2">Has sido invitado a tomar la propiedad del proyecto <strong>{{ $project->name }}</strong>.</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 text-sm border border-red-100">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('transfers.process', ['token' => $transfer->token]) }}" class="space-y-6">
            @csrf
            
            <div class="bg-gray-50 p-4 rounded-lg text-sm border border-gray-200">
                <p class="mb-2"><strong>Remitente:</strong> {{ $transfer->fromUser->name }}</p>
                <p><strong>Caduca:</strong> {{ $transfer->expires_at->diffForHumans() }}</p>
            </div>

            @if($transfer->billing_action === 'remove_bp')
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">El remitente ha desvinculado la facturación. Selecciona un perfil de facturación para cubrir este proyecto:</label>
                    <select name="billing_profile_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-2.5 border">
                        <option value="">Selecciona un perfil...</option>
                        @foreach($userBillingProfiles as $bp)
                            <option value="{{ $bp->id }}">{{ $bp->name ?: $bp->reference_name }} ({{ ucfirst($bp->tier) }})</option>
                        @endforeach
                    </select>
                </div>
            @elseif($transfer->billing_action === 'share_sender_bp')
                <div class="bg-blue-50 text-blue-700 p-4 rounded-lg text-sm border border-blue-100">
                    El remitente ha decidido compartir su perfil de facturación actual contigo. No necesitas proveer un método de pago en este momento.
                </div>
            @elseif($transfer->billing_action === 'keep_bp')
                <div class="bg-green-50 text-green-700 p-4 rounded-lg text-sm border border-green-100">
                    El proyecto ya utiliza un perfil de facturación al que tienes acceso. Se mantendrá igual.
                </div>
            @endif

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 bg-indigo-600 text-white font-medium py-2.5 px-4 rounded-lg hover:bg-indigo-700 transition shadow-sm">
                    Aceptar Transferencia
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('transfers.reject', ['token' => $transfer->token]) }}" class="mt-4">
            @csrf
            <button type="submit" class="w-full bg-white text-gray-700 font-medium py-2.5 px-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm" onclick="return confirm('¿Estás seguro de que deseas rechazar esta transferencia?')">
                Rechazar
            </button>
        </form>
    </div>
</body>
</html>
