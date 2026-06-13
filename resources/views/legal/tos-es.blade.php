@extends('legal.layout')

@section('title', 'Términos de Servicio')

@section('content')
    <h1>TÉRMINOS DE SERVICIO</h1>

    <p>Bienvenido a APIs Hub. Al utilizar nuestra aplicación, usted acepta cumplir y estar sujeto a los siguientes términos y condiciones de uso.</p>

    <h2>1. Descripción del Servicio</h2>
    <p>APIs Hub es una infraestructura especializada y herramienta de informes diseñada para agregar y visualizar datos de diversas APIs de marketing, incluyendo, pero no limitado a, Meta Ads (Facebook), Google Ads y Klaviyo.</p>

    <h2>2. Estado Beta y Disponibilidad del Servicio</h2>
    <p>APIs Hub se encuentra actualmente en <strong>Beta</strong>. Aunque nos esforzamos por lograr el máximo tiempo de actividad, el servicio se proporciona "tal cual" y "según disponibilidad". No ofrecemos Acuerdos de Nivel de Servicio (SLAs) con respecto al tiempo de actividad, retrasos en la sincronización de API o estabilidad de las funciones durante la fase Beta. Pueden ocurrir tiempos de inactividad ocasionales o retrasos en los datos a medida que actualizamos nuestra infraestructura.</p>

    <h2>3. Uso de Datos</h2>
    <p>Nuestra aplicación accede a los datos a través de APIs oficiales utilizando autenticación OAuth. Al conectar sus cuentas, usted otorga a APIs Hub el permiso para recuperar y almacenar métricas de informes únicamente con el propósito de proporcionarle paneles visuales e informes automatizados. No modificamos ni escribimos datos en las plataformas conectadas.</p>

    <h2>4. Uso Justo y Límites de API</h2>
    <p>Para garantizar la estabilidad de la red de APIs Hub, usted acepta una Política de Uso Justo. El sondeo excesivo, automatizado o malicioso de nuestros puntos finales de sincronización, o el intento de eludir los límites de velocidad de API de terceros, puede resultar en la limitación temporal o la suspensión permanente de su cuenta y proyectos conectados.</p>

    <h2>5. Facturación y Suscripciones</h2>
    <p>Si actualiza a un nivel de pago a través de Stripe o PayPal, las suscripciones se facturan por adelantado de forma recurrente. Todas las tarifas no son reembolsables a menos que la ley aplicable exija lo contrario. Puede cancelar su suscripción en cualquier momento a través de la configuración de su cuenta, y conservará el acceso a las funciones pagas hasta el final de su ciclo de facturación actual.</p>

    <h2>6. Responsabilidades del Usuario</h2>
    <ul>
        <li>Usted es responsable de mantener la confidencialidad de sus tokens de acceso y credenciales de cuenta.</li>
        <li>Usted se compromete a no utilizar la aplicación para ningún propósito ilegal o no autorizado.</li>
        <li>Usted debe cumplir con todos los términos y políticas aplicables de las plataformas de terceros (por ejemplo, las Políticas para Desarrolladores de Meta) que conecte a APIs Hub.</li>
    </ul>

    <h2>7. Limitación de Responsabilidad</h2>
    <p>APIs Hub se proporciona "tal cual" sin ninguna garantía de ningún tipo. Bajo ninguna circunstancia Aníbal Álvarez o el equipo de desarrollo serán responsables de ningún daño directo, indirecto, incidental o consecuente que resulte del uso de este software o de la imposibilidad de usarlo.</p>

    <h2>8. Modificaciones al Servicio</h2>
    <p>Nos reservamos el derecho de modificar o interrumpir, temporal o permanentemente, el servicio (o cualquier parte del mismo) con o sin previo aviso.</p>

    <h2>9. Privacidad</h2>
    <p>Su uso del servicio también se rige por nuestra <a href="{{ app()->getLocale() === 'es' ? route('legal.privacy.es') : route('legal.privacy') }}" class="text-brand-blue hover:underline">Política de Privacidad</a>.</p>

    <hr>
    <p><strong>Última actualización: 24 de marzo de 2026</strong><br>
    Para consultas relacionadas con estos términos, contáctenos en <a href="mailto:admin@apis-hub.cloud">admin@apis-hub.cloud</a>.</p>
@endsection
