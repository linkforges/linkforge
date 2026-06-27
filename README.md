<div align="center">

<img src="public/logo.png" alt="LinkForge" width="96" height="96">

# LinkForge

**A self-hosted, AI-native URL shortener and link-management platform.**
Branded short links, a QR studio, link-in-bio pages, deep analytics, and monetization, all on hosting you own.

[![CI](https://github.com/sanmaxdev/linkforge/actions/workflows/ci.yml/badge.svg)](https://github.com/sanmaxdev/linkforge/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](composer.json)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20.svg)](https://laravel.com)

### [Try the live demo at linkforge.store](https://linkforge.store)

</div>

---

LinkForge is a complete link-management platform that you run on your own server. There is no license check, no phone-home, and no subscription. It is free and open source under the MIT License, with clean, readable code (no ionCube, no obfuscation) that you can audit, extend, and ship.

<div align="center">
  <img src="public/docs/images/dashboard.png" alt="LinkForge dashboard" width="820">
</div>

## Features

### Links and redirects

- **Short links:** custom aliases, expiry, click limits, password protection, a UTM builder, and bulk import.
- **Branded domains:** multi-tenant custom domains with one-click verification and per-domain analytics.
- **Targeting and rotation:** geo, device, OS, language, and time rules, plus weighted A/B rotation per link.

### Analytics and AI

- **Deep analytics:** click trends, an interactive geo map (built-in GeoIP), and device, referrer, and UTM breakdowns.
- **AI layer (optional):** alias suggestions, natural-language analytics, title and bio writers, and weekly insights. Bring your own Anthropic or OpenRouter key; with no key set, every AI surface stays hidden.

### Engagement

- **QR code studio:** styled, scannable codes with custom colors, shapes, logos, and saved templates.
- **Link-in-bio pages:** a builder with themes, content blocks, lead capture, and a live mobile preview.

### Monetization

- **Ads and retargeting:** interstitial ads, link-level ad codes, and built-in retargeting pixels (11 providers).
- **Billing and plans:** Stripe, PayPal, CoinPayments, Crypto.com, or an offline gateway, with plan-gated features and credits.
- **Affiliate program:** referral tracking, commissions, and payouts.

### Platform

- **Developer API and webhooks:** a token-scoped REST API with HMAC-signed webhook deliveries.
- **Admin panel:** user management, content moderation, branding and appearance, localization, broadcasts, and a built-in updater.
- **Safety:** local and threat-feed URL screening (URLhaus, VirusTotal, and Web Risk), disposable-email blocking, and an optional Turnstile CAPTCHA.
- **Content tools:** a blog, a help center, CMS pages, sitemap and robots, and cookie consent.
- **Localization and theming:** multiple languages, light and dark themes, white-label branding, and a built-in demo mode.

## Tech stack

Laravel 12, PHP 8.2+, MySQL or MariaDB, Tailwind CSS 4, Vite, and PHPUnit. No paid services are required.

## Requirements

- PHP **8.2+** with the standard Laravel extensions (`pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `gd`, `zip`, `sodium`).
- MySQL 5.7+ or 8, or MariaDB 10.3+.
- Composer, and Node.js 18+ (only to build front-end assets).

## Installation

### Production (web installer, no shell needed)

1. Build the assets and install production dependencies, or download a packaged release from [Releases](https://github.com/sanmaxdev/linkforge/releases).
2. Upload the files to your web root and point the document root at `public/`.
3. Create an empty MySQL database.
4. Visit your domain. The **first-run installer** walks you through a requirements check, the database and `.env` setup (it runs the migrations), and creating your admin account.
5. Add the scheduler cron so background jobs run:
   ```cron
   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
   ```

### Local development

```bash
git clone https://github.com/sanmaxdev/linkforge.git
cd linkforge
composer install
npm install
cp .env.example .env
php artisan key:generate
# set DB_* in .env, then:
php artisan migrate --seed
npm run build      # or: npm run dev
php artisan serve
```

## Configuration

Everything optional is config-gated and off by default. The app runs fully with just the database configured. Set the extras you want in `.env` (all documented in `.env.example`):

| Area | Keys |
|---|---|
| AI | `AI_PROVIDER`, `ANTHROPIC_API_KEY` or `OPENROUTER_API_KEY`, `AI_MODEL` |
| Payments | `STRIPE_SECRET`, `PAYPAL_*`, `COINPAYMENTS_*`, `CRYPTOCOM_*` |
| Geo (analytics) | `GEOLITE_DB_PATH`, or use the bundled DB-IP country database or a Cloudflare header |
| Safety | `SAFETY_URLHAUS`, `VIRUSTOTAL_API_KEY`, `WEBRISK_API_KEY`, `TURNSTILE_*` |
| Social login | Google, GitHub, and Facebook OAuth credentials (under Admin → Settings) |

Most settings are also editable from **Admin → Settings** at runtime.

## Documentation

Full operator and user documentation ships with the app and is served at **`/docs`** on any install (offline-capable, also at `public/docs/index.html`).

## Updating

Use **Admin → Updates** to upload and apply a release package. For a Git checkout, run `git pull` followed by `composer install`, `php artisan migrate`, and `npm run build`.

## Demo

Try the live demo at **[linkforge.store](https://linkforge.store)**.

LinkForge also includes a built-in demo mode that turns an install into a safe, read-only public showcase (one-click logins, hourly reset, no real email). Run it on a **separate** install only; see [DEMO.md](DEMO.md).

## Contributing

Contributions of all kinds are welcome: code, translations, docs, bug reports, and ideas.

**New here?** Browse the [good first issues](https://github.com/sanmaxdev/linkforge/labels/good%20first%20issue) for small, well-scoped tasks to get you started. Areas where help is especially welcome:

- **Translations:** LinkForge ships English and Spanish (`lang/*.json`) and is built for i18n. Adding a new language, or wrapping more of the UI for translation, is a great first PR.
- **Tests:** broaden coverage of the link, analytics, and admin flows.
- **Docs:** clarify the setup guides and in-app help.
- **Features:** see the [help wanted](https://github.com/sanmaxdev/linkforge/labels/help%20wanted) label and the open issues.

Read [CONTRIBUTING.md](CONTRIBUTING.md) for the dev setup, coding standards (Laravel Pint), and the PR process. Every PR runs the test suite and linter via CI and gets a maintainer review before merge. By participating, you agree to our [Code of Conduct](CODE_OF_CONDUCT.md).

## Security

Found a vulnerability? Please do not open a public issue. See [SECURITY.md](SECURITY.md) for private reporting.

## License

LinkForge is open-source software licensed under the [MIT License](LICENSE). Bundled third-party software and data are credited in [THIRD_PARTY_LICENSES.md](THIRD_PARTY_LICENSES.md).
