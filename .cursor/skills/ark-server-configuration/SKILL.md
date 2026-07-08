---
name: ark-server-configuration
description: >-
  ARK: Survival Ascended (ASA) and ARK: Survival Evolved (ASE) dedicated server
  configuration reference. Use when configuring server launch commands, editing
  GameUserSettings.ini, Game.ini, setting up clusters, Cross-ARK transfers,
  DynamicConfig, creature spawn overrides, engram overrides, loot crate
  overrides, stat multipliers, or any server administration task. Activate when
  the user mentions ARK server settings, INI files, command line arguments,
  server configuration, mods, difficulty, taming speed, breeding rates, or
  similar ARK server admin topics.
---

# ARK Server Configuration

Source: https://ark.wiki.gg/wiki/Server_configuration

## Command Line Syntax

```
<executable> <map_name>[?<option>=<value>]... [-<option>[=<value>]] ...
```

- `?` options are appended directly after the map name with no spaces
- `-` options are separated by spaces
- For single player / non-dedicated, only `-` options work; use INI files for `?` options

### ASA Map Names

| Map | Level Name |
|-----|-----------|
| The Island | `TheIsland_WP` |
| The Center | `TheCenter_WP` |
| Scorched Earth | `ScorchedEarth_WP` |
| Ragnarok | `Ragnarok_WP` |
| Aberration | `Aberration_WP` |
| Extinction | `Extinction_WP` |
| Valguero | `Valguero_WP` |
| Genesis: Part 1 | `Genesis_WP` |
| Astraeos | `Astraeos_WP` |
| Lost Colony | `LostColony_WP` |

## Configuration Files

| Platform | Path |
|----------|------|
| Linux | `ShooterGame/Saved/Config/LinuxServer/` |
| Windows | `ShooterGame\Saved\Config\WindowsServer\` |

Files: `GameUserSettings.ini` and `Game.ini`. Options are `key=value`, one per line.

## Key Command Line Options (ASA-focused)

| Option | Description |
|--------|-------------|
| `-mods=<id>[,<id>...]` | CurseForge mod IDs, auto-updated on start |
| `-passivemods=<id>[,<id>...]` | Load mod data without activating functionality |
| `-port=<port>` | Server port (default 7777) |
| `-WinLiveMaxPlayers=<n>` | Max players (default 70, replaces MaxPlayers INI) |
| `-clusterid=<name>` | Cluster name for Cross-ARK transfers |
| `-ClusterDirOverride=<path>` | Cross-server storage location |
| `-NoBattlEye` | Run without BattlEye |
| `-ForceRespawnDinos` | Destroy all wild creatures on start |
| `-AutoDestroyStructures` | Enable auto-destruction of old structures |
| `-UseDynamicConfig` | Enable dynamic config (live tuning without restart) |
| `-ServerPlatform=<platforms>` | `PC`, `PS5`, `XSX`, `WINGDK`, `ALL` (crossplay) |
| `-GBUsageToForceRestart=<GB>` | OOM protection restart threshold (default 35) |
| `-exclusivejoin` | Whitelist-only mode |
| `?AltSaveDirectoryName=<name>` | Custom save directory name |

For the full command line reference, see [reference-command-line.md](reference-command-line.md).

## GameUserSettings.ini Key Sections

### [ServerSettings] — Common Options

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `ServerPassword` | — | string | Join password |
| `ServerAdminPassword` | — | string | Admin password |
| `SessionName` | — | string | Set in `[SessionSettings]` |
| `serverPVE` | False | bool | Enable PvE mode |
| `DifficultyOffset` | 1.0 | float | Difficulty level |
| `OverrideOfficialDifficulty` | 0.0 | float | 5.0 = max level 150 wild |
| `XPMultiplier` | 1.0 | float | Global XP scaling |
| `TamingSpeedMultiplier` | 1.0 | float | Taming speed |
| `HarvestAmountMultiplier` | 1.0 | float | Harvest yield |
| `DayCycleSpeedScale` | 1.0 | float | Day/night cycle speed |
| `DayTimeSpeedScale` | 1.0 | float | Daytime length |
| `NightTimeSpeedScale` | 1.0 | float | Nighttime length |
| `DinoCountMultiplier` | 1.0 | float | Wild creature spawn count |
| `DinoDamageMultiplier` | 1.0 | float | Wild creature damage |
| `PlayerDamageMultiplier` | 1.0 | float | Player damage |
| `TamedDinoDamageMultiplier` | 1.0 | float | Tamed creature damage |
| `StructureDamageMultiplier` | 1.0 | float | Structure damage dealt |
| `PlayerResistanceMultiplier` | 1.0 | float | Player damage taken (higher = more damage) |
| `DinoResistanceMultiplier` | 1.0 | float | Wild dino damage taken |
| `TamedDinoResistanceMultiplier` | 1.0 | float | Tamed dino damage taken |
| `StructureResistanceMultiplier` | 1.0 | float | Structure damage taken |
| `MaxTamedDinos` | 5000 | float | Global tame cap |
| `MaxPersonalTamedDinos` | 0 | int | Per-tribe tame limit (0 = off) |
| `ActiveMods` | — | string | Comma-separated mod IDs (ASE) |
| `AutoSavePeriodMinutes` | 15.0 | float | Autosave interval |
| `RCONEnabled` | False | bool | Enable RCON |
| `RCONPort` | 27020 | int | RCON TCP port |
| `PreventOfflinePvP` | False | bool | Offline Raid Prevention |
| `AllowThirdPersonPlayer` | True | bool | Third person camera |
| `ShowFloatingDamageText` | False | bool | RPG damage numbers |
| `ItemStackSizeMultiplier` | 1.0 | float | Global stack size multiplier |

### [SessionSettings]

| Option | Default | Description |
|--------|---------|-------------|
| `SessionName` | `ARK #123456` | Server name in browser |
| `Port` | 7777 | UDP game port |
| `QueryPort` | 27015 | UDP Steam query port |

### [MessageOfTheDay]

| Option | Default | Description |
|--------|---------|-------------|
| `Message` | — | Login message (`\n` for newlines) |
| `Duration` | 20 | Display duration in seconds |

For the full GameUserSettings.ini reference, see [reference-gameusersettings.md](reference-gameusersettings.md).

## Game.ini Key Options

All options go under `[/script/shootergame.shootergamemode]`.

### Breeding & Imprinting

| Option | Default | Description |
|--------|---------|-------------|
| `BabyMatureSpeedMultiplier` | 1.0 | Baby maturation speed |
| `EggHatchSpeedMultiplier` | 1.0 | Egg hatch speed |
| `MatingIntervalMultiplier` | 1.0 | Time between mating |
| `MatingSpeedMultiplier` | 1.0 | Mating process speed |
| `BabyCuddleIntervalMultiplier` | 1.0 | Imprint care frequency |
| `BabyImprintAmountMultiplier` | 1.0 | % gained per imprint |
| `BabyImprintingStatScaleMultiplier` | 1.0 | Imprint stat bonus |
| `BabyFoodConsumptionSpeedMultiplier` | 1.0 | Baby food drain |
| `BabyCuddleGracePeriodMultiplier` | 1.0 | Grace before imprint decay |

### Per-Level Stat Multipliers

Use attribute indices: 0=Health, 1=Stamina, 2=Torpidity, 3=Oxygen, 4=Food, 5=Water, 6=Temp, 7=Weight, 8=Melee, 9=Speed, 10=Fortitude, 11=Crafting.

```ini
PerLevelStatsMultiplier_Player[7]=2.0
PerLevelStatsMultiplier_DinoWild[0]=1.0
PerLevelStatsMultiplier_DinoTamed[0]=1.0
PerLevelStatsMultiplier_DinoTamed_Add[0]=1.0
PerLevelStatsMultiplier_DinoTamed_Affinity[0]=1.0
```

### Creature Spawn Overrides

**Add creatures to a spawn area:**
```ini
ConfigAddNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesBeach_C",NPCSpawnEntries=((AnEntryName="GigaSpawner",EntryWeight=1.0,NPCsToSpawnStrings=("Gigant_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Gigant_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.01)))
```

**Remove creatures from a spawn area:**
```ini
ConfigSubtractNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesBeach_C",NPCSpawnEntries=((NPCsToSpawnStrings=("Trike_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Trike_Character_BP_C")))
```

**Replace all creatures in a spawn area:**
```ini
ConfigOverrideNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesMountain_C",NPCSpawnEntries=((AnEntryName="RexSpawner",EntryWeight=1.0,NPCsToSpawnStrings=("Rex_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Rex_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.5)))
```

**Replace one creature species with another globally:**
```ini
NPCReplacements=(FromClassName="MegaRaptor_Character_BP_C",ToClassName="Dodo_Character_BP_C")
```

Each entry must be on a single line with no spaces in the INI file.

### Engram Overrides

```ini
OverrideNamedEngramEntries=(EngramClassName="EngramEntry_Campfire_C",EngramHidden=False,EngramPointsCost=3,EngramLevelRequirement=3,RemoveEngramPreReq=True)
bAutoUnlockAllEngrams=True
EngramEntryAutoUnlocks=(EngramClassName="EngramEntry_TekTeleporter_C",LevelToAutoUnlock=0)
```

### Item Overrides

**Override crafting cost:**
```ini
ConfigOverrideItemCraftingCosts=(ItemClassString="PrimalItem_WeaponStoneHatchet_C",BaseCraftingResourceRequirements=((ResourceItemTypeString="PrimalItemResource_Thatch_C",BaseResourceRequirement=1.0,bCraftingRequireExactResourceType=False)))
```

**Override stack size:**
```ini
ConfigOverrideItemMaxQuantity=(ItemClassString="PrimalItemAmmo_ArrowTranq_C",Quantity=(MaxItemQuantity=543,bIgnoreMultiplier=True))
```

For the full Game.ini reference, see [reference-game-ini.md](reference-game-ini.md).

## Cross-ARK Cluster Setup

Run multiple servers with the same `clusterid` and shared cluster directory:

```
ArkAscendedServer.exe TheIsland_WP?SessionName=Server1 -clusterid=MyCluster -ClusterDirOverride="C:\MyClusterData"
ArkAscendedServer.exe ScorchedEarth_WP?SessionName=Server2 -clusterid=MyCluster -ClusterDirOverride="C:\MyClusterData"
```

Transfer control options in `[ServerSettings]`:
- `noTributeDownloads`, `PreventDownloadSurvivors/Items/Dinos`
- `PreventUploadSurvivors/Items/Dinos`
- `MinimumDinoReuploadInterval` (seconds, official = 43200)
- `TributeCharacterExpirationSeconds`, `TributeDinoExpirationSeconds`, `TributeItemExpirationSeconds`

Keep expiration values consistent across all cluster servers.

## DynamicConfig

Live-tuning without restart. Requires `-UseDynamicConfig` and `CustomDynamicConfigUrl` in `[ServerSettings]`. The file is fetched on each autosave or via `ForceUpdateDynamicConfig` console command. Must be served via HTTP (not HTTPS for ASE, both supported for ASA) with `.ini` extension as `text/plain`.

Supports overriding: `XPMultiplier`, `TamingSpeedMultiplier`, `HarvestAmountMultiplier`, `MatingIntervalMultiplier`, `BabyMatureSpeedMultiplier`, `EggHatchSpeedMultiplier`, `BabyCuddleIntervalMultiplier`, `BabyImprintAmountMultiplier`, `GlobalSpoilingTimeMultiplier`, `StructureDamageMultiplier`, `ActiveEventColors`, `NPCReplacements`, and more.

For the full DynamicConfig reference, see [reference-dynamic-config.md](reference-dynamic-config.md).

## ARK IDs & Whitelisting

**ASA** uses EOS IDs (32-char alphanumeric). Get via `whoami` console command.
**ASE** uses Steam IDs (17-digit numeric).

| File | Purpose |
|------|---------|
| `ShooterGame/Saved/AllowedCheaterAccountIDs.txt` | Admin whitelist (ASA) |
| `ShooterGame/Saved/AllowedCheaterSteamIDs.txt` | Admin whitelist (ASE) |
| `ShooterGame/Binaries/<platform>/PlayersExclusiveJoinList.txt` | Join whitelist (`-exclusivejoin`) |
| `ShooterGame/Binaries/<platform>/PlayersJoinNoCheckList.txt` | Bypass max player count |

## Stat Attribute Index Table

| Index | Stat |
|-------|------|
| 0 | Health |
| 1 | Stamina / Charge Capacity |
| 2 | Torpidity |
| 3 | Oxygen / Charge Regeneration |
| 4 | Food |
| 5 | Water |
| 6 | Temperature (unused for players) |
| 7 | Weight |
| 8 | Melee Damage / Charge Emission Range |
| 9 | Movement Speed |
| 10 | Fortitude |
| 11 | Crafting Speed |

## Additional References

- [Command Line Options](reference-command-line.md)
- [GameUserSettings.ini Options](reference-gameusersettings.md)
- [Game.ini Options](reference-game-ini.md)
- [DynamicConfig Options](reference-dynamic-config.md)
