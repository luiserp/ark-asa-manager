<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public const PROVIDERS = [
        'curseforge' => 'CurseForge',
        'openai' => 'OpenAI',
        'claude' => 'Claude',
        'other' => 'Other',
    ];

    public function index(): Response
    {
        return Inertia::render('integrations/Index', [
            'integrations' => Integration::orderBy('provider')
                ->get()
                ->map(fn (Integration $i) => [
                    'id' => $i->id,
                    'provider' => $i->provider,
                    'label' => $i->label,
                    'is_active' => $i->is_active,
                    'has_key' => $i->api_key !== null && $i->api_key !== '',
                    'created_at' => $i->created_at?->diffForHumans(),
                ]),
            'providers' => self::PROVIDERS,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('integrations/Create', [
            'providers' => self::PROVIDERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:1000'],
        ]);

        Integration::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Integration added.']);

        return redirect()->route('integrations.index');
    }

    public function edit(Integration $integration): Response
    {
        return Inertia::render('integrations/Edit', [
            'integration' => [
                'id' => $integration->id,
                'provider' => $integration->provider,
                'label' => $integration->label,
                'api_key' => $integration->api_key,
                'is_active' => $integration->is_active,
            ],
            'providers' => self::PROVIDERS,
        ]);
    }

    public function update(Integration $integration, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $integration->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Integration updated.']);

        return redirect()->route('integrations.index');
    }

    public function destroy(Integration $integration): RedirectResponse
    {
        $integration->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Integration removed.']);

        return redirect()->route('integrations.index');
    }
}
