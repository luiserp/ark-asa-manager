<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import echo from '@/echo';
import ServerDetailController from '@/actions/App/Http/Controllers/ServerDetailController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { index as serversIndex } from '@/routes/servers';
import { Play, Square, Download, Loader2, Cpu, MemoryStick, HardDrive, Users, Clock, Terminal, ScrollText, MonitorCog, Eye } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servers', href: serversIndex() },
            { title: 'Details', href: '#' },
        ],
    },
});

const props = defineProps<{
    server: {
        id: string;
        name: string;
        host: string;
        port: number;
    };
}>();

interface ServerStatus {
    installed: boolean;
    running: boolean;
    pid: number | null;
    uptime: string | null;
    error?: string | null;
    cpuPercent?: number | null;
    memoryUsed?: number | null;
    memoryTotal?: number | null;
    diskUsage?: string | null;
    players?: number | null;
    maxPlayers?: number | null;
}

interface PresenceUser {
    id: number;
    name: string;
}

const status = ref<ServerStatus>({
    installed: false,
    running: false,
    pid: null,
    uptime: null,
});

const taskOutput = ref<string[]>([]);
const serverLogs = ref('');
const consoleLogs = ref('');
const activeTask = ref<string | null>(null);
const loadingStatus = ref(true);
const liveOutputContainer = ref<HTMLElement | null>(null);
const viewers = ref<PresenceUser[]>([]);

const uptimeSeconds = ref(0);
let uptimeInterval: ReturnType<typeof setInterval> | null = null;

function parseUptimeToSeconds(uptime: string): number {
    const parts = uptime.trim().split(/[-:]/).reverse();
    let seconds = 0;
    if (parts[0]) seconds += parseInt(parts[0]);
    if (parts[1]) seconds += parseInt(parts[1]) * 60;
    if (parts[2]) seconds += parseInt(parts[2]) * 3600;
    if (parts[3]) seconds += parseInt(parts[3]) * 86400;
    return seconds;
}

function formatUptime(totalSeconds: number): string {
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const secs = totalSeconds % 60;

    if (days > 0) {
        return `${days}d ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

const displayUptime = computed(() => {
    if (!status.value.running || uptimeSeconds.value === 0) return '—';
    return formatUptime(uptimeSeconds.value);
});

function syncUptime(uptime: string | null) {
    if (!uptime) {
        uptimeSeconds.value = 0;
        return;
    }
    uptimeSeconds.value = parseUptimeToSeconds(uptime);
}

function startUptimeTicker() {
    if (uptimeInterval) return;
    uptimeInterval = setInterval(() => {
        if (status.value.running && uptimeSeconds.value > 0) {
            uptimeSeconds.value++;
        }
    }, 1000);
}

function stopUptimeTicker() {
    if (uptimeInterval) {
        clearInterval(uptimeInterval);
        uptimeInterval = null;
    }
}

let csrfToken = '';

const fetchHeaders = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
};

function dispatchTask(task: string) {
    activeTask.value = task;
    taskOutput.value = [];

    const urlMap: Record<string, string> = {
        install: ServerDetailController.install.url({ server: props.server.id }),
        start: ServerDetailController.start.url({ server: props.server.id }),
        stop: ServerDetailController.stop.url({ server: props.server.id }),
    };

    fetch(urlMap[task], {
        method: 'POST',
        headers: { ...fetchHeaders, 'X-CSRF-TOKEN': csrfToken },
    });
}


function scrollToBottom() {
    nextTick(() => {
        if (liveOutputContainer.value) {
            liveOutputContainer.value.scrollTop = liveOutputContainer.value.scrollHeight;
        }
    });
}

let currentChannelId: string | null = null;

function notifyLeave(serverId?: string) {
    const id = serverId ?? currentChannelId ?? props.server.id;
    const url = ServerDetailController.leave.url({ server: id });
    const data = new FormData();
    data.append('_token', csrfToken);
    navigator.sendBeacon(url, data);
}

function leaveChannel() {
    if (!currentChannelId) return;
    stopUptimeTicker();
    notifyLeave(currentChannelId);
    echo.leave(`server.${currentChannelId}`);
    currentChannelId = null;
}

function joinChannel(serverId: string) {
    leaveChannel();
    currentChannelId = serverId;

    status.value = { installed: false, running: false, pid: null, uptime: null };
    taskOutput.value = [];
    serverLogs.value = '';
    consoleLogs.value = '';
    activeTask.value = null;
    loadingStatus.value = true;
    uptimeSeconds.value = 0;

    echo.join(`server.${serverId}`)
        .here((users: PresenceUser[]) => {
            viewers.value = users;
        })
        .joining((user: PresenceUser) => {
            viewers.value.push(user);
        })
        .leaving((user: PresenceUser) => {
            viewers.value = viewers.value.filter(v => v.id !== user.id);
        })
        .listen('.ServerStatusUpdated', (e: ServerStatus) => {
            status.value = e;
            loadingStatus.value = false;
            syncUptime(e.uptime);
            if (e.running) {
                startUptimeTicker();
            } else {
                stopUptimeTicker();
                uptimeSeconds.value = 0;
            }
        })
        .listen('.ServerLogsUpdated', (e: { lines: string }) => {
            serverLogs.value = e.lines;
        })
        .listen('.ServerConsoleUpdated', (e: { lines: string }) => {
            consoleLogs.value = e.lines;
        })
        .listen('.ServerTaskOutput', (e: { task: string; line: string; type: string }) => {
            taskOutput.value.push(e.line);
            scrollToBottom();
        })
        .listen('.ServerTaskCompleted', (e: { task: string; success: boolean; exitCode: number }) => {
            const label = e.success ? 'completed successfully' : `failed (exit ${e.exitCode})`;
            taskOutput.value.push(`--- ${e.task} ${label} ---`);
            activeTask.value = null;
            scrollToBottom();
        });
}

watch(() => props.server.id, (newId, oldId) => {
    if (newId !== oldId) {
        joinChannel(newId);
    }
});

onMounted(() => {
    csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    joinChannel(props.server.id);
    window.addEventListener('beforeunload', () => notifyLeave());
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', () => notifyLeave());
    leaveChannel();
});
</script>

<template>

    <Head :title="`${server.name} - Details`" />

    <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-hidden p-4">
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between">
            <Heading :title="server.name" :description="`${server.host}:${server.port}`" />
            <div class="flex items-center gap-2">
                <Loader2 v-if="loadingStatus" class="h-4 w-4 animate-spin text-muted-foreground" />
                <Badge v-if="status.error" variant="destructive">Unreachable</Badge>
                <Badge v-else-if="status.running" variant="default" class="bg-green-600">
                    Running (PID {{ status.pid }})
                </Badge>
                <Badge v-else-if="status.installed" variant="secondary">Stopped</Badge>
                <Badge v-else variant="outline">Not Installed</Badge>

                <Badge v-if="activeTask" variant="default">
                    <Loader2 class="mr-1 h-3 w-3 animate-spin" />
                    {{ activeTask }}...
                </Badge>

                <Badge v-if="viewers.length > 0" variant="outline" class="gap-1">
                    <Eye class="h-3 w-3" />
                    {{ viewers.length }}
                </Badge>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid shrink-0 grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">CPU</CardTitle>
                    <Cpu class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">
                        {{ status.cpuPercent != null ? `${status.cpuPercent}%` : '—' }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Memory</CardTitle>
                    <MemoryStick class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">
                        <template v-if="status.memoryUsed != null && status.memoryTotal != null">
                            {{ status.memoryUsed.toLocaleString('de-DE') }}
                            <span class="text-sm font-normal text-muted-foreground">/ {{
                                status.memoryTotal.toLocaleString('de-DE') }} MB</span>
                        </template>
                        <template v-else>—</template>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Disk</CardTitle>
                    <HardDrive class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">
                        {{ status.diskUsage ?? '—' }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Players</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">
                        <template v-if="status.players != null">
                            {{ status.players }}
                            <span v-if="status.maxPlayers" class="text-sm font-normal text-muted-foreground">/ {{
                                status.maxPlayers }}</span>
                        </template>
                        <template v-else>—</template>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Uptime</CardTitle>
                    <Clock class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold tabular-nums">
                        {{ displayUptime }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Action Groups -->
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <div class="flex items-center gap-2">
                <Button :disabled="!!activeTask || !status.installed || status.running" @click="dispatchTask('start')">
                    <Loader2 v-if="activeTask === 'start'" class="mr-2 h-4 w-4 animate-spin" />
                    <Play v-else class="mr-2 h-4 w-4" />
                    Start
                </Button>

                <Button variant="destructive" :disabled="!!activeTask || !status.running" @click="dispatchTask('stop')">
                    <Loader2 v-if="activeTask === 'stop'" class="mr-2 h-4 w-4 animate-spin" />
                    <Square v-else class="mr-2 h-4 w-4" />
                    Stop
                </Button>
            </div>

            <Separator orientation="vertical" class="mx-1 h-8" />

            <div class="flex items-center gap-2">
                <Button variant="outline" :disabled="!!activeTask" @click="dispatchTask('install')">
                    <Loader2 v-if="activeTask === 'install'" class="mr-2 h-4 w-4 animate-spin" />
                    <Download v-else class="mr-2 h-4 w-4" />
                    {{ status.installed ? 'Update' : 'Install' }}
                </Button>
            </div>
        </div>

        <!-- Tabbed Output Panel -->
        <Tabs default-value="output" class="flex min-h-0 flex-1 flex-col">
            <div class="flex shrink-0 items-center justify-between">
                <TabsList>
                    <TabsTrigger value="output">
                        <Terminal class="mr-2 h-4 w-4" />
                        Live Output
                    </TabsTrigger>
                    <TabsTrigger value="logs">
                        <ScrollText class="mr-2 h-4 w-4" />
                        Engine Logs
                    </TabsTrigger>
                    <TabsTrigger value="console">
                        <MonitorCog class="mr-2 h-4 w-4" />
                        Console
                    </TabsTrigger>
                </TabsList>

                <div class="flex items-center gap-2">
                    <Button v-if="taskOutput.length > 0 && !activeTask" variant="ghost" size="sm"
                        @click="taskOutput = []">
                        Clear
                    </Button>
                </div>
            </div>

            <TabsContent value="output" class="mt-2 min-h-0 flex-1">
                <div ref="liveOutputContainer"
                    class="terminal-scroll h-full overflow-auto rounded-md border bg-black p-4 font-mono text-xs text-green-400">
                    <div v-if="taskOutput.length > 0">
                        <div v-for="(line, i) in taskOutput" :key="i" class="whitespace-pre-wrap">{{ line }}</div>
                    </div>
                    <p v-else class="text-gray-500">Waiting for task output...</p>
                </div>
            </TabsContent>

            <TabsContent value="logs" class="mt-2 min-h-0 flex-1">
                <div
                    class="terminal-scroll h-full overflow-auto rounded-md border bg-black p-4 font-mono text-xs text-green-400">
                    <pre v-if="serverLogs" class="whitespace-pre-wrap">{{ serverLogs }}</pre>
                    <p v-else class="text-gray-500">Waiting for engine logs...</p>
                </div>
            </TabsContent>

            <TabsContent value="console" class="mt-2 min-h-0 flex-1">
                <div
                    class="terminal-scroll h-full overflow-auto rounded-md border bg-black p-4 font-mono text-xs text-amber-400">
                    <pre v-if="consoleLogs" class="whitespace-pre-wrap">{{ consoleLogs }}</pre>
                    <p v-else class="text-gray-500">Waiting for console output...</p>
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
