@if(request()->routeIs('filament.app.auth.login'))
    <meta name="description" content="Log in to APIs Hub to access your unified marketing dashboard. Connect Meta, Google, Shopify, and Klaviyo to visualize your data.">
    <link rel="canonical" href="{{ url()->current() }}">
    
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Login | APIs Hub">
    <meta property="og:description" content="Log in to APIs Hub to access your unified marketing dashboard. Connect Meta, Google, Shopify, and Klaviyo to visualize your data.">
    
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="Login | APIs Hub">
    <meta property="twitter:description" content="Log in to APIs Hub to access your unified marketing dashboard. Connect Meta, Google, Shopify, and Klaviyo to visualize your data.">

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebPage",
      "name": "Login | APIs Hub",
      "description": "Log in to APIs Hub to access your unified marketing dashboard.",
      "url": "{{ url()->current() }}"
    }
    </script>
@elseif(request()->routeIs('filament.app.auth.register'))
    <meta name="description" content="Create an APIs Hub account to instantly aggregate your advertising, social, and ecommerce metrics into high-performance dashboards.">
    <link rel="canonical" href="{{ url()->current() }}">
    
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Register | APIs Hub">
    <meta property="og:description" content="Create an APIs Hub account to instantly aggregate your advertising, social, and ecommerce metrics into high-performance dashboards.">
    
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="Register | APIs Hub">
    <meta property="twitter:description" content="Create an APIs Hub account to instantly aggregate your advertising, social, and ecommerce metrics into high-performance dashboards.">

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebPage",
      "name": "Register | APIs Hub",
      "description": "Create an APIs Hub account to instantly aggregate your advertising, social, and ecommerce metrics into high-performance dashboards.",
      "url": "{{ url()->current() }}"
    }
    </script>
@endif
