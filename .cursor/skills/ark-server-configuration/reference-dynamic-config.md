# ARK DynamicConfig Reference

Source: https://ark.wiki.gg/wiki/Server_configuration

## Setup

1. Add `-UseDynamicConfig` to server command line
2. Set `CustomDynamicConfigUrl=<url>` in `[ServerSettings]` of `GameUserSettings.ini`
3. Host the `.ini` file on a web server (HTTP for ASE, HTTP/HTTPS for ASA)
4. Serve with MIME type `text/plain`

The config is fetched on every autosave. Force immediate update with `ForceUpdateDynamicConfig` console command.

## Available Settings

### Rate Multipliers

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `XPMultiplier` | 1.0 | float | Global XP scaling |
| `TamingSpeedMultiplier` | 1.0 | float | Taming speed |
| `HarvestAmountMultiplier` | 1.0 | float | Harvest yield |
| `MatingIntervalMultiplier` | 1.0 | float | Mating interval |
| `MatingSpeedMultiplier` | 1.0 | float | Mating speed |
| `BabyMatureSpeedMultiplier` | 1.0 | float | Baby maturation |
| `EggHatchSpeedMultiplier` | 1.0 | float | Egg hatch speed |
| `BabyCuddleIntervalMultiplier` | 1.0 | float | Imprint care frequency |
| `BabyImprintAmountMultiplier` | 1.0 | float | Imprint % per care |
| `BabyFoodConsumptionSpeedMultiplier` | 1.0 | float | Baby food drain |
| `CropGrowthSpeedMultiplier` | 1.0 | float | Crop growth |
| `CustomRecipeEffectivenessMultiplier` | 1.0 | float | Custom recipe effectiveness |
| `GlobalSpoilingTimeMultiplier` | 1.0 | float | Spoiling times |
| `HexagonRewardMultiplier` | 1.0 | float | Hexagon rewards |
| `DinoCharacterFoodDrainMultiplier` | 1.0 | float | Creature food drain |
| `StructureDamageMultiplier` | 1.0 | float | Structure damage |

### Decay Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `bDisableDinoDecayPvE` | False | bool | Disable creature decay PvE |
| `bDisableStructureDecayPvE` | False | bool | Disable structure decay PvE |
| `bPvPDinoDecay` | False | bool | Creature decay in PvP (ORP) |
| `bPvPStructureDecay` | False | bool | Structure decay in PvP (ORP) |
| `PvEDinoDecayPeriodMultiplier` | 1.0 | float | PvE creature decay scale |
| `PvEStructureDecayPeriodMultiplier` | 1.0 | float | PvE structure decay scale |

### Transfer Settings

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `TributeDinoExpirationSeconds` | 86400 | int | Uploaded creature expiry |
| `TributeItemExpirationSeconds` | 86400 | int | Uploaded item expiry |

### Event Colors

| Option | Type | Description |
|--------|------|-------------|
| `ActiveEventColors` | string | Activate event color palette on wild spawns |
| `DynamicColorset` | string | Comma-separated color names (requires `ActiveEventColors=custom`) |
| `DynamicColorsetChanceOverride` | float | Color override chance 0.0-1.0 (default 0.25) |

#### Available Event Color Names

| Event Name | Event |
|------------|-------|
| `easter` | Eggcellent Adventure 7 |
| `FearEvolved` | Fear Evolved 6 |
| `PAX` | PAX Party |
| `Summer` | Summer EVO |
| `TurkeyTrial` | Turkey Trial 6 |
| `vday` | Valentine's EVO |
| `WinterWonderland` | Winter Wonderland 7 |
| `custom` | Custom colors (define in `DynamicColorset`) |

### Creature Replacements

Use the DynamicConfig-specific syntax (extra brackets, all in one line):

```ini
NPCReplacements=((FromClassName="MegaRaptor_Character_BP_C",ToClassName="Dodo_Character_BP_C"),(FromClassName="Coel_Character_BP_C",ToClassName="Piranha_Character_BP_C"))
```

### Genesis World Buffs

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `EnableWorldBuffScaling` | False | bool | Scale world effects by server settings |
| `WorldBuffScalingEfficacy` | 1.0 | float | Scaling effectiveness multiplier |
| `DisableWorldBuffs` | — | string | Comma-separated buff IDs to disable |

Known buff IDs: `MATINGINTERVAL_DOWN_HARD`, `MATINGINTERVAL_DOWN_MEDIUM`, `MATINGINTERVAL_DOWN_EASY`, `BABYMATURE_BOON_EASY`, `BABYMATURE_BOON_MEDIUM`, `BABYMATURE_BOON_HARD`

### Undocumented/Internal Options

| Option | Default | Type | Description |
|--------|---------|------|-------------|
| `bUseAlarmNotifications` | — | bool | Toggle web alarm notifications |
| `DisableTimestampVerification` | False | bool | Unknown |
| `DynamicUndermeshRegions` | — | string | Force dynamic undermesh volume update |
| `EnableFullDump` | False | bool | Full memory dump on crash |
| `GMaxFlameThrowerServerTicksPerFrame` | 5 | int | Flamethrower tick rate per frame |
| `GUseServerNetSpeedCheck` | False | bool | Limit player movement data per tick |
