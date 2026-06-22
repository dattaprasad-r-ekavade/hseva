<?php

namespace App\Http\Controllers;

use App\Support\PageRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PortalPageController extends Controller
{
    public function show(string $portal, string $page): View|RedirectResponse
    {
        $key = $portal === '' ? '/' : $portal.'/'.$page;
        $config = PageRegistry::get($key);

        if ($config === null) {
            abort(404);
        }

        if (! empty($config['redirect'])) {
            return redirect($config['redirect']);
        }

        $config['portal'] = $portal;
        $config['pageKey'] = $page;
        $config['navigation'] = \App\Support\NavigationBuilder::sections($portal === 'super-admin' ? 'super-admin' : 'client');

        return match ($config['layout'] ?? 'portal') {
            'auth' => view('layouts.auth', $config),
            'minimal' => view('layouts.minimal', $config),
            'landing' => view('layouts.landing'),
            default => view('layouts.portal', $config),
        };
    }
}
