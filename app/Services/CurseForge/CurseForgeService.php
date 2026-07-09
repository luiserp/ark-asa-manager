<?php

namespace App\Services\CurseForge;

use App\Models\Integration;
use App\Models\Team;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurseForgeService
{
    private const BASE_URL = 'https://api.curseforge.com';

    private const GAME_ID = 83374;

    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly string $apiKey,
    ) {}

    public static function make(?Team $team = null): static
    {
        $query = Integration::where('provider', 'curseforge')
            ->where('is_active', true);

        if ($team) {
            $query->where('team_id', $team->id);
        }

        $integration = $query->first();

        if (! $integration || empty($integration->api_key)) {
            throw new \RuntimeException('No active CurseForge integration found. Please add one in Settings > Integrations.');
        }

        return new static(apiKey: $integration->api_key);
    }

    /**
     * Search for mods on CurseForge.
     *
     * @param  array{searchFilter?: string, index?: int, pageSize?: int, sortField?: int, sortOrder?: string}  $params
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, int>}
     */
    public function searchMods(array $params = []): array
    {
        $query = array_merge([
            'gameId' => self::GAME_ID,
            'pageSize' => 20,
            'sortField' => 2, // Popularity
            'sortOrder' => 'desc',
        ], $params);

        $response = $this->client()->get('/v1/mods/search', $query);

        $response->throw();

        $json = $response->json();

        $data = collect($json['data'] ?? [])->map(function (array $mod) {
            $transformed = $this->transformMod($mod);
            Cache::put("curseforge_mod:{$mod['id']}", $transformed, self::CACHE_TTL);

            return $transformed;
        })->all();

        return [
            'data' => $data,
            'pagination' => $json['pagination'] ?? [],
        ];
    }

    /**
     * Get a single mod by ID (cached).
     *
     * @return array<string, mixed>
     */
    public function getMod(int $modId): array
    {
        return Cache::remember("curseforge_mod:{$modId}", self::CACHE_TTL, function () use ($modId) {
            $response = $this->client()->get("/v1/mods/{$modId}");

            $response->throw();

            return $this->transformMod($response->json('data'));
        });
    }

    /**
     * Get multiple mods by their IDs (cached individually, batch-fetches uncached).
     *
     * @param  array<int, int>  $modIds
     * @return array<int, array<string, mixed>> Keyed by mod ID
     */
    public function getModsByIds(array $modIds): array
    {
        if (empty($modIds)) {
            return [];
        }

        $results = [];
        $uncached = [];

        foreach ($modIds as $id) {
            $cached = Cache::get("curseforge_mod:{$id}");
            if ($cached !== null) {
                $results[$id] = $cached;
            } else {
                $uncached[] = $id;
            }
        }

        if (! empty($uncached)) {
            $response = $this->client()->post('/v1/mods', [
                'modIds' => $uncached,
            ]);

            $response->throw();

            foreach ($response->json('data') ?? [] as $mod) {
                $transformed = $this->transformMod($mod);
                Cache::put("curseforge_mod:{$mod['id']}", $transformed, self::CACHE_TTL);
                $results[$mod['id']] = $transformed;
            }
        }

        return $results;
    }

    /**
     * Transform raw CurseForge mod data into a consistent shape.
     *
     * @param  array<string, mixed>  $mod
     * @return array<string, mixed>
     */
    private function transformMod(array $mod): array
    {
        $logo = $mod['logo'] ?? null;
        $screenshots = collect($mod['screenshots'] ?? [])
            ->map(fn (array $s) => [
                'id' => $s['id'],
                'title' => $s['title'] ?? '',
                'url' => $s['url'] ?? '',
                'thumbnailUrl' => $s['thumbnailUrl'] ?? '',
            ])
            ->all();

        return [
            'id' => $mod['id'],
            'name' => $mod['name'] ?? '',
            'slug' => $mod['slug'] ?? '',
            'summary' => $mod['summary'] ?? '',
            'downloadCount' => $mod['downloadCount'] ?? 0,
            'thumbUrl' => $logo['thumbnailUrl'] ?? $logo['url'] ?? null,
            'logoUrl' => $logo['url'] ?? null,
            'screenshots' => $screenshots,
            'authors' => collect($mod['authors'] ?? [])->pluck('name')->all(),
            'dateReleased' => $mod['dateReleased'] ?? null,
            'links' => [
                'websiteUrl' => $mod['links']['websiteUrl'] ?? null,
            ],
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withHeaders(['x-api-key' => $this->apiKey])
            ->acceptJson()
            ->timeout(15);
    }
}
