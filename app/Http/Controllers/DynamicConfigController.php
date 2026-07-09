<?php

namespace App\Http\Controllers;

use App\Models\GameServerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DynamicConfigController extends Controller
{
    private const SETTINGS_SCHEMA = [
        'rates' => [
            'label' => 'Rate Multipliers',
            'fields' => [
                'XPMultiplier' => ['label' => 'XP Multiplier', 'type' => 'float', 'default' => 1.0],
                'TamingSpeedMultiplier' => ['label' => 'Taming Speed', 'type' => 'float', 'default' => 1.0],
                'HarvestAmountMultiplier' => ['label' => 'Harvest Amount', 'type' => 'float', 'default' => 1.0],
                'MatingIntervalMultiplier' => ['label' => 'Mating Interval', 'type' => 'float', 'default' => 1.0],
                'MatingSpeedMultiplier' => ['label' => 'Mating Speed', 'type' => 'float', 'default' => 1.0],
                'BabyMatureSpeedMultiplier' => ['label' => 'Baby Maturation Speed', 'type' => 'float', 'default' => 1.0],
                'EggHatchSpeedMultiplier' => ['label' => 'Egg Hatch Speed', 'type' => 'float', 'default' => 1.0],
                'BabyCuddleIntervalMultiplier' => ['label' => 'Imprint Care Interval', 'type' => 'float', 'default' => 1.0],
                'BabyImprintAmountMultiplier' => ['label' => 'Imprint Amount Per Care', 'type' => 'float', 'default' => 1.0],
                'BabyFoodConsumptionSpeedMultiplier' => ['label' => 'Baby Food Drain', 'type' => 'float', 'default' => 1.0],
                'CropGrowthSpeedMultiplier' => ['label' => 'Crop Growth Speed', 'type' => 'float', 'default' => 1.0],
                'CustomRecipeEffectivenessMultiplier' => ['label' => 'Custom Recipe Effectiveness', 'type' => 'float', 'default' => 1.0],
                'GlobalSpoilingTimeMultiplier' => ['label' => 'Spoiling Time', 'type' => 'float', 'default' => 1.0],
                'HexagonRewardMultiplier' => ['label' => 'Hexagon Rewards', 'type' => 'float', 'default' => 1.0],
                'DinoCharacterFoodDrainMultiplier' => ['label' => 'Creature Food Drain', 'type' => 'float', 'default' => 1.0],
                'StructureDamageMultiplier' => ['label' => 'Structure Damage', 'type' => 'float', 'default' => 1.0],
            ],
        ],
        'decay' => [
            'label' => 'Decay Settings',
            'fields' => [
                'bDisableDinoDecayPvE' => ['label' => 'Disable Creature Decay (PvE)', 'type' => 'bool', 'default' => false],
                'bDisableStructureDecayPvE' => ['label' => 'Disable Structure Decay (PvE)', 'type' => 'bool', 'default' => false],
                'bPvPDinoDecay' => ['label' => 'Creature Decay (PvP/ORP)', 'type' => 'bool', 'default' => false],
                'bPvPStructureDecay' => ['label' => 'Structure Decay (PvP/ORP)', 'type' => 'bool', 'default' => false],
                'PvEDinoDecayPeriodMultiplier' => ['label' => 'PvE Creature Decay Period', 'type' => 'float', 'default' => 1.0],
                'PvEStructureDecayPeriodMultiplier' => ['label' => 'PvE Structure Decay Period', 'type' => 'float', 'default' => 1.0],
            ],
        ],
        'transfers' => [
            'label' => 'Transfer Settings',
            'fields' => [
                'TributeDinoExpirationSeconds' => ['label' => 'Uploaded Creature Expiry (seconds)', 'type' => 'int', 'default' => 86400],
                'TributeItemExpirationSeconds' => ['label' => 'Uploaded Item Expiry (seconds)', 'type' => 'int', 'default' => 86400],
            ],
        ],
        'events' => [
            'label' => 'Event Colors',
            'fields' => [
                'ActiveEventColors' => ['label' => 'Event Color Palette', 'type' => 'select', 'default' => '', 'options' => [
                    '' => 'None',
                    'easter' => 'Eggcellent Adventure',
                    'FearEvolved' => 'Fear Evolved',
                    'PAX' => 'PAX Party',
                    'Summer' => 'Summer EVO',
                    'TurkeyTrial' => 'Turkey Trial',
                    'vday' => "Valentine's EVO",
                    'WinterWonderland' => 'Winter Wonderland',
                    'custom' => 'Custom Colors',
                ]],
                'DynamicColorset' => ['label' => 'Custom Color Names (comma-separated)', 'type' => 'string', 'default' => ''],
                'DynamicColorsetChanceOverride' => ['label' => 'Color Override Chance (0.0–1.0)', 'type' => 'float', 'default' => 0.25],
            ],
        ],
    ];

    public function index(Request $request, GameServerProfile $profile): InertiaResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($profile->gameServer->team_id === $team->id, 403);

        return Inertia::render('profiles/DynamicConfig', [
            'profile' => $profile->only('id', 'name', 'dynamic_config_enabled', 'dynamic_config'),
            'server' => $profile->gameServer->only('id', 'name'),
            'schema' => self::SETTINGS_SCHEMA,
            'serveUrl' => route('dynamic-config.serve', $profile),
        ]);
    }

    public function active(Request $request): InertiaResponse|RedirectResponse
    {
        $server = $request->user()->currentServer();
        $profile = $server
            ? GameServerProfile::where('game_server_id', $server->id)->active()->first()
            : null;

        if (! $profile) {
            return to_route('profiles.index');
        }

        return Inertia::render('profiles/DynamicConfig', [
            'profile' => $profile->only('id', 'name', 'dynamic_config_enabled', 'dynamic_config'),
            'server' => $profile->gameServer->only('id', 'name'),
            'schema' => self::SETTINGS_SCHEMA,
            'serveUrl' => route('dynamic-config.serve', $profile),
        ]);
    }

    public function update(Request $request, GameServerProfile $profile): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($profile->gameServer->team_id === $team->id, 403);

        $data = $request->validate([
            'dynamic_config_enabled' => ['required', 'boolean'],
            'dynamic_config' => ['nullable', 'array'],
        ]);

        $profile->update($data);

        return response()->json(['success' => true]);
    }

    /**
     * Public endpoint that serves the DynamicConfig as a plain-text INI file.
     * The ARK server fetches this URL on each autosave.
     */
    public function serve(GameServerProfile $profile): Response
    {
        $config = $profile->dynamic_config ?? [];
        $lines = [];

        foreach (self::SETTINGS_SCHEMA as $section) {
            foreach ($section['fields'] as $key => $field) {
                if (! array_key_exists($key, $config)) {
                    continue;
                }

                $value = $config[$key];

                if ($value === null || $value === '') {
                    continue;
                }

                $default = $field['default'];

                if ($field['type'] === 'bool') {
                    $boolVal = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    if ($boolVal === $default) {
                        continue;
                    }
                    $lines[] = "{$key}=".($boolVal ? 'True' : 'False');
                } elseif ($field['type'] === 'float') {
                    $floatVal = (float) $value;
                    if (abs($floatVal - (float) $default) < 0.0001) {
                        continue;
                    }
                    $lines[] = "{$key}={$floatVal}";
                } elseif ($field['type'] === 'int') {
                    $intVal = (int) $value;
                    if ($intVal === (int) $default) {
                        continue;
                    }
                    $lines[] = "{$key}={$intVal}";
                } elseif ($field['type'] === 'select') {
                    if ($value === $default) {
                        continue;
                    }
                    $lines[] = "{$key}={$value}";
                } else {
                    $lines[] = "{$key}={$value}";
                }
            }
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
