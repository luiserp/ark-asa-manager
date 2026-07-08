<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import echo from '@/echo';
import ServerDetailController from '@/actions/App/Http/Controllers/ServerDetailController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { index as serversIndex } from '@/routes/servers';
import { Play, Square, Download, RefreshCw, Loader2, Cpu, MemoryStick, HardDrive, Users, Clock, Terminal, ScrollText } from '@lucide/vue';

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

const status = ref<ServerStatus>({
    installed: false,
    running: false,
    pid: null,
    uptime: null,
});

const taskOutput = ref<string[]>([]);
const serverLogs = ref('');
const activeTask = ref<string | null>(null);
const loadingStatus = ref(true);
const loadingLogs = ref(false);
const liveOutputContainer = ref<HTMLElement | null>(null);

let statusPollInterval: ReturnType<typeof setInterval> | null = null;
let csrfToken = '';

const fetchHeaders = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
};

function requestStatus() {
    loadingStatus.value = true;
    fetch(ServerDetailController.status.url({ server: props.server.id }), {
        method: 'POST',
        headers: { ...fetchHeaders, 'X-CSRF-TOKEN': csrfToken },
    }).catch(() => {
        loadingStatus.value = false;
    });
}

function requestLogs() {
    loadingLogs.value = true;
    serverLogs.value = '';
    fetch(ServerDetailController.logs.url({ server: props.server.id }), {
        method: 'POST',
        headers: { ...fetchHeaders, 'X-CSRF-TOKEN': csrfToken },
    }).catch(() => {
        loadingLogs.value = false;
    });
}

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

onMounted(() => {
    csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    requestStatus();

    statusPollInterval = setInterval(() => {
        if (!activeTask.value) {
            requestStatus();
        }
    }, 30000);

    echo.private(`server.${props.server.id}`)
        .listen('.ServerStatusUpdated', (e: ServerStatus) => {
            status.value = e;
            loadingStatus.value = false;
        })
        .listen('.ServerTaskOutput', (e: { task: string; line: string; type: string }) => {
            if (e.task === 'logs') {
                serverLogs.value += (serverLogs.value ? '\n' : '') + e.line;
                loadingLogs.value = false;
            } else {
                taskOutput.value.push(e.line);
                scrollToBottom();
            }
        })
        .listen('.ServerTaskCompleted', (e: { task: string; success: boolean; exitCode: number }) => {
            if (e.task === 'logs') {
                loadingLogs.value = false;
                return;
            }

            const label = e.success ? 'completed successfully' : `failed (exit ${e.exitCode})`;
            taskOutput.value.push(`--- ${e.task} ${label} ---`);
            activeTask.value = null;
            scrollToBottom();

            setTimeout(() => requestStatus(), 2000);
        });
});

onUnmounted(() => {
    if (statusPollInterval) clearInterval(statusPollInterval);
    echo.leave(`server.${props.server.id}`);
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
                            {{ (status.memoryUsed / 1024).toFixed(1) }}
                            <span class="text-sm font-normal text-muted-foreground">/ {{ (status.memoryTotal / 1024).toFixed(0) }} GB</span>
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
                            <span v-if="status.maxPlayers" class="text-sm font-normal text-muted-foreground">/ {{ status.maxPlayers }}</span>
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
                    <div class="text-xl font-bold">
                        {{ status.uptime ?? '—' }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Action Groups -->
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <div class="flex items-center gap-2">
                <Button
                    :disabled="!!activeTask || !status.installed || status.running"
                    @click="dispatchTask('start')"
                >
                    <Loader2 v-if="activeTask === 'start'" class="mr-2 h-4 w-4 animate-spin" />
                    <Play v-else class="mr-2 h-4 w-4" />
                    Start
                </Button>

                <Button
                    variant="destructive"
                    :disabled="!!activeTask || !status.running"
                    @click="dispatchTask('stop')"
                >
                    <Loader2 v-if="activeTask === 'stop'" class="mr-2 h-4 w-4 animate-spin" />
                    <Square v-else class="mr-2 h-4 w-4" />
                    Stop
                </Button>
            </div>

            <Separator orientation="vertical" class="mx-1 h-8" />

            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    :disabled="!activeTask"
                    @click="dispatchTask('install')"
                >
                    <Loader2 v-if="activeTask === 'install'" class="mr-2 h-4 w-4 animate-spin" />
                    <Download v-else class="mr-2 h-4 w-4" />
                    {{ status.installed ? 'Update' : 'Install' }}
                </Button>
            </div>

            <Separator orientation="vertical" class="mx-1 h-8" />

            <Button variant="ghost" size="icon" @click="requestStatus" :disabled="loadingStatus">
                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loadingStatus }" />
            </Button>
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
                        Server Logs
                    </TabsTrigger>
                </TabsList>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="taskOutput.length > 0 && !activeTask"
                        variant="ghost"
                        size="sm"
                        @click="taskOutput = []"
                    >
                        Clear
                    </Button>
                    <Button variant="ghost" size="sm" @click="requestLogs" :disabled="loadingLogs">
                        <Loader2 v-if="loadingLogs" class="mr-2 h-3 w-3 animate-spin" />
                        <RefreshCw v-else class="mr-2 h-3 w-3" />
                        Fetch Logs
                    </Button>
                </div>
            </div>

            <TabsContent value="output" class="mt-2 min-h-0 flex-1">
                <div
                    ref="liveOutputContainer"
                    class="terminal-scroll h-full overflow-auto rounded-md border bg-black p-4 font-mono text-xs text-green-400"
                >
                    <div v-if="taskOutput.length > 0">
                        <div
                            v-for="(line, i) in taskOutput"
                            :key="i"
                            class="whitespace-pre-wrap"
                        >{{ line }}</div>
                    </div>
                    <p v-else class="text-gray-500">Waiting for task output...</p>
                </div>
            </TabsContent>

            <TabsContent value="logs" class="mt-2 min-h-0 flex-1">
                <div
                    class="terminal-scroll h-full overflow-auto rounded-md border bg-black p-4 font-mono text-xs text-green-400"
                >
                    <pre v-if="serverLogs" class="whitespace-pre-wrap">{{ serverLogs }}</pre>
                    <p v-else class="text-gray-500">Click "Fetch Logs" to load server logs.</p>
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
