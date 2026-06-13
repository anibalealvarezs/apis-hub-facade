@extends('legal.layout')

@section('title', 'Instrucciones de Eliminación de Datos')

@section('content')
    <h1>INSTRUCCIONES DE ELIMINACIÓN DE DATOS</h1>

    <p>APIs Hub no almacena sus datos personales de forma permanente en nuestros servidores a menos que sea estrictamente necesario para la operación de los servicios que usted ha solicitado explícitamente (tales como sus IDs de Cuenta Publicitaria o su Correo Electrónico para propósitos de autenticación).</p>

    <p>Si desea eliminar sus actividades o datos personales de la aplicación APIs Hub, puede hacerlo siguiendo estos sencillos pasos:</p>

    <h2>1. Eliminación Automatizada a través de las Plataformas de los Proveedores</h2>
    <p>Puede solicitar la eliminación de datos o revocar el acceso directamente desde la configuración de sus proveedores conectados (por ejemplo, Meta/Facebook, Google, etc.). Esto activa una solicitud automatizada a nuestros sistemas:</p>
    <ul>
        <li><strong>Desautorización:</strong> Cuando elimina a APIs Hub de sus aplicaciones activas en el proveedor, inmediatamente borramos las conexiones del nodo remoto y eliminamos sus credenciales asociadas de nuestros sistemas.</li>
        <li><strong>Solicitud de Eliminación de Datos:</strong> Si solicita explícitamente la eliminación de datos a través de la plataforma del proveedor, APIs Hub automáticamente crea un <strong>Ticket de Soporte</strong> en su nombre. Recibirá un código de confirmación vinculado a este ticket, y nuestro equipo revisará y procesará la eliminación completa de los datos dentro de <strong>48-72 horas</strong>.</li>
    </ul>

    <h2>2. Solicitud Manual de Eliminación de Datos</h2>
    <p>También puede solicitar la eliminación de datos de forma manual en cualquier momento utilizando nuestro sistema de Tickets de Soporte:</p>
    <ol>
        <li>Inicie sesión en su <strong>Portal de Cuenta</strong> de APIs Hub.</li>
        <li>Vaya a la sección de <strong>Tickets de Soporte</strong>.</li>
        <li>Cree un nuevo ticket solicitando una <strong>Eliminación de Datos</strong>.</li>
        <li>Nuestro equipo procesará su solicitud dentro de <strong>48-72 horas</strong>.</li>
        <li>Una vez procesada, eliminaremos permanentemente todos sus registros asociados de nuestras bases de datos y revocaremos cualquier Token de Acceso activo conectado a APIs externas.</li>
    </ol>

    <h2>¿Qué datos serán eliminados?</h2>
    <ul>
        <li>Todas las asociaciones de Cuentas de Proveedor.</li>
        <li>Tokens de Acceso y Tokens de Actualización del Proveedor.</li>
        <li>Métricas de informes en caché asociadas con su cuenta.</li>
        <li>Información de perfil del usuario (Nombre y Correo Electrónico).</li>
    </ul>

    <h2>Suspensión de Proyectos y Retención</h2>
    <p>Cuando un proyecto es suspendido permanentemente, APIs Hub mantiene los datos asociados por un período de seguridad de <strong>30 días</strong>. Esto asegura que los datos puedan ser recuperados en caso de suspensión accidental o si el usuario cambia de opinión.</p>
    <ul>
        <li>Una vez que expira el período de 30 días, todos los datos se eliminan de forma automática y permanente.</li>
        <li>Si un usuario solicita la eliminación explícita a través de un Ticket de Soporte, el proceso se acelera y se completa dentro de <strong>24 a 72 horas</strong>.</li>
        <li>Las cuentas de usuario (perfiles) no se eliminan automáticamente cuando se suspende un proyecto; solo se eliminan mediante solicitud explícita vía Ticket de Soporte.</li>
    </ul>

    <h2>Cierre de Sesión Social Manual</h2>
    <p>Adicionalmente, siempre puede eliminar el acceso de APIs Hub a sus datos directamente a través de la configuración de su proyecto en APIs Hub:</p>
    <ol>
        <li>Vaya a la sección de <strong>Fuentes de Datos</strong> (Data Sources) de su proyecto y desconecte las cuentas haciendo clic en el botón de desvincular/cerrar sesión.</li>
    </ol>

    <p>Para cualquier consulta adicional, por favor contacte a nuestro equipo de soporte en <a href="mailto:admin@apis-hub.cloud">admin@apis-hub.cloud</a> o abra un Ticket de Soporte.</p>
@endsection
