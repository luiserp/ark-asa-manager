# ARK Server Command Line Options Reference

Source: https://ark.wiki.gg/wiki/Server_configuration

Options starting with `?` are appended directly after the map name. Options starting with `-` are separated by spaces.

## Active Events (`-ActiveEvent=<name>`)

ASA: Obsolete, use `-mods` instead. ASE only:

| Event Name | Event |
|------------|-------|
| `ark7th` | ARK: 7th Anniversary |
| `ARKaeology` | ARK: ARKaeology |
| `birthday` | ARK: 5th Anniversary |
| `Easter` | Eggcellent Adventure 7 |
| `FearEvolved` | Fear Evolved 6 |
| `ExtinctionChronicles` | Extinction Chronicles |
| `Summer` | Summer EVO |
| `TurkeyTrial` | Turkey Trial 6 |
| `vday` | Valentine's EVO |
| `WinterWonderland` | Winter Wonderland 7 |
| `None` | Disable all events |

## Server Options

| Option | Description |
|--------|-------------|
| `-AllowFlyerSpeedLeveling` | Enable flyer speed leveling (alt: `bAllowFlyerSpeedLeveling=True` in Game.ini) |
| `?AltSaveDirectoryName=<name>` | Custom save directory name for cluster management |
| `-AlwaysTickDedicatedSkeletalMeshes` | Disable idle creature animation optimization |
| `-AutoDestroyStructures` | Enable auto-destruction of old structures (timer via `AutoDestroyOldStructuresMultiplier` in INI) |
| `-automanagedmods` | Steam only, auto mod download/install/update via `[ModInstaller]` in Game.ini |
| `-culture=<lang_code>` | Override server output language |
| `-DoCustomCosmeticValidation` | Enable custom cosmetic mod validation |
| `-DisableCustomCosmetics` | Disable Custom Cosmetic system |
| `-disabledinonetrangescaling` | Disable creature network replication range optimization |
| `-DisableRailgunPVP` | Disable Tek Railgun on PvP |
| `-EasterColors` | Chance for Easter colors on wild spawns |
| `-EnableIdlePlayerKick` | Kick idle players after `KickIdlePlayersPeriod` |
| `-exclusivejoin` | Whitelist-only mode |
| `?EventColorsChanceOverride=<0-1>` | Override event color chance on freshly spawned wilds |
| `-ForceAllowCaveFlyers` | Allow flyers in caves |
| `-ForceRespawnDinos` | Destroy all wild creatures on start |
| `-GBUsageToForceRestart=<GB>` | OOM restart threshold (default 35, 0 = off) |
| `-imprintlimit=<percent>` | Auto-destroy creatures exceeding imprint bonus limit |
| `-insecure` | Disable VAC |
| `-MaxNumOfSaveBackups=<n>` | Max backup saves (default 20) |
| `-MinimumTimeBetweenInventoryRetrieval=<sec>` | Fjordhawk retrieval cooldown (default 3600) |
| `-mods=<id>[,<id>...]` | ASA CurseForge mod IDs, auto-updated |
| `-MULTIHOME` | Enable multihoming (set IP in `[SessionSettings]`) |
| `-newsaveformat` | Enable save format v11 (faster, smaller cryopods) |
| `-NoBattlEye` | Run without BattlEye |
| `-NoDinos` | Prevent all wild creature spawns |
| `-NoHangDetection` | Prevent shutdown if startup > 45 min |
| `-NotifyAdminCommandsInChat` | Show cheat commands to admins in chat |
| `-noundermeshchecking` | Disable anti-meshing entirely |
| `-noundermeshkilling` | Disable anti-mesh kills (keep teleporting) |
| `-NoWildBabies` | Disable wild baby spawns (ASA only) |
| `-passivemods=<id>[,<id>...]` | Load mod data without activating |
| `-port=<port>` | Server port (default 7777) |
| `-pvedisallowtribewar` | Prevent PvE tribe wars |
| `-SecureSendArKPayload` | Protect against creature upload exploit |
| `-servergamelog` | Enable server admin logs + RCON log support |
| `-servergamelogincludetribelogs` | Include tribe logs in server logs |
| `-ServerRCONOutputTribeLogs` | Include tribe logs in RCON output |
| `-ServerPlatform=<platforms>` | `PC`, `PS5`, `XSX`, `WINGDK`, `ALL` (crossplay) |
| `-StasisKeepControllers` | Keep AI controllers in memory during stasis |
| `-structurememopts` | Structure memory optimizations (may break snap-point mods) |
| `-UseDynamicConfig` | Enable dynamic config live tuning |
| `-UseItemDupeCheck` | Additional dupe protection |
| `-UseSecureSpawnRules` | Additional spawn protection |
| `-usestore` | Official-style player data handling |
| `-UseStructureStasisGrid` | Structure stasis grid for large base performance |
| `-webalarm` | Web notifications for tribe events |
| `-WinLiveMaxPlayers=<n>` | Max players (default 70, replaces INI `MaxPlayers`) |

## Cross-ARK Transfer Options

| Option | Description |
|--------|-------------|
| `-clusterid=<name>` | Cluster name ID for Cross-ARK transfers |
| `-ClusterDirOverride=<path>` | Cross-server storage location |
| `-NoTransferFromFiltering` | Prevent ARK Data between single player and non-cluster servers |

## Undocumented Options (use at own risk)

| Option | Description |
|--------|-------------|
| `-AllowChatSpam` | Allow chat spam |
| `-BackupTransferPlayerDatas` | Full official-style character profile backups (requires `-usestore`) |
| `-converttostore` | Convert saves to stored data format |
| `-CustomMerticsURL=<url>` | Custom server metrics URL (HTTP only) |
| `-dedihibernation` | Force wild spawns in hibernation mode |
| `-disableCharacterTracker` | Disable character tracking |
| `-MaxConnectionsPerIP=<n>` | Max connections per IP (default -1 = unlimited) |
| `-nitradotest2` | Limit tick rate to 3 with no players |
| `-NoAI` | Disable AI controllers for creatures |
| `-NoDinosExceptForcedSpawn` | Only allow forced spawns |
| `-NoDinosExceptWaterSpawn` | Only allow water spawns |
| `-nosound` | Disable sounds for performance |
| `-onethread` | Disable multithreading |
| `-parseservertojson` | Dump server save to JSON at startup |
| `-UseTameEffectivenessClamp` | Prevent taming effectiveness > 100% |
| `-UseServerNetSpeedCheck` | Limit player movement data per tick |
