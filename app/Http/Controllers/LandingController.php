<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeAlphaLead;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LandingController extends Controller
{
    /**
     * Show the landing page with obfuscated portal links.
     */
    public function index(Request $request, $locale = null)
    {
        $host = $request->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.network_domain', 'apis-hub.cloud');

        // 1. Detectamos si hay un subdominio (que no sea 'www' ni el dominio principal)
        if ($host !== $mainDomain && $host !== "www.{$mainDomain}") {
            $subdomain = explode('.', $host)[0];
            
            // 2. Verificamos si es un proyecto válido en la DB
            $projectExists = \App\Models\Project::where('subdomain', $subdomain)->exists();

            if (!$projectExists) {
                return redirect()->away("https://{$mainDomain}");
            }
        }

        // Enforce locale based on route for SEO purposes
        if ($locale === 'es') {
            app()->setLocale('es');
            session()->put('locale', 'es');
        } else {
            app()->setLocale('en');
            // We do not overwrite session if it's en, or we can, 
            // but for SEO strictness / is always en
            session()->put('locale', 'en');
        }

        $gtmId = config('services.gtm.id');
        
        return view('welcome', [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode(config('services.docs.url', 'https://docs.apis-hub.cloud')),
            ],
            'gtmId' => ($gtmId && $gtmId !== 'GTM-XXXXXXX') ? $gtmId : null,
        ]);
    }

    /**
     * Show the plans and features informational page.
     */
    public function plans(Request $request, $locale = null)
    {
        $host = $request->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.network_domain', 'apis-hub.cloud');

        if ($host !== $mainDomain && $host !== "www.{$mainDomain}") {
            $subdomain = explode('.', $host)[0];
            $projectExists = \App\Models\Project::where('subdomain', $subdomain)->exists();
            if (!$projectExists) {
                return redirect()->away("https://{$mainDomain}");
            }
        }

        if ($locale === 'es') {
            app()->setLocale('es');
            session()->put('locale', 'es');
        } else {
            app()->setLocale('en');
            session()->put('locale', 'en');
        }

        $gtmId = config('services.gtm.id');

        return view('plans', [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode(config('services.docs.url', 'https://docs.apis-hub.cloud')),
            ],
            'gtmId' => ($gtmId && $gtmId !== 'GTM-XXXXXXX') ? $gtmId : null,
        ]);
    }

    /**
     * Collect interest for the alpha launch.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $lead = Lead::where('email', $request->email)->first();

        if ($lead && $lead->status === 'alpha_waitlist') {
            return back()->with('error', 'You are already on the APIs Hub Alpha waitlist.');
        }

        $lead = Lead::updateOrCreate(
            ['email' => $request->email],
            [
                'source' => 'landing_page_launch',
                'status' => 'alpha_waitlist',
            ]
        );

        // Send Welcome Email via Zoho (Synchronous for now, or via Queue if configured)
        Mail::to($lead->email)->send(new WelcomeAlphaLead($lead));

        return back()->with('success', 'Success! You are now on the APIs Hub Alpha waitlist.');
    }

    /**
     * Unsubscribe a lead from the mailing list.
     */
    public function unsubscribe(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired unsubscribe link.');
        }

        $lead = Lead::where('email', $request->email)->first();

        if ($lead) {
            $lead->update(['status' => 'unsubscribed']);
        }

        return view('welcome', [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode(config('services.docs.url', 'https://docs.apis-hub.cloud')),
            ],
            'gtmId' => config('services.gtm.id'),
            'unsubscribe_message' => 'You have been successfully unsubscribed from the APIs Hub Alpha waitlist.'
        ]);
    }

    /**
     * Show the architecture overview page.
     */
    public function architecture(Request $request, $locale = null)
    {
        $this->resolveLocale($request, $locale);
        return view('landing.architecture', $this->baseViewData());
    }

    /**
     * Show the agency client reporting solution page.
     */
    public function agencyReporting(Request $request, $locale = null)
    {
        $this->resolveLocale($request, $locale);
        return view('landing.agency-reporting', $this->baseViewData());
    }

    /**
     * Show the cross-channel metric normalization solution page.
     */
    public function crossChannelNormalization(Request $request, $locale = null)
    {
        $this->resolveLocale($request, $locale);
        return view('landing.cross-channel-normalization', $this->baseViewData());
    }

    /**
     * Show the Blog feed / index page.
     */
     public function blogIndex(Request $request, $locale = null)
     {
         $this->resolveLocale($request, $locale);
         $isEs = app()->getLocale() === 'es';

         $posts = [
             [
                 'slug' => $isEs ? 'solucion-error-cuota-looker-studio-gsc' : 'fix-gsc-looker-quota-limits',
                 'url' => $isEs ? route('landing.blog.looker-quota.es') : route('landing.blog.looker-quota'),
                 'title' => $isEs
                     ? 'Cómo solucionar definitivamente el error de cuota de Google Search Console en Looker Studio'
                     : 'How to Permanently Fix Looker Studio Google Search Console Quota Errors',
                 'excerpt' => $isEs
                     ? 'Si administras paneles de Looker Studio conectados directamente a Google Search Console o GA4, probablemente te has encontrado con el error "Resource has been exhausted". Aquí explicamos el fallo arquitectónico y cómo resolverlo de raíz.'
                     : 'If you manage Looker Studio reports connected directly to Google Search Console or GA4, you have likely encountered "Resource has been exhausted" quota limit errors. Here is why direct connectors fail and how to fix them.',
                 'category' => $isEs ? 'Guía Técnica' : 'Engineering Guide',
                 'reading_time' => $isEs ? '5 min de lectura' : '5 min read',
                 'date' => '2026-10-27',
                 'image' => asset('images/seo/blog/fix-looker-studio-limits.webp'),
                 'tags' => ['Looker Studio', 'Google Search Console', 'GA4', 'API Quotas'],
             ],
         ];

         return view('landing.blog-index', array_merge($this->baseViewData(), [
             'posts' => $posts,
         ]));
     }

    /**
     * Show the Supported Channels & Providers Matrix page.
     */
    public function supportedChannels(Request $request, $locale = null)
    {
        $this->resolveLocale($request, $locale);
        $isEs = app()->getLocale() === 'es';

        $supported = [
            [
                'provider' => 'Google',
                'description' => $isEs ? 'Ecosistema de búsqueda, analítica y publicidad de Google.' : 'Google search, analytics, and advertising ecosystem.',
                'channels' => [
                    ['name' => 'Search Console', 'badge' => 'Active', 'metrics' => $isEs ? 'Clics, impresiones, CTR, posición media' : 'Clicks, impressions, CTR, average position'],
                    ['name' => 'Ads', 'badge' => 'Active', 'metrics' => $isEs ? 'Gasto, impresiones, conversiones, CPC, ROAS' : 'Spend, impressions, conversions, CPC, ROAS'],
                    ['name' => 'Analytics (GA4)', 'badge' => 'Active', 'metrics' => $isEs ? 'Sesiones, usuarios, eventos clave, ingresos' : 'Sessions, users, key events, revenue'],
                ],
            ],
            [
                'provider' => 'Meta',
                'description' => $isEs ? 'Plataforma publicitaria y presencia social en Facebook e Instagram.' : 'Facebook & Instagram advertising and organic social presence.',
                'channels' => [
                    ['name' => 'Ads', 'badge' => 'Active', 'metrics' => $isEs ? 'Gasto, alcance, clics salientes, compras, ROAS' : 'Spend, reach, outbound clicks, purchases, ROAS'],
                    ['name' => $isEs ? 'Orgánico (Facebook / Instagram)' : 'Organic (FB Page / Instagram)', 'badge' => 'Active', 'metrics' => $isEs ? 'Engagement, impresiones orgánicas, seguidores' : 'Engagement, organic impressions, followers'],
                ],
            ],
            [
                'provider' => 'Mailchimp',
                'description' => $isEs ? 'Campañas de email marketing, automatizaciones y métricas de audiencia.' : 'Email marketing campaigns, automations, and audience telemetry.',
                'channels' => [
                    ['name' => 'Campaigns & Audiences', 'badge' => 'Active', 'metrics' => $isEs ? 'Tasa de apertura, clics, rebotes, ingresos atribuidos' : 'Open rates, click rates, bounces, attributed revenue'],
                ],
            ],
        ];

        $testing = [
            [
                'provider' => 'Shopify',
                'status' => $isEs ? 'Fase de Pruebas' : 'Testing Phase',
                'description' => $isEs ? 'Órdenes de e-commerce, productos, valor medio de pedido (AOV) y clientes.' : 'E-commerce orders, line items, average order value (AOV), and customer segments.',
            ],
            [
                'provider' => 'Klaviyo',
                'status' => $isEs ? 'Fase de Pruebas' : 'Testing Phase',
                'description' => $isEs ? 'Métricas de flujo, ingresos atribuidos por email/SMS y conversión de segmentos.' : 'Flow analytics, attributed email/SMS revenue, and segment conversion.',
            ],
            [
                'provider' => 'Meta (Leads)',
                'status' => $isEs ? 'Fase de Pruebas' : 'Testing Phase',
                'description' => $isEs ? 'Ingesta instantánea de formularios instantáneos de captación de leads en Meta.' : 'Instant form lead capture telemetry and webhook ingestion.',
            ],
        ];

        return view('landing.supported-channels', array_merge($this->baseViewData(), [
            'supported' => $supported,
            'testing' => $testing,
        ]));
    }

    /**
     * Show the FAQ page.
     */
    public function faq(Request $request, $locale = null)
    {
        $this->resolveLocale($request, $locale);
        $isEs = app()->getLocale() === 'es';

        $faqs = [
            [
                'question' => $isEs 
                    ? '¿Por qué APIs Hub evita los errores de cuota de Looker Studio y conectores directos?' 
                    : 'Why does APIs Hub eliminate Looker Studio and live connector quota limits?',
                'answer' => $isEs
                    ? 'Los conectores tradicionales consultan las APIs de Google o Meta en vivo cada vez que un usuario abre un panel. APIs Hub desacopla la extracción: sincroniza tus datos periódicamente en un cluster local aislado. Looker Studio y tus dashboards consultan el cluster local en 10-20 ms sin consumir la cuota de la plataforma de origen.'
                    : 'Traditional connectors query Google or Meta APIs on-demand every time a user loads or filters a report. APIs Hub decouples data ingestion: automated background workers sync metrics into an isolated local cluster. Looker Studio and dashboards query this local cluster in 10-20ms with zero origin API consumption.',
            ],
            [
                'question' => $isEs 
                    ? '¿Qué canales y plataformas publicitarias están soportadas actualmente?' 
                    : 'Which advertising and marketing channels are currently supported?',
                'answer' => $isEs
                    ? 'Actualmente soportamos de forma activa Google (Search Console, Ads, Analytics GA4), Meta (Ads y presencia orgánica en Facebook/Instagram) y Mailchimp. En fase de pruebas avanzada contamos con Shopify, Klaviyo y Meta Leads.'
                    : 'We actively support Google (Search Console, Ads, Analytics GA4), Meta (Ads and Organic FB/Instagram), and Mailchimp. In active testing phase, we have Shopify, Klaviyo, and Meta Leads.',
            ],
            [
                'question' => $isEs 
                    ? '¿Cómo funciona el aislamiento de datos por cliente para agencias?' 
                    : 'How does client data isolation work for marketing agencies?',
                'answer' => $isEs
                    ? 'Cada cliente de tu agencia opera dentro de su propio contenedor y base de datos independiente. Las credenciales de acceso, perfiles de facturación y consultas analíticas de una marca están 100% aisladas y nunca se mezclan ni ralentizan los reportes de otros clientes.'
                    : 'Each agency client portfolio operates inside its own isolated database container. API tokens, billing profiles, and heavy analytical queries for one brand are 100% segregated and never degrade or expose another client\'s data.',
            ],
            [
                'question' => $isEs 
                    ? '¿Qué es la normalización de métricas cross-channel?' 
                    : 'What is cross-channel metric normalization?',
                'answer' => $isEs
                    ? 'Cada plataforma nombra las métricas de forma distinta (ej: link_clicks vs sessions vs clicks). APIs Hub mapea todas las fuentes a un diccionario canónico unificado, permitiendo calcular métricas reales como Blended ROAS, blended CPA y MER consistente sin hojas de cálculo intermedias.'
                    : 'Ad networks define metrics inconsistently (e.g. Meta link_clicks vs GA4 sessions vs Google Ads clicks). APIs Hub normalizes all sources into a unified canonical schema, allowing teams to calculate genuine Blended ROAS, blended CPA, and uniform MER without manual spreadsheet gymnastics.',
            ],
            [
                'question' => $isEs 
                    ? '¿Puedo conectar mis propios asistentes de IA o herramientas externas?' 
                    : 'Can I connect external AI assistants or custom BI tools?',
                'answer' => $isEs
                    ? 'Sí. Disponemos de una API REST documentada bajo estándar OpenAPI 3.1 y conectores nativos de Model Context Protocol (MCP) para que agentes como Cursor, Claude, Antigravity o scripts internos puedan consultar tus datos analíticos con permisos granulares.'
                    : 'Yes. We provide a fully typed OpenAPI 3.1 REST API along with native Model Context Protocol (MCP) connectors, allowing LLM agents (Cursor, Claude, Antigravity) and custom internal pipelines to query analytical metrics securely.',
            ],
            [
                'question' => $isEs 
                    ? '¿Se requiere tarjeta de crédito para la versión beta?' 
                    : 'Is a credit card required during the beta?',
                'answer' => $isEs
                    ? 'No. Puedes registrarte y desplegar tu primer cluster de datos de forma gratuita sin ingresar datos de pago durante el período beta.'
                    : 'No. You can provision your first data cluster for free during our public beta without entering payment information.',
            ],
        ];

        return view('landing.faq', array_merge($this->baseViewData(), [
            'faqs' => $faqs,
        ]));
    }

    /**
     * Show the problem-solving guide for Looker Studio quota limits.
     */
    public function fixLookerQuota(Request $request, $locale = null)
    {
        $this->resolveLocale($request, $locale);
        return view('landing.fix-looker-quota', $this->baseViewData());
    }

    protected function resolveLocale(Request $request, ?string $locale): void
    {
        if ($locale === 'es' || $request->is('es/*')) {
            app()->setLocale('es');
            session()->put('locale', 'es');
        } else {
            app()->setLocale('en');
            session()->put('locale', 'en');
        }
    }

    protected function baseViewData(): array
    {
        $gtmId = config('services.gtm.id');
        return [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode(config('services.docs.url', 'https://docs.apis-hub.cloud')),
            ],
            'gtmId' => ($gtmId && $gtmId !== 'GTM-XXXXXXX') ? $gtmId : null,
        ];
    }
}
