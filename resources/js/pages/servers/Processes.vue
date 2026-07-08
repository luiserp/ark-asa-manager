<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ServerProcessController from '@/actions/App/Http/Controllers/ServerProcessController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as serversIndex } from '@/routes/servers';
import { RefreshCw, Skull, ChevronDown, ArrowUpDown, Cpu, MemoryStick, HardDrive } from '@lucide/vue';

interface Process {
    pid: number;
    user: string;
    cpu: number;
    mem: number;
    rss: number;
    stat: string;
    start: string;
    time: string;
    command: string;
}

interface Stats {
    cpu: string | null;
    memory: string | null;
    disk: string | null;
}

const props = defineProps<{
    server: { id: string; name: string };
    processes: Process[];
    stats: Stats;
    error: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servers', href: serversIndex() },
            { title: 'Processes', href: '#' },
        ],
    },
});

const page = usePage();
const csrfToken = (page.props as any)._token
    ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    ?? '';

const search = ref('');
const sortKey = ref<keyof Process>('mem');
const sortAsc = ref(false);
const killTarget = ref<Process | null>(null);
const killing = ref(false);

type SortableKey = 'pid' | 'user' | 'cpu' | 'mem' | 'rss' | 'command';

function toggleSort(key: SortableKey) {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = false;
    }
}

const filteredProcesses = computed(() => {
    let list = [...props.processes];

    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(p =>
            p.command.toLowerCase().includes(q) ||
            p.user.toLowerCase().includes(q) ||
            String(p.pid).includes(q),
        );
    }

    list.sort((a, b) => {
        const av = a[sortKey.value];
        const bv = b[sortKey.value];
        if (typeof av === 'number' && typeof bv === 'number') {
            return sortAsc.value ? av - bv : bv - av;
        }
        return sortAsc.value
            ? String(av).localeCompare(String(bv))
            : String(bv).localeCompare(String(av));
    });

    return list;
});

const refreshing = ref(false);

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['processes', 'error'],
        onFinish: () => { refreshing.value = false; },
    });
}

function formatRss(kb: number): string {
    if (kb < 1024) return `${kb} KB`;
    if (kb < 1048576) return `${(kb / 1024).toFixed(1)} MB`;
    return `${(kb / 1048576).toFixed(1)} GB`;
}

async function killProcess(signal: 'TERM' | 'KILL') {
    if (!killTarget.value) return;
    killing.value = true;

    try {
        const url = ServerProcessController.kill.url({ server: props.server.id });
        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ pid: killTarget.value.pid, signal }),
        });

        if (res.ok) {
            killTarget.value = null;
            refresh();
        }
    } finally {
        killing.value = false;
    }
}


</script>

<template>
    <Head :title="`Processes - ${server.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Processes" :description="`Running processes on ${server.name}`" />
            <Button variant="outline" size="sm" class="cursor-pointer" :disabled="refreshing" @click="refresh">
                <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': refreshing }" />
                Refresh
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">CPU</CardTitle>
                    <Cpu class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">{{ stats.cpu ?? '—' }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Memory</CardTitle>
                    <MemoryStick class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">{{ stats.memory ?? '—' }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Disk</CardTitle>
                    <HardDrive class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold">{{ stats.disk ?? '—' }}</div>
                </CardContent>
            </Card>
        </div>

        <div class="flex items-center gap-4">
            <Input
                v-model="search"
                placeholder="Filter by command, user, or PID..."
                class="max-w-sm"
            />
            <span class="text-sm text-muted-foreground">
                {{ filteredProcesses.length }} of {{ processes.length }} processes
            </span>
        </div>

        <div v-if="error" class="rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive">
            {{ error }}
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-20 cursor-pointer select-none" @click="toggleSort('pid')">
                            <span class="flex items-center gap-1">PID <ArrowUpDown class="h-3 w-3" /></span>
                        </TableHead>
                        <TableHead class="cursor-pointer select-none" @click="toggleSort('user')">
                            <span class="flex items-center gap-1">User <ArrowUpDown class="h-3 w-3" /></span>
                        </TableHead>
                        <TableHead class="w-20 cursor-pointer select-none" @click="toggleSort('cpu')">
                            <span class="flex items-center gap-1">CPU% <ArrowUpDown class="h-3 w-3" /></span>
                        </TableHead>
                        <TableHead class="w-20 cursor-pointer select-none" @click="toggleSort('mem')">
                            <span class="flex items-center gap-1">MEM% <ArrowUpDown class="h-3 w-3" /></span>
                        </TableHead>
                        <TableHead class="w-24 cursor-pointer select-none" @click="toggleSort('rss')">
                            <span class="flex items-center gap-1">RSS <ArrowUpDown class="h-3 w-3" /></span>
                        </TableHead>
                        <TableHead class="cursor-pointer select-none" @click="toggleSort('command')">
                            <span class="flex items-center gap-1">Command <ArrowUpDown class="h-3 w-3" /></span>
                        </TableHead>
                        <TableHead class="w-16" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="proc in filteredProcesses"
                        :key="proc.pid"
                        :class="{
                            'bg-red-50/50 dark:bg-red-950/20': proc.cpu > 80 || proc.mem > 50,
                            'bg-amber-50/50 dark:bg-amber-950/20': (proc.cpu > 30 || proc.mem > 20) && proc.cpu <= 80 && proc.mem <= 50,
                        }"
                    >
                        <TableCell class="font-mono text-xs">{{ proc.pid }}</TableCell>
                        <TableCell class="text-xs">{{ proc.user }}</TableCell>
                        <TableCell class="font-mono text-xs" :class="{ 'font-bold text-red-600 dark:text-red-400': proc.cpu > 50 }">
                            {{ proc.cpu.toFixed(1) }}
                        </TableCell>
                        <TableCell class="font-mono text-xs" :class="{ 'font-bold text-red-600 dark:text-red-400': proc.mem > 30 }">
                            {{ proc.mem.toFixed(1) }}
                        </TableCell>
                        <TableCell class="font-mono text-xs">{{ formatRss(proc.rss) }}</TableCell>
                        <TableCell class="max-w-xs truncate font-mono text-xs" :title="proc.command">
                            {{ proc.command }}
                        </TableCell>
                        <TableCell>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-7 w-7"
                                title="Kill process"
                                @click="killTarget = proc"
                            >
                                <Skull class="h-3.5 w-3.5 text-destructive" />
                            </Button>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="filteredProcesses.length === 0">
                        <TableCell colspan="7" class="py-8 text-center text-muted-foreground">
                            No processes found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>

    <AlertDialog :open="!!killTarget" @update:open="killTarget = null">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Kill process?</AlertDialogTitle>
                <AlertDialogDescription>
                    <span class="block">
                        PID <strong class="font-mono">{{ killTarget?.pid }}</strong> —
                        <span class="font-mono text-xs">{{ killTarget?.command }}</span>
                    </span>
                    <span class="mt-1 block text-xs text-muted-foreground">
                        Choose SIGTERM for graceful shutdown or SIGKILL to force terminate immediately.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="killing">Cancel</AlertDialogCancel>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="destructive" :disabled="killing">
                            Kill
                            <ChevronDown class="ml-2 h-3 w-3" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="killProcess('TERM')">
                            SIGTERM (graceful)
                        </DropdownMenuItem>
                        <DropdownMenuItem class="text-destructive" @click="killProcess('KILL')">
                            SIGKILL (force)
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

