# ARK Game.ini Reference

Source: https://ark.wiki.gg/wiki/Server_configuration

All options go under `[/script/shootergame.shootergamemode]`.

## Breeding & Imprinting

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `BabyMatureSpeedMultiplier` | 1.0 | float | Baby maturation speed (higher = faster) |
| `EggHatchSpeedMultiplier` | 1.0 | float | Egg hatch speed (higher = faster) |
| `MatingIntervalMultiplier` | 1.0 | float | Time between mating (lower = shorter) |
| `MatingSpeedMultiplier` | 1.0 | float | Mating process speed (higher = faster) |
| `BabyCuddleIntervalMultiplier` | 1.0 | float | Imprint care frequency (default: 8h at 1.0) |
| `BabyImprintAmountMultiplier` | 1.0 | float | Imprint % per care |
| `BabyImprintingStatScaleMultiplier` | 1.0 | float | Imprint stat bonus effect (0 = disabled) |
| `BabyCuddleGracePeriodMultiplier` | 1.0 | float | Grace period before imprint decay |
| `BabyCuddleLoseImprintQualitySpeedMultiplier` | 1.0 | float | Imprint decay speed after grace |
| `BabyFoodConsumptionSpeedMultiplier` | 1.0 | float | Baby food drain (lower = less food) |
| `LayEggIntervalMultiplier` | 1.0 | float | Egg laying frequency |
| `bDisableDinoBreeding` | False | bool | Disable breeding entirely |
| `PreventBreedingForClassNames` | — | string | Block breeding per species classname |

## XP Multipliers

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `CraftXPMultiplier` | 1.0 | float | Crafting XP |
| `GenericXPMultiplier` | 1.0 | float | Passive XP over time |
| `HarvestXPMultiplier` | 1.0 | float | Harvesting XP |
| `KillXPMultiplier` | 1.0 | float | Kill XP |
| `SpecialXPMultiplier` | 1.0 | float | Special event XP |
| `OverrideMaxExperiencePointsPlayer` | — | int | Max player XP cap |
| `OverrideMaxExperiencePointsDino` | — | int | Max creature XP cap |

## Gameplay Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `bAllowSpeedLeveling` | False | bool | Allow speed stat leveling |
| `bAllowFlyerSpeedLeveling` | False | bool | Allow flyer speed leveling (requires `bAllowSpeedLeveling=True` in ASA) |
| `bAllowUnlimitedRespecs` | False | bool | Unlimited Mindwipe Tonic |
| `bAllowCustomRecipes` | True | bool | Custom recipe system |
| `bAllowPlatformSaddleMultiFloors` | False | bool | Multi-floor platforms |
| `bAllowUnclaimDinos` | True | bool | Allow unclaiming tames |
| `bDisableDinoRiding` | False | bool | Disable creature riding |
| `bDisableDinoTaming` | False | bool | Disable creature taming |
| `bDisableFriendlyFire` | False | bool | Disable tribe friendly fire |
| `bDisableLootCrates` | False | bool | Disable supply crate spawns |
| `bDisablePhotoMode` | False | bool | Disable photo mode |
| `bDisableStructurePlacementCollision` | False | bool | Allow structures to clip terrain |
| `bShowCreativeMode` | False | bool | Show creative mode button |
| `bUseCorpseLocator` | True | bool | Green beam at death location |
| `bUseDinoLevelUpAnimations` | True | bool | Level-up animation |
| `bUseSingleplayerSettings` | False | bool | Single-player balanced rates |
| `bPassiveDefensesDamageRiderlessDinos` | False | bool | Spikes damage riderless creatures |
| `CraftingSkillBonusMultiplier` | 1.0 | float | Crafting skill bonus scale |
| `CustomRecipeEffectivenessMultiplier` | 1.0 | float | Custom recipe effectiveness |
| `CustomRecipeSkillMultiplier` | 1.0 | float | Custom recipe crafting speed effect |
| `DestroyTamesOverLevelClamp` | 0 | int | Delete tames above this level (official: 450) |
| `MaxNumberOfPlayersInTribe` | 0 | int | Max tribe members (0 = unlimited, 1 = no tribes) |
| `MaxTribeLogs` | 400 | int | Max tribe log entries |
| `MaxFallSpeedMultiplier` | 1.0 | float | Fall damage threshold (higher = more fall tolerance) |
| `PhotoModeRangeLimit` | 3000 | int | Max photo mode camera distance |
| `PoopIntervalMultiplier` | 1.0 | float | Poop frequency |
| `PreventDinoTameClassNames` | — | string | Block taming per species classname |
| `PreventTransferForClassNames` | — | string | Block transfer per species classname |
| `TribeSlotReuseCooldown` | 0.0 | float | Seconds before tribe slot can be reused |

## Harvesting & Resources

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `CropDecaySpeedMultiplier` | 1.0 | float | Crop decay speed |
| `CropGrowthSpeedMultiplier` | 1.0 | float | Crop growth speed |
| `DinoHarvestingDamageMultiplier` | 3.2 | float | Tame harvesting damage |
| `PlayerHarvestingDamageMultiplier` | 1.0 | float | Player harvesting damage |
| `FuelConsumptionIntervalMultiplier` | 1.0 | float | Fuel consumption rate |
| `HairGrowthSpeedMultiplier` | 1.0 (ASE) / 0 (ASA) | float | Hair growth |
| `ResourceNoReplenishRadiusPlayers` | 1.0 | float | Resource no-grow radius around players |
| `ResourceNoReplenishRadiusStructures` | 1.0 | float | Resource no-grow radius around structures |
| `WirelessCraftingRangeOverride` | 3000 | int | Tek Dedicated Storage wireless range |
| `bDisableWirelessCrafting` | False | bool | Disable wireless crafting entirely |

## Decomposition & Spoiling

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `GlobalCorpseDecompositionTimeMultiplier` | 1.0 | float | Corpse decomposition time |
| `GlobalItemDecompositionTimeMultiplier` | 1.0 | float | Dropped item decomposition |
| `GlobalSpoilingTimeMultiplier` | 1.0 | float | Perishable spoiling time |
| `UseCorpseLifeSpanMultiplier` | 1.0 | float | Corpse/loot bag lifespan |

## PvP Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `bIncreasePvPRespawnInterval` | True | bool | Enable PvP respawn scaling |
| `IncreasePvPRespawnIntervalBaseAmount` | 60.0 | float | Base respawn penalty seconds |
| `IncreasePvPRespawnIntervalMultiplier` | 2.0 | float | Respawn penalty scale |
| `IncreasePvPRespawnIntervalCheckPeriod` | 300.0 | float | Check period seconds |
| `PvPZoneStructureDamageMultiplier` | 6.0 | float | Cave structure damage multiplier |
| `PreventOfflinePvPConnectionInvincibleInterval` | 5.0 | float | Login invincibility seconds |
| `bPvEAllowTribeWar` | True | bool | Allow PvE tribe wars |
| `bPvEDisableFriendlyFire` | False | bool | Disable friendly fire in PvE |

## Turret Limits

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `bLimitTurretsInRange` | True | bool | Enable turret range limit |
| `bHardLimitTurretsInRange` | False | bool | Retroactive hard limit |
| `LimitTurretsNum` | 100 | int | Max turrets in range |
| `LimitTurretsRange` | 10000.0 | float | Turret limit range (UU) |
| `DinoTurretDamageMultiplier` | 1.0 | float | Turret damage to creatures |
| `LimitGeneratorsNum` | 3 | int | Max generators in range |
| `LimitGeneratorsRange` | 15000 | int | Generator limit range (UU) |

## Structure Repair & Decay

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `StructureDamageRepairCooldown` | 180 | int | Repair cooldown after damage (0 = off) |
| `FastDecayInterval` | 43200 | int | Seconds for fast decay (requires `FastDecayUnsnappedCoreStructures`) |

## Supply Crate & Fishing

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `SupplyCrateLootQualityMultiplier` | 1.0 | float | Supply crate quality (1.0-5.0) |
| `FishingLootQualityMultiplier` | 1.0 | float | Fishing quality (1.0-5.0) |

## Creature Spawn Configuration

### ConfigAddNPCSpawnEntriesContainer

Add creatures to existing spawn areas:

```ini
ConfigAddNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="<spawn_class>",NPCSpawnEntries=((AnEntryName="<name>",EntryWeight=<float>,NPCsToSpawnStrings=("<entity_id>"))),NPCSpawnLimits=((NPCClassString="<entity_id>",MaxPercentageOfDesiredNumToAllow=<float>)))
```

### ConfigSubtractNPCSpawnEntriesContainer

Remove creatures from spawn areas:

```ini
ConfigSubtractNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="<spawn_class>",NPCSpawnEntries=((NPCsToSpawnStrings=("<entity_id>"))),NPCSpawnLimits=((NPCClassString="<entity_id>")))
```

### ConfigOverrideNPCSpawnEntriesContainer

Replace all creatures in a spawn area:

```ini
ConfigOverrideNPCSpawnEntriesContainer=(NPCSpawnEntriesContainerClassString="<spawn_class>",NPCSpawnEntries=((AnEntryName="<name>",EntryWeight=<float>,NPCsToSpawnStrings=("<entity_id>"))),NPCSpawnLimits=((NPCClassString="<entity_id>",MaxPercentageOfDesiredNumToAllow=<float>)))
```

### Advanced Spawn Arguments

| Argument | Description |
|----------|-------------|
| `NPCsSpawnOffsets=((X=,Y=,Z=))` | Spawn offset per creature |
| `NPCsToSpawnPercentageChance=(float,...)` | Spawn chance per creature |
| `ManualSpawnPointSpreadRadius=<float>` | Spread radius from spawn point |
| `NPCDifficultyLevelRanges=(...)` | Custom level ranges per difficulty |
| `RandGroupSpawnOffsetZMin/Max=<float>` | Height offset range |
| `NPCOverrideLevel=<int>` | Force specific level |
| `NPCMinLevelOffset/NPCMaxLevelOffset=<float>` | Level offset |
| `NPCMinLevelMultiplier/NPCMaxLevelMultiplier=<float>` | Level multiplier |

### DinoSpawnWeightMultipliers

```ini
DinoSpawnWeightMultipliers=(DinoNameTag=<tag>,SpawnWeightMultiplier=<float>,OverrideSpawnLimitPercentage=True,SpawnLimitPercentage=<float>)
```

### NPCReplacements

```ini
NPCReplacements=(FromClassName="<class>",ToClassName="<class>")
```

## Creature Stats Overrides

```ini
DinoClassDamageMultipliers=(ClassName="<class>",Multiplier=<float>)
DinoClassResistanceMultipliers=(ClassName="<class>",Multiplier=<float>)
TamedDinoClassDamageMultipliers=(ClassName="<class>",Multiplier=<float>)
TamedDinoClassResistanceMultipliers=(ClassName="<class>",Multiplier=<float>)
TamedDinoClassSpeedMultipliers=(ClassName="<class>",Multiplier=<float>)
TamedDinoClassStaminaMultipliers=(ClassName="<class>",Multiplier=<float>)
WildDinoCharacterFoodDrainMultiplier=<float>
TamedDinoCharacterFoodDrainMultiplier=<float>
WildDinoTorporDrainMultiplier=<float>
TamedDinoTorporDrainMultiplier=<float>
```

## Per-Level Stat Multipliers

Attribute indices: 0=Health, 1=Stamina, 2=Torpidity, 3=Oxygen, 4=Food, 5=Water, 6=Temp, 7=Weight, 8=Melee, 9=Speed, 10=Fortitude, 11=Crafting

```ini
PerLevelStatsMultiplier_Player[<index>]=<float>
PerLevelStatsMultiplier_DinoWild[<index>]=<float>
PerLevelStatsMultiplier_DinoTamed[<index>]=<float>
PerLevelStatsMultiplier_DinoTamed_Add[<index>]=<float>
PerLevelStatsMultiplier_DinoTamed_Affinity[<index>]=<float>
PlayerBaseStatMultipliers[<index>]=<float>
```

### Default PerLevelStatsMultiplier Values

| Attribute | Wild | Tamed | Tamed_Add | Tamed_Affinity |
|-----------|------|-------|-----------|----------------|
| 0 Health | 1 | 0.2 | 0.14 | 0.44 |
| 1 Stamina | 1 | 1 | 1 | 1 |
| 7 Weight | 1 | 1 | 1 | 1 |
| 8 Damage | 1 | 0.17 | 0.14 | 0.44 |
| 9 Speed | 1 | 1 | 1 | 1 |

## Engram Overrides

```ini
OverrideEngramEntries=(EngramIndex=<int>,EngramHidden=<bool>,EngramPointsCost=<int>,EngramLevelRequirement=<int>,RemoveEngramPreReq=<bool>)
OverrideNamedEngramEntries=(EngramClassName="<class>",EngramHidden=<bool>,EngramPointsCost=<int>,EngramLevelRequirement=<int>,RemoveEngramPreReq=<bool>)
EngramEntryAutoUnlocks=(EngramClassName="<class>",LevelToAutoUnlock=<int>)
bAutoUnlockAllEngrams=True
bOnlyAllowSpecifiedEngrams=False
OverridePlayerLevelEngramPoints=<int>  (repeat per level)
```

## Item Overrides

### ConfigOverrideItemCraftingCosts

```ini
ConfigOverrideItemCraftingCosts=(ItemClassString="<class>",BaseCraftingResourceRequirements=((ResourceItemTypeString="<class>",BaseResourceRequirement=<float>,bCraftingRequireExactResourceType=False)))
```

### ConfigOverrideItemMaxQuantity

```ini
ConfigOverrideItemMaxQuantity=(ItemClassString="<class>",Quantity=(MaxItemQuantity=<int>,bIgnoreMultiplier=<bool>))
```

### ConfigOverrideSupplyCrateItems

```ini
ConfigOverrideSupplyCrateItems=(SupplyCrateClassString="<class>",MinItemSets=<int>,MaxItemSets=<int>,NumItemSetsPower=<float>,bSetsRandomWithoutReplacement=<bool>,ItemSets=((MinNumItems=<int>,MaxNumItems=<int>,NumItemsPower=<float>,SetWeight=<float>,bItemsRandomWithoutReplacement=<bool>,ItemEntries=((EntryWeight=<float>,ItemClassStrings=("<class>"),ItemsWeights=(<float>),MinQuantity=<float>,MaxQuantity=<float>,MinQuality=<float>,MaxQuality=<float>,bForceBlueprint=<bool>,ChanceToBeBlueprintOverride=<float>)))))
```

Optional supply crate args: `bAppendItemSets=True` (add instead of replace), `bForcePreventGrinding=True`, `ItemStatClampsMultiplier=<float>`.

### ItemStatClamps

Requires `ClampItemStats=True`. Indices: 0=Quality, 1=Armor, 2=Durability, 3=Weapon Damage, 4=Clip Ammo, 5=Cold Insulation, 6=Weight, 7=Heat Insulation.

```ini
ItemStatClamps[1]=19800
ItemStatClamps[3]=19800
```

### HarvestResourceItemAmountClassMultipliers

```ini
HarvestResourceItemAmountClassMultipliers=(ClassName="PrimalItemResource_Thatch_C",Multiplier=2.0)
```

## Level Experience Override

```ini
LevelExperienceRampOverrides=(ExperiencePointsForLevel[0]=1,ExperiencePointsForLevel[1]=5,...,ExperiencePointsForLevel[64]=1000)
```

First occurrence = player levels. Second occurrence = creature levels. Include 100 extra levels for ascension/chibi/explorer.

## Genesis-Specific Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `bDisableGenesisMissions` | False | bool | Disable Genesis missions |
| `bDisableHexagonStore` | False | bool | Disable Hexagon store |
| `BaseHexagonRewardMultiplier` | 1.0 | float | Mission hexagon reward scale |
| `HexagonCostMultiplier` | 1.0 | float | Store cost scale |
| `bDisableDefaultMapItemSets` | False | bool | Disable Gen2 Tek Suit on spawn |
| `bDisableWorldBuffs` | False | bool | Disable Gen2 world effects |
| `bEnableWorldBuffScaling` | False | bool | Scale world effects by server settings |
| `AdjustableMutagenSpawnDelayMultiplier` | 1.0 | float | Mutagen spawn rate |
| `MutagenLevelBoost[<stat>]` | varies | int | Mutagen level points (wild ancestry) |
| `MutagenLevelBoost_Bred[<stat>]` | varies | int | Mutagen level points (bred) |

## Single Player Settings Multipliers

When `bUseSingleplayerSettings=True`, these additional multipliers apply on top of configured values:

| Option | Additional Multiplier |
|--------|----------------------|
| `BabyCuddleIntervalMultiplier` | x 0.17 |
| `BabyMatureSpeedMultiplier` | x 35.0 |
| `CropGrowthSpeedMultiplier` | x 4.0 |
| `EggHatchSpeedMultiplier` | x 9.0 |
| `MatingIntervalMultiplier` | x 0.15 |
| `TamingSpeedMultiplier` | x 2.5 |
| `RaidDinoCharacterFoodDrainMultiplier` | x 0.4 |

## [ModInstaller] Section

For ASE Steam Workshop auto-managed mods (requires `-automanagedmods`):

```ini
[ModInstaller]
ModIDS=123456789
ModIDS=987654321
```
