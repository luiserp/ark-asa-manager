# ARK: Survival Ascended — Spawn Configuration Guide

This document covers everything about customizing creature spawns on an ARK dedicated server: what settings are available, where the data comes from, and how to use each one.

All spawn-related settings go in **Game.ini** under `[/script/shootergame.shootergamemode]` unless otherwise noted. Changes require a **server restart** to take effect (except `NPCReplacements` via DynamicConfig).

---

## Table of Contents

- [Quick Reference: Which Setting to Use](#quick-reference-which-setting-to-use)
- [1. DinoCountMultiplier](#1-dinocountmultiplier)
- [2. DinoSpawnWeightMultipliers](#2-dinospawnweightmultipliers)
- [3. NPCReplacements](#3-npcreplacements)
- [4. ConfigAddNPCSpawnEntriesContainer](#4-configaddnpcspawnentriescontainer)
- [5. ConfigSubtractNPCSpawnEntriesContainer](#5-configsubtractnpcspawnentriescontainer)
- [6. ConfigOverrideNPCSpawnEntriesContainer](#6-configoverridenpcspawnentriescontainer)
- [Advanced Spawn Arguments](#advanced-spawn-arguments)
- [Data Sources: Where to Find IDs](#data-sources-where-to-find-ids)
  - [Creature Class Names & Name Tags](#creature-class-names--name-tags)
  - [Spawn Zone Class Names (Per Map)](#spawn-zone-class-names-per-map)
- [Examples](#examples)
- [External References](#external-references)

---

## Quick Reference: Which Setting to Use

| Goal | Setting | File | Restart Required |
|------|---------|------|:---:|
| More/fewer wild dinos **overall** | `DinoCountMultiplier` | GameUserSettings.ini | Yes |
| Make one species more/less common **map-wide** | `DinoSpawnWeightMultipliers` | Game.ini | Yes |
| Swap species A for species B **everywhere** | `NPCReplacements` | Game.ini (or DynamicConfig) | Yes (No via DynamicConfig) |
| Add a creature to a **specific zone** | `ConfigAddNPCSpawnEntriesContainer` | Game.ini | Yes |
| Remove a creature from a **specific zone** | `ConfigSubtractNPCSpawnEntriesContainer` | Game.ini | Yes |
| Completely redesign a **zone's spawns** | `ConfigOverrideNPCSpawnEntriesContainer` | Game.ini | Yes |
| Remove a species **entirely** from the map | `NPCReplacements` with empty `ToClassName` | Game.ini | Yes |

---

## 1. DinoCountMultiplier

**File:** `GameUserSettings.ini` → `[ServerSettings]`

The simplest spawn setting — a single global multiplier that controls the total number of wild creatures across the entire map.

```ini
[ServerSettings]
DinoCountMultiplier=2.0
```

- **Default:** `1.0`
- **Type:** float
- **Effect:** Multiplies the total wild creature population. `2.0` = roughly double the spawns, `0.5` = half.
- **Important:** Affects **all** species equally. It doesn't change what spawns, only how many.

> **Tip:** High values (3.0+) can cause lag on large maps. Increase gradually and test.

---

## 2. DinoSpawnWeightMultipliers

**File:** `Game.ini` → `[/script/shootergame.shootergamemode]`

Adjusts how often a specific species spawns relative to others, across the **entire map**. Doesn't change spawn zones — just the probability of a species being chosen when something spawns.

```ini
DinoSpawnWeightMultipliers=(DinoNameTag=Rex,SpawnWeightMultiplier=3.0,OverrideSpawnLimitPercentage=True,SpawnLimitPercentage=0.5)
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `DinoNameTag` | string | The creature's **name tag** (not the full class name). See [creature IDs](#creature-class-names--name-tags). Examples: `Rex`, `Raptor`, `Spino`, `Trike` |
| `SpawnWeightMultiplier` | float | How much more (>1) or less (<1) likely the species is to spawn. `3.0` = 3x more common, `0.1` = 10x rarer |
| `OverrideSpawnLimitPercentage` | bool | Whether to also control the species' population cap |
| `SpawnLimitPercentage` | float | Max percentage of all wild dinos this species can be. `0.5` = up to 50% of all wild spawns. Only used when `OverrideSpawnLimitPercentage=True` |

> **When to use:** You want more or fewer of a species everywhere, without caring about specific zones. The simplest per-species control.

### Multiple Entries

Repeat the full line for each creature — one entry per line, never combined:

```ini
DinoSpawnWeightMultipliers=(DinoNameTag=Rex,SpawnWeightMultiplier=3.0,OverrideSpawnLimitPercentage=True,SpawnLimitPercentage=0.5)
DinoSpawnWeightMultipliers=(DinoNameTag=Spino,SpawnWeightMultiplier=2.0,OverrideSpawnLimitPercentage=True,SpawnLimitPercentage=0.3)
DinoSpawnWeightMultipliers=(DinoNameTag=Raptor,SpawnWeightMultiplier=0.1,OverrideSpawnLimitPercentage=True,SpawnLimitPercentage=0.05)
```

This same pattern applies to **all** spawn settings (`NPCReplacements`, `ConfigAdd...`, `ConfigSubtract...`, `ConfigOverride...`) — always one complete declaration per line.

---

## 3. NPCReplacements

**File:** `Game.ini` → `[/script/shootergame.shootergamemode]` (also available via DynamicConfig)

Globally replaces every spawn of one species with another. Every spawn point that would produce species A now produces species B instead.

```ini
NPCReplacements=(FromClassName="Trike_Character_BP_C",ToClassName="Rex_Character_BP_C")
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `FromClassName` | string | The creature **class name** to replace (e.g. `Trike_Character_BP_C`). See [creature IDs](#creature-class-names--name-tags) |
| `ToClassName` | string | The creature **class name** to spawn instead. Use `""` (empty) to remove the species entirely |

### Examples

**Replace all Trikes with Rexes:**
```ini
NPCReplacements=(FromClassName="Trike_Character_BP_C",ToClassName="Rex_Character_BP_C")
```

**Remove Ichthyornis from the map entirely:**
```ini
NPCReplacements=(FromClassName="Ichthyornis_Character_BP_C",ToClassName="")
```

> **DynamicConfig:** This is one of the few spawn settings that works with DynamicConfig, meaning it can be applied **live without a restart**. The ARK server fetches the DynamicConfig on each autosave cycle (~15 minutes).

---

## 4. ConfigAddNPCSpawnEntriesContainer

**File:** `Game.ini` → `[/script/shootergame.shootergamemode]`

Adds a creature to a specific spawn zone **without removing** what already spawns there. The new creature appears alongside the defaults.

```ini
ConfigAddNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesBeach_C",NPCSpawnEntries=((AnEntryName="BeachGiga",EntryWeight=0.5,NPCsToSpawnStrings=("Gigant_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Gigant_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.01)))
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `NPCSpawnEntriesContainerClassString` | string | The **spawn zone class name** (map-specific). See [spawn zones](#spawn-zone-class-names-per-map). Example: `DinoSpawnEntriesBeach_C` |
| `NPCSpawnEntries` | array | One or more spawn entries, each defining what creature to add |
| → `AnEntryName` | string | A label for your reference (can be anything) |
| → `EntryWeight` | float | Probability weight relative to other entries in the zone. Higher = more common |
| → `NPCsToSpawnStrings` | array | One or more creature **class names** to spawn |
| `NPCSpawnLimits` | array | Population limits for each creature being added |
| → `NPCClassString` | string | The creature **class name** |
| → `MaxPercentageOfDesiredNumToAllow` | float | Max fraction of the zone's population this creature can fill. `0.01` = 1%, `0.5` = 50% |

> **When to use:** You want a creature to appear in a zone where it doesn't naturally exist (e.g. Gigas on beaches), while keeping all default spawns.

---

## 5. ConfigSubtractNPCSpawnEntriesContainer

**File:** `Game.ini` → `[/script/shootergame.shootergamemode]`

Removes a specific creature from a specific spawn zone. The creature stops spawning in that zone but continues to spawn in other zones where it naturally appears.

```ini
ConfigSubtractNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesBeach_C",NPCSpawnEntries=((NPCsToSpawnStrings=("Trike_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Trike_Character_BP_C")))
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `NPCSpawnEntriesContainerClassString` | string | The **spawn zone class name** to remove the creature from |
| `NPCSpawnEntries` | array | Creatures to remove |
| → `NPCsToSpawnStrings` | array | Creature **class names** to remove from this zone |
| `NPCSpawnLimits` | array | Must also list the creature here |
| → `NPCClassString` | string | The creature **class name** |

> **When to use:** You want to remove a specific creature from one area only (e.g. no Raptors on beaches, but keep them in jungles).

---

## 6. ConfigOverrideNPCSpawnEntriesContainer

**File:** `Game.ini` → `[/script/shootergame.shootergamemode]`

**Completely replaces** all creatures in a spawn zone with your custom list. Everything that normally spawns in that zone is removed and replaced with what you define.

```ini
ConfigOverrideNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesMountain_C",NPCSpawnEntries=((AnEntryName="MountainRex",EntryWeight=1.0,NPCsToSpawnStrings=("Rex_Character_BP_C")),(AnEntryName="MountainGiga",EntryWeight=0.2,NPCsToSpawnStrings=("Gigant_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Rex_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.5),(NPCClassString="Gigant_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.05)))
```

Uses the same parameters as [ConfigAddNPCSpawnEntriesContainer](#4-configaddnpcspawnentriescontainer), but **replaces** instead of adding.

> **When to use:** You want full control over a zone. For example, making mountains spawn only Rexes and Gigas with nothing else.

> **Warning:** This removes ALL default spawns from the zone. If you only list 2 creatures, only those 2 will ever spawn there.

---

## Advanced Spawn Arguments

These optional parameters can be added inside the `NPCSpawnEntries` section of any spawn container setting for finer control:

| Argument | Type | Description |
|----------|------|-------------|
| `NPCsToSpawnPercentageChance=(0.5)` | float array | Chance per spawn attempt. `0.5` = 50% chance each time the spawner fires |
| `NPCOverrideLevel=150` | int | Force spawned creatures to be a specific level |
| `NPCMinLevelOffset=10` | float | Increase the minimum possible level |
| `NPCMaxLevelOffset=50` | float | Increase the maximum possible level |
| `NPCMinLevelMultiplier=2.0` | float | Multiply the minimum level |
| `NPCMaxLevelMultiplier=2.0` | float | Multiply the maximum level |
| `NPCsSpawnOffsets=((X=0,Y=0,Z=100))` | vector array | Offset spawn position (useful for flying creatures) |
| `ManualSpawnPointSpreadRadius=500` | float | How far from the spawn point creatures can appear |
| `RandGroupSpawnOffsetZMin=-100` | float | Minimum height offset |
| `RandGroupSpawnOffsetZMax=200` | float | Maximum height offset |

### Example: Add Wyverns to mountains at level 150 with 30% spawn chance

```ini
ConfigAddNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesMountain_C",NPCSpawnEntries=((AnEntryName="MountainWyvern",EntryWeight=0.3,NPCsToSpawnStrings=("Wyvern_Character_BP_Fire_C"),NPCsToSpawnPercentageChance=(0.3),NPCOverrideLevel=150)),NPCSpawnLimits=((NPCClassString="Wyvern_Character_BP_Fire_C",MaxPercentageOfDesiredNumToAllow=0.02)))
```

---

## Data Sources: Where to Find IDs

The spawn zone names, creature class names, and name tags are not included in the ARK server files. They come from the game's internal asset data, maintained by the community.

### Creature Class Names & Name Tags

Every creature has three identifiers:

| Identifier | Example | Used In |
|------------|---------|---------|
| **Class Name** | `Rex_Character_BP_C` | `NPCsToSpawnStrings`, `NPCClassString`, `NPCReplacements`, `ConfigSubtract...` |
| **Name Tag** | `Rex` | `DinoSpawnWeightMultipliers` |
| **Blueprint Path** | `Blueprint'/Game/PrimalEarth/Dinos/Rex/Rex_Character_BP.Rex_Character_BP'` | Admin spawn commands |

**Where to find them:**

- **Full creature ID table:** [ark.wiki.gg/wiki/Creature_IDs](https://ark.wiki.gg/wiki/Creature_IDs) — searchable table with class name, name tag, and blueprint path for every creature in the game (base game + DLCs).
- **ASA-specific creatures:** [ark.wiki.gg/wiki/Mod:Ark_Ascended/Creatures](https://ark.wiki.gg/wiki/Mod:Ark_Ascended) — creatures unique to ASA.

**Common creatures quick reference:**

| Creature | Class Name | Name Tag |
|----------|-----------|----------|
| Rex | `Rex_Character_BP_C` | `Rex` |
| Raptor | `Raptor_Character_BP_C` | `Raptor` |
| Trike | `Trike_Character_BP_C` | `Trike` |
| Spino | `Spino_Character_BP_C` | `Spino` |
| Giganotosaurus | `Gigant_Character_BP_C` | `Gigant` |
| Argentavis | `Argent_Character_BP_C` | `Argent` |
| Quetzal | `Quetz_Character_BP_C` | `Quetz` |
| Wyvern (Fire) | `Wyvern_Character_BP_Fire_C` | `Wyvern` |
| Dodo | `Dodo_Character_BP_C` | `Dodo` |
| Parasaur | `Para_Character_BP_C` | `Para` |
| Pteranodon | `Ptero_Character_BP_C` | `Ptero` |
| Carno | `Carno_Character_BP_C` | `Carno` |
| Bronto | `Sauropod_Character_BP_C` | `Sauropod` |
| Stego | `Stego_Character_BP_C` | `Stego` |
| Ankylo | `Ankylo_Character_BP_C` | `Ankylo` |
| Ichthyornis | `Ichthyornis_Character_BP_C` | `Ichthyornis` |
| Megalodon | `Megalodon_Character_BP_C` | `Mega` |
| Mosa | `Mosa_Character_BP_C` | `Mosa` |
| Therizinosaurus | `Therizino_Character_BP_C` | `Therizino` |
| Yutyrannus | `Yutyrannus_Character_BP_C` | `Yutyrannus` |

> For the full list of 400+ creatures, see [ark.wiki.gg/wiki/Creature_IDs](https://ark.wiki.gg/wiki/Creature_IDs).

### Spawn Zone Class Names (Per Map)

Each map has its own set of spawn zones. A spawn zone is a geographic area (beach, mountain, swamp, etc.) with a specific creature spawn table.

**Where to find them:**

- **The Island spawn map:** [ark.wiki.gg/wiki/Spawn_Map_(The_Island)](https://ark.wiki.gg/wiki/Spawn_Map_(The_Island))
- **Spawn entries reference:** [ark.wiki.gg/wiki/Spawn_Entries](https://ark.wiki.gg/wiki/Spawn_Entries)
- **Per-map spawn containers:** Each map's wiki page lists its spawn entry containers under the "Spawning" or "Spawn Entries" section.

**The Island — Common spawn zones:**

| Zone | Class Name |
|------|-----------|
| Beach (general) | `DinoSpawnEntriesBeach_C` |
| Beach (South) | `DinoSpawnEntriesSouthBeach_C` |
| Jungle | `DinoSpawnEntriesJungle_C` |
| Mountain | `DinoSpawnEntriesMountain_C` |
| Forest | `DinoSpawnEntriesForest_C` |
| Redwood | `DinoSpawnEntriesRedwood_C` |
| Snow | `DinoSpawnEntriesSnow_C` |
| Swamp | `DinoSpawnEntriesSwamp_C` |
| River | `DinoSpawnEntriesRiver_C` |
| Ocean | `DinoSpawnEntriesOcean_C` |
| Deep Ocean | `DinoSpawnEntriesDeepOcean_C` |
| Cave (various) | `DinoSpawnEntriesCave_C`, `DinoSpawnEntriesCaveLava_C`, etc. |

> **Important:** Other maps (Scorched Earth, Ragnarok, Aberration, etc.) have different spawn zone names specific to their geography. Always check the wiki for the specific map you're configuring.

---

## Examples

### Example 1: Boosted Rex server

Make Rexes 5x more common everywhere, double overall wild spawns:

**GameUserSettings.ini:**
```ini
[ServerSettings]
DinoCountMultiplier=2.0
```

**Game.ini:**
```ini
[/script/shootergame.shootergamemode]
DinoSpawnWeightMultipliers=(DinoNameTag=Rex,SpawnWeightMultiplier=5.0,OverrideSpawnLimitPercentage=True,SpawnLimitPercentage=0.3)
```

### Example 2: Remove annoying creatures

Remove Ichthyornis and Pegomastax from the entire map:

```ini
[/script/shootergame.shootergamemode]
NPCReplacements=(FromClassName="Ichthyornis_Character_BP_C",ToClassName="")
NPCReplacements=(FromClassName="Pegomastax_Character_BP_C",ToClassName="")
```

### Example 3: Custom beach spawns

Remove Trikes from beaches and add Raptors instead:

```ini
[/script/shootergame.shootergamemode]
ConfigSubtractNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesBeach_C",NPCSpawnEntries=((NPCsToSpawnStrings=("Trike_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Trike_Character_BP_C")))
ConfigAddNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesBeach_C",NPCSpawnEntries=((AnEntryName="BeachRaptors",EntryWeight=1.0,NPCsToSpawnStrings=("Raptor_Character_BP_C"))),NPCSpawnLimits=((NPCClassString="Raptor_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.15)))
```

### Example 4: Full mountain takeover

Replace all mountain spawns with only high-level Rexes and Gigas:

```ini
[/script/shootergame.shootergamemode]
ConfigOverrideNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="DinoSpawnEntriesMountain_C",NPCSpawnEntries=((AnEntryName="AlphaRex",EntryWeight=1.0,NPCsToSpawnStrings=("Rex_Character_BP_C"),NPCMinLevelMultiplier=2.0),(AnEntryName="RareGiga",EntryWeight=0.1,NPCsToSpawnStrings=("Gigant_Character_BP_C"),NPCsToSpawnPercentageChance=(0.3))),NPCSpawnLimits=((NPCClassString="Rex_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.8),(NPCClassString="Gigant_Character_BP_C",MaxPercentageOfDesiredNumToAllow=0.05)))
```

### Example 5: Replace all Dodos with Rexes (chaos mode)

```ini
[/script/shootergame.shootergamemode]
NPCReplacements=(FromClassName="Dodo_Character_BP_C",ToClassName="Rex_Character_BP_C")
```

---

## External References

- **ARK Wiki — Server Configuration:** [ark.wiki.gg/wiki/Server_configuration](https://ark.wiki.gg/wiki/Server_configuration)
- **ARK Wiki — Creature IDs (full list):** [ark.wiki.gg/wiki/Creature_IDs](https://ark.wiki.gg/wiki/Creature_IDs)
- **ARK Wiki — Spawn Entries:** [ark.wiki.gg/wiki/Spawn_Entries](https://ark.wiki.gg/wiki/Spawn_Entries)
- **ARK Wiki — Spawn Map (The Island):** [ark.wiki.gg/wiki/Spawn_Map_(The_Island)](https://ark.wiki.gg/wiki/Spawn_Map_(The_Island))
- **ARK Wiki — DynamicConfig:** [ark.wiki.gg/wiki/Dynamic_Config](https://ark.wiki.gg/wiki/Server_configuration#DynamicConfig)
- **ARK Wiki — Console Commands:** [ark.wiki.gg/wiki/Console_commands](https://ark.wiki.gg/wiki/Console_commands)
