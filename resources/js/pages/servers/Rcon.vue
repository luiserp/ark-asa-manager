<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import ServerRconController from '@/actions/App/Http/Controllers/ServerRconController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as serversIndex } from '@/routes/servers';
import { Send, Terminal, Loader2, Play } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servers', href: serversIndex() },
            { title: 'RCON Console', href: '#' },
        ],
    },
});

interface RconCommand {
    id: string;
    name: string;
    command: string;
    category: string;
    description: string | null;
}

const props = defineProps<{
    server: {
        id: string;
        name: string;
        host: string;
        port: number;
    };
    commands: RconCommand[];
}>();

interface OutputEntry {
    type: 'command' | 'response' | 'error';
    text: string;
    timestamp: string;
}

const commandInput = ref('');
const sending = ref(false);
const output = ref<OutputEntry[]>([]);
const outputContainer = ref<HTMLElement | null>(null);

let csrfToken = '';
if (typeof document !== 'undefined') {
    csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

function timestamp(): string {
    return new Date().toLocaleTimeString();
}

function scrollToBottom() {
    nextTick(() => {
        if (outputContainer.value) {
            outputContainer.value.scrollTop = outputContainer.value.scrollHeight;
        }
    });
}

async function sendCommand(command?: string) {
    const cmd = command ?? commandInput.value.trim();
    if (!cmd || sending.value) return;

    output.value.push({ type: 'command', text: `> ${cmd}`, timestamp: timestamp() });
    scrollToBottom();

    if (!command) {
        commandInput.value = '';
    }

    sending.value = true;

    try {
        const response = await fetch(
            ServerRconController.execute.url({ server: props.server.id }),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ command: cmd }),
            },
        );

        const data = await response.json();

        if (response.ok) {
            output.value.push({ type: 'response', text: data.response, timestamp: timestamp() });
        } else {
            output.value.push({ type: 'error', text: data.error ?? 'Unknown error', timestamp: timestamp() });
        }
    } catch (e) {
        output.value.push({ type: 'error', text: 'Network error: failed to reach server', timestamp: timestamp() });
    } finally {
        sending.value = false;
        scrollToBottom();
    }
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendCommand();
    }
}

function useQuickCommand(command: RconCommand) {
    commandInput.value = command.command;
}

const categories: Record<string, string> = {
    server: 'Server',
    players: 'Players',
    dinos: 'Dinos',
    world: 'World',
    info: 'Info',
};
</script>

<template>
    <Head :title="`${server.name} - RCON Console`" />

    <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between">
            <Heading title="RCON Console" :description="`${server.host} · Remote server administration`" />
            <Badge variant="outline" class="gap-1.5">
                <Terminal class="h-3 w-3" />
                RCON
            </Badge>
        </div>

        <!-- Command Bar -->
        <div class="flex shrink-0 items-center gap-2">
            <div class="relative flex-1">
                <Terminal class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="commandInput"
                    placeholder="Type an RCON command..."
                    class="pl-10 font-mono"
                    :disabled="sending"
                    @keydown="handleKeydown"
                />
            </div>
            <Button :disabled="!commandInput.trim() || sending" @click="sendCommand()">
                <Loader2 v-if="sending" class="mr-2 h-4 w-4 animate-spin" />
                <Send v-else class="mr-2 h-4 w-4" />
                Send
            </Button>
        </div>

        <!-- Two Column Layout -->
        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-3">
            <!-- Command Output (2/3 width) -->
            <Card class="flex min-h-0 flex-col lg:col-span-2">
                <CardHeader class="shrink-0 pb-3">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-sm font-medium">Output</CardTitle>
                        <Button
                            v-if="output.length > 0"
                            variant="ghost"
                            size="sm"
                            @click="output = []"
                        >
                            Clear
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="min-h-0 flex-1 pb-4">
                    <div
                        ref="outputContainer"
                        class="terminal-scroll h-full overflow-auto rounded-md border bg-black p-4 font-mono text-xs"
                    >
                        <div v-if="output.length > 0" class="space-y-1">
                            <div
                                v-for="(entry, i) in output"
                                :key="i"
                                class="whitespace-pre-wrap"
                                :class="{
                                    'text-cyan-400': entry.type === 'command',
                                    'text-green-400': entry.type === 'response',
                                    'text-red-400': entry.type === 'error',
                                }"
                            >
                                <span class="mr-2 text-gray-600">{{ entry.timestamp }}</span>
                                {{ entry.text }}
                            </div>
                        </div>
                        <p v-else class="text-gray-500">
                            RCON console ready. Type a command or use a quick action to get started.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Commands (1/3 width) -->
            <Card class="flex min-h-0 flex-col">
                <CardHeader class="shrink-0 pb-3">
                    <CardTitle class="text-sm font-medium">Quick Commands</CardTitle>
                </CardHeader>
                <CardContent class="terminal-scroll min-h-0 flex-1 overflow-auto pb-4">
                    <div class="space-y-4">
                        <div v-for="(label, key) in categories" :key="key">
                            <template v-if="commands.filter(c => c.category === key).length > 0">
                                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                    {{ label }}
                                </h4>
                                <Table>
                                    <TableBody>
                                        <TableRow
                                            v-for="cmd in commands.filter(c => c.category === key)"
                                            :key="cmd.id"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="useQuickCommand(cmd)"
                                        >
                                            <TableCell class="py-2">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-medium">{{ cmd.name }}</p>
                                                        <p v-if="cmd.description" class="truncate text-xs text-muted-foreground">
                                                            {{ cmd.description }}
                                                        </p>
                                                    </div>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        class="h-7 w-7 shrink-0"
                                                        :disabled="sending"
                                                        title="Execute command"
                                                        @click.stop="sendCommand(cmd.command)"
                                                    >
                                                        <Play class="h-3 w-3" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
