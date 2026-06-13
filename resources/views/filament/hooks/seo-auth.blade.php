@if(request()->routeIs('filament.app.auth.login'))
    <meta name="description" content="Log in to APIs Hub to access your unified marketing dashboard. Connect Meta, Google, Shopify, and Klaviyo to visualize your data.">
    <meta property="og:title" content="Login | APIs Hub">
    <meta property="og:description" content="Log in to APIs Hub to access your unified marketing dashboard. Connect Meta, Google, Shopify, and Klaviyo to visualize your data.">
    <link rel="canonical" href="{{ url()->current() }}">
@elseif(request()->routeIs('filament.app.auth.register'))
    <meta name="description" content="Create an APIs Hub account to instantly aggregate your advertising, social, and ecommerce metrics into high-performance dashboards.">
    <meta property="og:title" content="Register | APIs Hub">
    <meta property="og:description" content="Create an APIs Hub account to instantly aggregate your advertising, social, and ecommerce metrics into high-performance dashboards.">
    <link rel="canonical" href="{{ url()->current() }}">
@endif
