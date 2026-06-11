<x-mail::message>
# Has sido invitado a colaborar

Se te ha invitado a colaborar en el proyecto **{{ $projectName }}** con el rol de **{{ $roleName }}**.

Para aceptar la invitación y unirte al proyecto, haz clic en el siguiente botón:

<x-mail::button :url="$acceptUrl">
Aceptar Invitación
</x-mail::button>

Si no tienes una cuenta en la plataforma, este enlace te permitirá crear una de forma segura, validar tu correo automáticamente, y unirte directo al proyecto.

Si tú no solicitaste esto, puedes ignorar este correo.

Saludos,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
