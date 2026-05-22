<x-mail::message>
# Transferencia de Propiedad: {{ $project->name }}

Hola **{{ $toUser->name }}**,

**{{ $fromUser }}** ha iniciado una solicitud para transferirte la propiedad absoluta del proyecto **{{ $project->name }}** en APIs Hub.

Al aceptar esta transferencia:
- Te convertirás en el Propietario Único del proyecto.
- Tendrás permisos exclusivos para eliminar el proyecto, o volver a transferirlo.
- {{ $fromUser }} perderá sus derechos de propiedad absoluta, aunque mantendrá acceso administrativo al proyecto.

Esta solicitud expira en 48 horas.

<x-mail::button :url="$acceptUrl" color="success">
Aceptar Transferencia
</x-mail::button>

Si no esperabas esta solicitud, simplemente puedes ignorar este correo.

Saludos,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
