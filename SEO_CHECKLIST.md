# APIs Hub Facade - SEO & Performance Checklist

This document serves as the standard operating procedure (SOP) for launching new public-facing pages or marketing routes within the APIs Hub platform. Before deploying any trackable page, ensure it meets all the following criteria.

## 1. Document Structure & Meta Tags
- [ ] **HTML Doctype**: The document begins exactly with `<!DOCTYPE html>`.
- [ ] **Language Attribute**: The `<html>` tag contains the correct locale (e.g., `<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">`).
- [ ] **Canonical URL**: `<link rel="canonical" href="{{ url()->current() }}" />` is present to prevent duplicate content indexing (Indicar versión canónica de la página).
- [ ] **SEO Meta Tags**: `<title>` and `<meta name="description">` are defined and optimized.
- [ ] **Social Meta Tags**: Open Graph (`og:title`, `og:description`, `og:image`, `og:url`) and Twitter Cards (`twitter:card`, `twitter:title`) are present.
- [ ] **Content Hierarchy**: The page uses exactly one `<h1>` tag representing the main topic, with semantic `<h2>` and `<h3>` tags structuring the rest of the document.

## 2. Localization & Regional Options
- [ ] **Hreflang Tags**: If the page supports multiple languages, `<link rel="alternate" hreflang="...">` tags are present for all supported locales (e.g., `en`, `es`, and `x-default`).
- [ ] **Language Toggles**: If applicable, UI toggles for language switching are accessible and update the URL correctly.

## 3. Structured Data (JSON-LD)
- [ ] **Entity Definition**: The page includes a `<script type="application/ld+json">` block containing relevant Schema.org structured data.
- [ ] **Graph Usage**: Use the `@graph` structure to link related entities (e.g., `WebSite`, `Organization` with logo, `SoftwareApplication`, or `WebPage`).

## 4. External Scripts & Tracking
- [ ] **Google Tag Manager**: `gtm.js` is properly loaded. For Filament portals, this must be injected via `panels::head.start` and `panels::body.start` render hooks.
- [ ] **JS Async/Defer**: All external third-party scripts (like Google ReCaptcha) utilize `async defer` attributes to prevent render blocking.
- [ ] **Preconnects**: Critical external domains have established connections early in the `<head>`:
  - `<link rel="preconnect" href="https://fonts.googleapis.com">`
  - `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>`
  - `<link rel="preconnect" href="https://www.googletagmanager.com">`

## 5. Performance & Caching
- [ ] **Static Asset Caching**: The deployment infrastructure (e.g., Caddyfile) enforces aggressive cache lifetimes for static files (`.js`, `.css`, `.webp`, `.woff2`) using `Cache-Control: public, max-age=31536000, immutable`.
- [ ] **Unused JS Removal**: Heavy scripts (like `filament-charts.js`) are conditionally loaded *only* on the routes that require them (e.g., restricted from loading on Auth/Login pages).
- [ ] **Image Optimization**: 
  - Above-the-fold images (like logos) use `fetchpriority="high" decoding="async"` and do NOT use lazy loading.
  - Offscreen/below-the-fold images use `loading="lazy"`.
- [ ] **Font Display**: Web font requests append `&display=swap` to ensure text remains visible during font load.

## 6. Accessibility (a11y)
- [ ] **ARIA Labels**: All icon-only buttons (like Dark Mode toggles) and inputs without visible labels utilize descriptive `aria-label="..."` attributes.
- [ ] **Alt Text**: All `<img>` elements contain descriptive `alt` attributes.
- [ ] **Contrast**: Text and background colors meet WCAG contrast requirements (especially in both light and dark modes).

## 7. Indexing Rules
- [ ] **Robots.txt**: The route is explicitly allowed in `public/robots.txt` (following our deny-by-default strategy).
- [ ] **Sitemap.xml**: The route (and its localized variants) are registered in `public/sitemap.xml` with appropriate `<priority>` and `<changefreq>`.
