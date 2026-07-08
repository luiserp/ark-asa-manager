# ARK GameUserSettings.ini Reference

Source: https://ark.wiki.gg/wiki/Server_configuration

## [ServerSettings]

### Core Server Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `ActiveMods` | — | string | Comma-separated mod IDs (ASE; ASA uses `-mods`) |
| `ActiveMapMod` | — | mod ID | Active mod map |
| `ActiveTotalConversion` | — | mod ID | Total conversion mod |
| `AdminLogging` | False | bool | Log admin commands to chat |
| `BanListURL` | official | URL | Global ban list URL |
| `ServerPassword` | — | string | Join password (no quotes) |
| `ServerAdminPassword` | — | string | Admin password (no quotes) |
| `serverPVE` | False | bool | Enable PvE mode |
| `ServerCrosshair` | True | bool | Show crosshair |
| `ServerForceNoHUD` | False | bool | Disable HUD for non-tribe NPCs |
| `ServerHardcore` | False | bool | Hardcore mode (reset to level 1 on death) |
| `SpectatorPassword` | — | string | Spectator mode password |
| `RCONEnabled` | False | bool | Enable RCON |
| `RCONPort` | 27020 | int | RCON TCP port |
| `RCONServerGameLogBuffer` | 600 | float | RCON log buffer lines |

### Difficulty & Rates

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `DifficultyOffset` | 1.0 | float | Difficulty level |
| `OverrideOfficialDifficulty` | 0.0 | float | Override difficulty (5.0 = max 150 wild) |
| `XPMultiplier` | 1.0 | float | Global XP scaling |
| `TamingSpeedMultiplier` | 1.0 | float | Taming speed |
| `HarvestAmountMultiplier` | 1.0 | float | Harvest yield |
| `HarvestHealthMultiplier` | 1.0 | float | Harvestable object health |
| `ResourcesRespawnPeriodMultiplier` | 1.0 | float | Resource respawn rate (lower = faster) |
| `ItemStackSizeMultiplier` | 1.0 | float | Global stack size multiplier |

### Day/Night Cycle

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `DayCycleSpeedScale` | 1.0 | float | Overall cycle speed |
| `DayTimeSpeedScale` | 1.0 | float | Daytime duration (lower = longer) |
| `NightTimeSpeedScale` | 1.0 | float | Nighttime duration (lower = longer) |

### Player Stats

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `PlayerDamageMultiplier` | 1.0 | float | Player attack damage |
| `PlayerResistanceMultiplier` | 1.0 | float | Player damage resistance (higher = more damage taken) |
| `PlayerCharacterFoodDrainMultiplier` | 1.0 | float | Food consumption speed |
| `PlayerCharacterWaterDrainMultiplier` | 1.0 | float | Water consumption speed |
| `PlayerCharacterStaminaDrainMultiplier` | 1.0 | float | Stamina drain speed |
| `PlayerCharacterHealthRecoveryMultiplier` | 1.0 | float | Health recovery speed |

### Creature Stats

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `DinoCountMultiplier` | 1.0 | float | Wild creature spawn count |
| `DinoDamageMultiplier` | 1.0 | float | Wild creature damage |
| `DinoResistanceMultiplier` | 1.0 | float | Wild creature resistance |
| `TamedDinoDamageMultiplier` | 1.0 | float | Tamed creature damage |
| `TamedDinoResistanceMultiplier` | 1.0 | float | Tamed creature resistance |
| `DinoCharacterFoodDrainMultiplier` | 1.0 | float | Creature food consumption |
| `DinoCharacterHealthRecoveryMultiplier` | 1.0 | float | Creature health recovery |
| `DinoCharacterStaminaDrainMultiplier` | 1.0 | float | Creature stamina drain |

### Structure Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `StructureDamageMultiplier` | 1.0 | float | Structure damage dealt |
| `StructureResistanceMultiplier` | 1.0 | float | Structure damage resistance |
| `TheMaxStructuresInRange` | 10500 | int | Max structures in range |
| `PerPlatformMaxStructuresMultiplier` | 1.0 | float | Platform saddle structure limit scale |
| `MaxPlatformSaddleStructureLimit` | 75 | int | Max platformed creatures/rafts |
| `PlatformSaddleBuildAreaBoundsMultiplier` | 1.0 | float | Platform build area size |
| `StructurePreventResourceRadiusMultiplier` | 1.0 | float | Resource block radius around structures |
| `ForceAllStructureLocking` | False | bool | Default-lock all structures |
| `AlwaysAllowStructurePickup` | False | bool | Disable pickup timer |
| `StructurePickupTimeAfterPlacement` | 30.0 | float | Seconds for quick pickup |
| `StructurePickupHoldDuration` | 0.5 | float | Hold duration for pickup (0 = instant) |
| `AllowIntegratedSPlusStructures` | True | bool | Enable built-in S+ structures |
| `OverrideStructurePlatformPrevention` | False | bool | Allow turrets on platforms |

### Decay Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `DisableStructureDecayPvE` | False | bool | Disable structure decay in PvE |
| `DisableDinoDecayPvE` | False | bool | Disable creature decay in PvE |
| `PvEStructureDecayPeriodMultiplier` | 1.0 | float | Structure decay timer scale |
| `PvEDinoDecayPeriodMultiplier` | 1.0 | float | Creature decay timer scale |
| `AutoDestroyOldStructuresMultiplier` | 0.0 | float | Auto-destroy timer (1.0 to enable) |
| `AutoDestroyDecayedDinos` | False | bool | Auto-destroy claimable decayed tames |
| `FastDecayUnsnappedCoreStructures` | False | bool | Fast decay for lone foundations/pillars |
| `OnlyDecayUnsnappedCoreStructures` | False | bool | Only decay unsnapped cores |
| `DestroyUnconnectedWaterPipes` | False | bool | Auto-destroy disconnected pipes |
| `PvPStructureDecay` | False | bool | Structure decay in PvP with ORP |
| `PvPDinoDecay` | False | bool | Creature decay in PvP with ORP |

### Tame Limits

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `MaxTamedDinos` | 5000 | float | Global tame cap |
| `MaxPersonalTamedDinos` | 0 | int | Per-tribe limit (500 PvE official, 300 PvP) |
| `MaxTamedDinos_SoftTameLimit` | 5000 | int | Soft tame limit (ASA) |
| `MaxTamedDinos_SoftTameLimit_CountdownForDeletionDuration` | 604800 | int | Seconds before auto-destroy over soft limit |
| `DestroyTamesOverTheSoftTameLimit` | False | bool | Enable soft limit enforcement |
| `PersonalTamedDinosSaddleStructureCost` | 0 | int | Tame slots used by platform saddles |

### ORP (Offline Raid Prevention)

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `PreventOfflinePvP` | False | bool | Enable ORP |
| `PreventOfflinePvPInterval` | 0.0 | float | Seconds before ORP activates |

### Cross-ARK Transfer Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `noTributeDownloads` | False | bool | Block CrossArk downloads |
| `PreventDownloadSurvivors` | False | bool | Block survivor downloads |
| `PreventDownloadItems` | False | bool | Block item downloads |
| `PreventDownloadDinos` | False | bool | Block creature downloads |
| `PreventUploadSurvivors` | False | bool | Block survivor uploads |
| `PreventUploadItems` | False | bool | Block item uploads |
| `PreventUploadDinos` | False | bool | Block creature uploads |
| `MinimumDinoReuploadInterval` | 0.0 | float | Creature re-upload cooldown (43200 on official) |
| `CrossARKAllowForeignDinoDownloads` | False | bool | Allow non-native creature downloads on Aberration |
| `TributeCharacterExpirationSeconds` | 0 | int | Uploaded survivor expiry (0 = never) |
| `TributeDinoExpirationSeconds` | 86400 | int | Uploaded creature expiry |
| `TributeItemExpirationSeconds` | 86400 | int | Uploaded item expiry |
| `MaxTributeCharacters` | 10 | int | Max uploaded character slots |
| `MaxTributeDinos` | 20 | int | Max uploaded creature slots |
| `MaxTributeItems` | 50 | int | Max uploaded item slots |

### Gameplay Toggles

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `AllowAnyoneBabyImprintCuddle` | False | bool | Anyone can imprint-cuddle |
| `AllowCaveBuildingPvE` | False | bool | Building in caves (PvE) |
| `AllowCaveBuildingPvP` | True | bool | Building in caves (PvP) |
| `AllowCrateSpawnsOnTopOfStructures` | False | bool | Supply crates on structures |
| `AllowFlyerCarryPvE` | False | bool | Flyers carry wilds in PvE |
| `AllowFlyingStaminaRecovery` | False | bool | Recover stamina on flyer |
| `AllowHitMarkers` | True | bool | Ranged hit markers |
| `AllowMultipleAttachedC4` | False | bool | Multiple C4 per creature |
| `AllowRaidDinoFeeding` | False | bool | Feed Titanosaurs permanently |
| `AllowSharedConnections` | False | bool | Family sharing players |
| `AllowThirdPersonPlayer` | True | bool | Third person camera |
| `ClampItemSpoilingTimes` | False | bool | Clamp spoiling to max |
| `ClampItemStats` | False | bool | Enable item stat clamping |
| `ClampResourceHarvestDamage` | False | bool | Limit harvest damage to resource HP |
| `DisableCryopodEnemyCheck` | False | bool | Use cryopods near enemies |
| `DisableCryopodFridgeRequirement` | False | bool | Use cryopods without fridge |
| `DisableImprintDinoBuff` | False | bool | Disable imprint stat bonus |
| `DisablePvEGamma` | False | bool | Disable gamma in PvE |
| `DisableWeatherFog` | False | bool | Disable fog |
| `EnablePvPGamma` | False | bool | Allow gamma in PvP |
| `EnableExtraStructurePreventionVolumes` | False | bool | Block building near mountains |
| `globalVoiceChat` | False | bool | Global voice chat |
| `NonPermanentDiseases` | False | bool | Diseases clear on respawn |
| `OxygenSwimSpeedStatMultiplier` | 1.0 | float | Oxygen swim speed bonus |
| `PreventDiseases` | False | bool | Disable diseases entirely |
| `PreventMateBoost` | False | bool | Disable mate boost |
| `PreventSpawnAnimations` | False | bool | Skip wake-up animation |
| `PreventTribeAlliances` | False | bool | Disable tribe alliances |
| `ProximityChat` | False | bool | Chat only near players |
| `RandomSupplyCratePoints` | False | bool | Random supply drop locations |
| `ShowFloatingDamageText` | False | bool | RPG damage numbers |
| `ShowMapPlayerLocation` | True | bool | Show player on map |
| `UseOptimizedHarvestingHealth` | False | bool | Optimized harvesting |

### Cryopod Nerf (PvP)

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `EnableCryopodNerf` | False | bool | No cryo cooldown, use damage debuff instead |
| `CryopodNerfDamageMult` | 0.01 | float | Damage reduction after deploy |
| `CryopodNerfDuration` | 0 | int | Debuff duration seconds |
| `CryopodNerfIncomingDamageMultPercent` | 0.0 | float | Extra incoming damage % |
| `EnableCryoSicknessPVE` | False | bool | Enable cryo cooldown in PvE |

### Text Filtering

| Option | Default | Description |
|--------|---------|-------------|
| `BadWordListURL` | official | Custom bad words URL |
| `BadWordWhiteListURL` | official | Custom good words URL |
| `bFilterCharacterNames` | False | Filter character names |
| `bFilterChat` | False | Filter chat |
| `bFilterTribeNames` | False | Filter tribe names |

### Tek Bunker (ASA)

| Option | Default | Type |
|--------|---------|------|
| `LimitBunkersPerTribe` | True | bool |
| `LimitBunkersPerTribeNum` | 3 | int |
| `AllowBunkersInPreventionZones` | False | bool |
| `AllowRidingDinosInsideBunkers` | True | bool |
| `AllowBunkerModulesAboveGround` | False | bool |
| `MinDistanceBetweenBunkers` | 3000.0 | float |
| `EnemyAccessBunkerHPThreshold` | 0.25 | float |

### NPC Network Stasis (Performance)

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `NPCNetworkStasisRangeScalePlayerCountStart` | 0 | int | Min players for range scaling (official: 24) |
| `NPCNetworkStasisRangeScalePlayerCountEnd` | 0 | int | Max players for range scaling (official: 70) |
| `NPCNetworkStasisRangeScalePercentEnd` | 0.55 | float | Max scale % (official: 0.5) |

## [SessionSettings]

| Option | Default | Description |
|--------|---------|-------------|
| `SessionName` | `ARK #123456` | Server name |
| `Port` | 7777 | UDP game port |
| `QueryPort` | 27015 | UDP Steam query port |
| `MultiHome` | — | Multihome IP address |

## [/Script/Engine.GameSession]

| Option | Default | Description |
|--------|---------|-------------|
| `MaxPlayers` | 70 | Max players (ASA: use `-WinLiveMaxPlayers` instead) |

## [MessageOfTheDay]

| Option | Default | Description |
|--------|---------|-------------|
| `Message` | — | Login message (`\n` for newlines) |
| `Duration` | 20 | Display duration in seconds |

## [Ragnarok]

| Option | Default | Description |
|--------|---------|-------------|
| `AllowMultipleTamedUnicorns` | False | Unlimited tamed unicorns |
| `EnableVolcano` | True | Volcano activity |
| `UnicornSpawnInterval` | 24 | Hours between unicorn respawns |
| `VolcanoIntensity` | 1 | Eruption intensity (lower = more intense, min 0.25) |
| `VolcanoInterval` | 0 | Seconds between eruptions (0 = 5000-15000s) |
