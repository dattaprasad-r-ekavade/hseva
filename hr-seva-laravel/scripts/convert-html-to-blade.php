#!/usr/bin/env php
<?php
/**
 * Converts static HTML/PHP portal pages into Blade content partials + PageRegistry.
 * Run from hr-seva-laravel/: php scripts/convert-html-to-blade.php
 */

declare(strict_types=1);

$base = dirname(__DIR__);
$public = $base.'/public';
$views = $base.'/resources/views/pages';
$contentDir = $views.'/content';
$topbarDir = $views.'/topbars';

@mkdir($contentDir, 0777, true);
@mkdir($topbarDir, 0777, true);
@mkdir($base.'/resources/views/auth', 0777, true);

/** Shared content keys: canonical client file => [client page, super-admin page|null, content slug] */
$shared = [
    'module' => ['client-module.html', 'super-admin-module.html'],
    'employee-master' => ['client-employee-master.html', 'super-admin-employee-master.html'],
    'employee-profile' => ['employee-profile.html', 'employee-profile.html'],
    'payroll-calc' => ['client-payroll-calc.html', 'super-admin-payroll-calc.html'],
    'payslips' => ['client-payslips.html', 'super-admin-payslips.html'],
    'compliance-calendar' => ['client-compliance-calendar.html', 'super-admin-compliance-calendar.html'],
    'attendance' => ['client-attendance.html', 'super-admin-attendance.html'],
    'shift-roster' => ['client-shift-roster.html', 'super-admin-shift-roster.html'],
    'leave' => ['client-leave.html', 'super-admin-leave.html'],
    'overtime' => ['client-overtime.html', 'super-admin-overtime.html'],
    'fnf' => ['client-fnf.html', 'super-admin-fnf.html'],
    'advance-salary' => ['client-advance-salary.html', 'super-admin-advance-salary.html'],
    'loan' => ['client-loan.html', 'super-admin-loan.html'],
    'view-loan' => ['client-view-loan.html', 'super-admin-view-loan.html'],
    'gratuity' => ['client-gratuity.html', 'super-admin-gratuity.html'],
    'bonus' => ['client-bonus.html', 'super-admin-bonus.html'],
    'incentive' => ['client-incentive.html', 'super-admin-incentive.html'],
    'pf-sheet' => ['client-pf-sheet.html', 'super-admin-pf-sheet.html'],
    'pf-return' => ['client-pf-return.html', 'super-admin-pf-return.html'],
    'esic-sheet' => ['client-esic-sheet.html', 'super-admin-esic-sheet.html'],
    'esic-return' => ['client-esic-return.html', 'super-admin-esic-return.html'],
    'ecr-sheet' => ['client-ecr-sheet.html', 'super-admin-ecr-sheet.html'],
    'control' => ['client-control.html', 'super-admin-control.html'],
    'roles' => ['client-roles.html', 'super-admin-roles.html'],
    'access-control' => ['client-access-control.html', 'super-admin-access-control.html'],
    'profile' => ['client-profile.html', 'super-admin-profile.html'],
    'dashboard' => ['index.html', 'index.html'],
    'face-scan' => ['scan-attendance.php', 'scan-attendance.php'],
    'face-registration' => ['face-attendance-registration.php', 'face-attendance-registration.php'],
    'face-settings' => ['face-attendance-settings.php', 'face-attendance-settings.php'],
    'face-sheet' => ['face-attendance-sheet.php', 'face-attendance-sheet.php'],
    'face-monthly-report' => ['monthly-attendance-report.php', 'monthly-attendance-report.php'],
];

$registry = [];

function readHtmlFile(string $path): string
{
    if (! is_file($path)) {
        throw new RuntimeException("Missing: $path");
    }

    return file_get_contents($path);
}

function extractTitle(string $html): string
{
    return preg_match('/<title>(.*?)<\/title>/is', $html, $m) ? trim(html_entity_decode($m[1])) : 'HR Seva';
}

function extractBodyAttrs(string $html): array
{
    $attrs = [];
    if (preg_match('/<body\b([^>]*)>/i', $html, $m)) {
        if (preg_match('/\bdata-face-page="([^"]*)"/', $m[1], $dm)) {
            $attrs['data-face-page'] = $dm[1];
        }
        if (preg_match('/\bclass="([^"]*)"/', $m[1], $cm)) {
            $attrs['_bodyClass'] = $cm[1];
        }
    }

    return $attrs;
}

function extractStyles(string $html): array
{
    $styles = [];
    if (preg_match_all('#href="\.\./assets/css/([^"]+)"#', $html, $m)) {
        foreach ($m[1] as $css) {
            if ($css !== 'app-common.css') {
                $styles[] = $css;
            }
        }
    }

    return array_values(array_unique($styles));
}

function extractScripts(string $html): array
{
    $scripts = [];
    if (preg_match_all('#src="\.\./assets/js/([^"?]+)(?:\?[^"]*)?"#', $html, $m)) {
        foreach ($m[1] as $js) {
            if ($js !== 'app-common.js') {
                $scripts[] = $js;
            }
        }
    }

    return array_values(array_unique($scripts));
}

function extractCdnScripts(string $html): array
{
    $cdns = [];
    if (preg_match_all('#<script[^>]+src="(https://[^"]+)"#i', $html, $m)) {
        foreach ($m[1] as $url) {
            if (! str_contains($url, 'bootstrap')) {
                $cdns[] = $url;
            }
        }
    }

    return array_values(array_unique($cdns));
}

function extractPortalParts(string $html): array
{
    $content = '';
    $modals = '';

    if (preg_match('/<\/header>\s*(.*?)\s*<\/main>/is', $html, $m)) {
        $content = trim($m[1]);
    }

    if (preg_match('/<\/main>\s*(.*?)\s*<script/is', $html, $m)) {
        $modals = trim($m[1]);
        $modals = preg_replace('/<div class="offcanvas.*?<\/div>\s*<\/div>/is', '', $modals) ?? $modals;
        $modals = trim($modals);
    }

    $pageTitle = '';
    $pageSubtitle = '';
    if (preg_match('/<header class="topbar">.*?<h4[^>]*>(.*?)<\/h4>/is', $html, $m)) {
        $pageTitle = trim(strip_tags($m[1]));
    } elseif (preg_match('/<header class="topbar">.*?class="fw-semibold"[^>]*>(.*?)<\/div>/is', $html, $m)) {
        $pageTitle = trim(strip_tags($m[1]));
    }
    if (preg_match('/<header class="topbar">.*?class="text-muted[^"]*"[^>]*>(.*?)<\/div>/is', $html, $m)) {
        $pageSubtitle = trim(strip_tags($m[1]));
    } elseif (preg_match('/<header class="topbar">.*?class="small text-muted-3"[^>]*>(.*?)<\/div>/is', $html, $m)) {
        $pageSubtitle = trim(strip_tags($m[1]));
    }

    return compact('content', 'modals', 'pageTitle', 'pageSubtitle');
}

function extractAuthBody(string $html): string
{
    if (preg_match('/<body[^>]*>(.*)<script/is', $html, $m)) {
        return trim($m[1]);
    }

    return '';
}

function writeContentPartial(string $slug, string $content, string $modals): void
{
    global $contentDir;
    $path = "$contentDir/$slug.blade.php";
    $body = $content;
    if ($modals !== '') {
        $body .= "\n\n@push('modals')\n".$modals."\n@endpush\n";
    }
    file_put_contents($path, $body);
}

function makePortalEntry(string $portal, string $page, string $slug, string $html, ?string $topbarSlug = null): array
{
    $attrs = extractBodyAttrs($html);
    $parts = extractPortalParts($html);
    $entry = [
        'layout' => 'portal',
        'contentView' => "pages.content.$slug",
        'title' => extractTitle($html),
        'pageTitle' => $parts['pageTitle'] ?: extractTitle($html),
        'pageSubtitle' => $parts['pageSubtitle'],
        'styles' => extractStyles($html),
        'scripts' => extractScripts($html),
        'cdnScripts' => extractCdnScripts($html),
    ];
    if (! empty($attrs['_bodyClass'])) {
        $entry['bodyClass'] = $attrs['_bodyClass'];
    }
    if (! empty($attrs['data-face-page'])) {
        $entry['bodyAttrs'] = ['data-face-page' => $attrs['data-face-page']];
    }
    if ($topbarSlug) {
        $entry['topbarView'] = "pages.topbars.$topbarSlug";
    }

    return $entry;
}

// --- Process shared module pages (extract once from client canonical) ---
$processedSlugs = [];
foreach ($shared as $slug => [$clientPage, $saPage]) {
    $clientPath = "$public/client/$clientPage";
    if (! is_file($clientPath)) {
        echo "Skip missing client: $clientPage\n";
        continue;
    }
    $html = readHtmlFile($clientPath);
    $parts = extractPortalParts($html);
    writeContentPartial($slug, $parts['content'], $parts['modals']);
    $processedSlugs[$slug] = true;

    $registry["client/$clientPage"] = makePortalEntry('client', $clientPage, $slug, $html);
    if ($saPage && $saPage !== $clientPage) {
        $saPath = "$public/super-admin/$saPage";
        if (is_file($saPath)) {
            $saHtml = readHtmlFile($saPath);
            $registry["super-admin/$saPage"] = makePortalEntry('super-admin', $saPage, $slug, $saHtml);
        }
    } elseif ($saPage === $clientPage) {
        $saPath = "$public/super-admin/$saPage";
        if (is_file($saPath)) {
            $saHtml = readHtmlFile($saPath);
            $registry["super-admin/$saPage"] = makePortalEntry('super-admin', $saPage, $slug, $saHtml);
        }
    }
}

// --- Unique client-only pages ---
$clientOnly = [
    'client-login.html' => ['layout' => 'auth', 'slug' => 'client-login', 'style' => 'client-login.css', 'script' => 'client-login.js?v=20260507'],
    'client-logout.html' => ['layout' => 'minimal', 'slug' => 'client-logout', 'script' => 'client-logout.js'],
    'client-invoices.html' => ['redirect' => '/client/client-billing.html'],
    'client-subscriptions.html' => ['slug' => 'client-subscriptions'],
    'client-billing.html' => ['slug' => 'client-billing'],
    'client-employee-types.html' => ['slug' => 'client-employee-types'],
    'client-attendance-statuses.html' => ['slug' => 'client-attendance-statuses'],
    'my-shift-roster.html' => ['slug' => 'my-shift-roster'],
    'my-face-attendance.php' => ['slug' => 'my-face-attendance'],
];

foreach ($clientOnly as $page => $meta) {
    $key = "client/$page";
    if (! empty($meta['redirect'])) {
        $registry[$key] = ['redirect' => $meta['redirect']];
        continue;
    }
    $path = "$public/client/$page";
    if (! is_file($path)) {
        continue;
    }
    $html = readHtmlFile($path);
    if (($meta['layout'] ?? '') === 'auth') {
        $body = extractAuthBody($html);
        file_put_contents("$base/resources/views/auth/{$meta['slug']}.blade.php", $body);
        $registry[$key] = [
            'layout' => 'auth',
            'contentView' => "auth.{$meta['slug']}",
            'title' => extractTitle($html),
            'style' => $meta['style'],
            'script' => $meta['script'],
        ];
    } elseif (($meta['layout'] ?? '') === 'minimal') {
        $body = extractAuthBody($html);
        file_put_contents("$base/resources/views/auth/{$meta['slug']}.blade.php", $body);
        $registry[$key] = [
            'layout' => 'minimal',
            'contentView' => "auth.{$meta['slug']}",
            'title' => extractTitle($html),
            'script' => $meta['script'],
            'loadAppCommon' => false,
        ];
    } else {
        $slug = $meta['slug'];
        if (empty($processedSlugs[$slug])) {
            $parts = extractPortalParts($html);
            writeContentPartial($slug, $parts['content'], $parts['modals']);
        }
        $registry[$key] = makePortalEntry('client', $page, $slug, $html);
    }
}

// --- Unique super-admin-only pages ---
$saOnly = [
    'super-admin-login.html' => ['layout' => 'auth', 'slug' => 'super-admin-login', 'style' => 'super-admin-login.css', 'script' => 'super-admin-login.js'],
    'super-admin-logout.html' => ['layout' => 'minimal', 'slug' => 'super-admin-logout', 'script' => 'client-logout.js'],
    'super-admin-invoices.html' => ['redirect' => '/super-admin/super-admin-billing.html'],
    'super-admin-dashboard.html' => ['slug' => 'super-admin-dashboard'],
    'super-admin-enquiries.html' => ['slug' => 'super-admin-enquiries'],
    'super-admin-smtp-control.html' => ['slug' => 'super-admin-smtp-control'],
    'super-admin-subscriptions.html' => ['slug' => 'super-admin-subscriptions'],
    'super-admin-billing.html' => ['slug' => 'super-admin-billing'],
    'super-admin-employee-types.html' => ['slug' => 'super-admin-employee-types'],
    'super-admin-attendance-statuses.html' => ['slug' => 'super-admin-attendance-statuses'],
];

foreach ($saOnly as $page => $meta) {
    $key = "super-admin/$page";
    if (! empty($meta['redirect'])) {
        $registry[$key] = ['redirect' => $meta['redirect']];
        continue;
    }
    $path = "$public/super-admin/$page";
    if (! is_file($path)) {
        continue;
    }
    $html = readHtmlFile($path);
    if (($meta['layout'] ?? '') === 'auth') {
        $body = extractAuthBody($html);
        file_put_contents("$base/resources/views/auth/{$meta['slug']}.blade.php", $body);
        $registry[$key] = [
            'layout' => 'auth',
            'contentView' => "auth.{$meta['slug']}",
            'title' => extractTitle($html),
            'style' => $meta['style'],
            'script' => $meta['script'],
        ];
    } elseif (($meta['layout'] ?? '') === 'minimal') {
        $body = extractAuthBody($html);
        file_put_contents("$base/resources/views/auth/{$meta['slug']}.blade.php", $body);
        $registry[$key] = [
            'layout' => 'minimal',
            'contentView' => "auth.{$meta['slug']}",
            'title' => extractTitle($html),
            'script' => $meta['script'],
            'loadAppCommon' => false,
        ];
    } else {
        $slug = $meta['slug'];
        if (empty($processedSlugs[$slug])) {
            $parts = extractPortalParts($html);
            writeContentPartial($slug, $parts['content'], $parts['modals']);
        }
        $registry[$key] = makePortalEntry('super-admin', $page, $slug, $html);
    }
}

// --- Landing page ---
$landingHtml = readHtmlFile("$public/index.html");
$landingBody = '';
if (preg_match('/<body[^>]*>(.*)<\/body>/is', $landingHtml, $m)) {
    $landingBody = trim($m[1]);
}
$landingHead = '';
if (preg_match('/<head[^>]*>(.*)<\/head>/is', $landingHtml, $m)) {
    $landingHead = trim($m[1]);
}
file_put_contents("$base/resources/views/landing/index.blade.php", "@extends('layouts.landing')\n");
file_put_contents("$base/resources/views/landing/_head.blade.php", $landingHead ?? '');
file_put_contents("$base/resources/views/landing/_body.blade.php", $landingBody ?? '');
$registry['/'] = ['layout' => 'landing'];

// --- Write PageRegistry.php ---
$export = var_export($registry, true);
$php = <<<PHP
<?php

namespace App\Support;

class PageRegistry
{
    /** @var array<string, array<string, mixed>> */
    private const PAGES = $export;

    public static function get(string \$key): ?array
    {
        return self::PAGES[\$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(self::PAGES);
    }
}

PHP;
file_put_contents($base.'/app/Support/PageRegistry.php', $php);

// --- Write routes snippet ---
$routeLines = ["<?php", "", "use App\\Http\\Controllers\\PortalPageController;", "use Illuminate\\Support\\Facades\\Route;", ""];
$routeLines[] = "Route::get('/', [PortalPageController::class, 'show'])->defaults('portal', '')->defaults('page', '/');";
foreach (array_keys($registry) as $key) {
    if ($key === '/') {
        continue;
    }
    [$portal, $page] = explode('/', $key, 2);
    $routeLines[] = "Route::get('/$portal/$page', [PortalPageController::class, 'show'])->defaults('portal', '$portal')->defaults('page', '$page');";
}
file_put_contents($base.'/routes/portal.php', implode("\n", $routeLines)."\n");

echo 'Converted '.count($registry)." pages.\n";
echo "Content partials: $contentDir\n";
echo "Registry: app/Support/PageRegistry.php\n";
echo "Routes: routes/portal.php\n";
