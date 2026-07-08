<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Editor as MonacoEditor } from '@guolao/vue-monaco-editor';
import ServerFilesController from '@/actions/App/Http/Controllers/ServerFilesController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as serversIndex } from '@/routes/servers';
import { Folder, File, FileSymlink, ArrowUp, Download, Pencil, Loader2, Save } from '@lucide/vue';

interface FileEntry {
    name: string;
    type: 'directory' | 'file' | 'link';
    size: string;
    modified: string;
    permissions: string;
    path: string;
}

const props = defineProps<{
    server: { id: string; name: string };
    currentPath: string;
    basePath: string;
    entries: FileEntry[];
    error: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servers', href: serversIndex() },
            { title: 'Files', href: '#' },
        ],
    },
});

const page = usePage();
const csrfToken = (page.props as any)._token
    ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    ?? '';

const EDITABLE_EXTENSIONS = ['ini', 'cfg', 'conf', 'txt', 'log', 'json', 'xml', 'yaml', 'yml', 'sh', 'bat', 'env', 'toml', 'md'];

const isAtBase = computed(() => props.currentPath === props.basePath);

const parentPath = computed(() => {
    const parts = props.currentPath.split('/');
    parts.pop();
    return parts.join('/') || '/';
});

const pathSegments = computed(() => {
    const relative = props.currentPath.replace(props.basePath, '');
    if (!relative) return [];
    return relative.split('/').filter(Boolean);
});

function navigateTo(path: string) {
    router.get(
        ServerFilesController.index.url({ server: props.server.id }),
        { path },
        { preserveScroll: true },
    );
}

function navigateToSegment(segmentIndex: number) {
    const path = props.basePath + '/' + pathSegments.value.slice(0, segmentIndex + 1).join('/');
    navigateTo(path);
}

function downloadFile(entry: FileEntry) {
    const url = ServerFilesController.download.url({ server: props.server.id }) + `?path=${encodeURIComponent(entry.path)}`;
    window.open(url, '_blank');
}

function isEditable(entry: FileEntry): boolean {
    if (entry.type !== 'file') return false;
    const ext = entry.name.split('.').pop()?.toLowerCase() ?? '';
    return EDITABLE_EXTENSIONS.includes(ext);
}

function getLanguage(filename: string): string {
    const ext = filename.split('.').pop()?.toLowerCase() ?? '';
    const map: Record<string, string> = {
        ini: 'ini', cfg: 'ini', conf: 'ini',
        json: 'json', xml: 'xml',
        yaml: 'yaml', yml: 'yaml',
        sh: 'shell', bat: 'bat',
        md: 'markdown', txt: 'plaintext',
        log: 'plaintext', env: 'plaintext',
        toml: 'plaintext',
    };
    return map[ext] || 'plaintext';
}

const editorOpen = ref(false);
const editorFile = ref<FileEntry | null>(null);
const editorContent = ref('');
const editorLoading = ref(false);
const editorSaving = ref(false);
const editorError = ref('');

async function openEditor(entry: FileEntry) {
    editorFile.value = entry;
    editorContent.value = '';
    editorError.value = '';
    editorLoading.value = true;
    editorOpen.value = true;

    try {
        const url = ServerFilesController.read.url({ server: props.server.id }) + `?path=${encodeURIComponent(entry.path)}`;
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (!res.ok) {
            editorError.value = data.error || 'Failed to read file';
        } else {
            editorContent.value = data.content;
        }
    } catch (e: any) {
        editorError.value = e.message;
    } finally {
        editorLoading.value = false;
    }
}

async function saveEditor() {
    if (!editorFile.value) return;
    editorSaving.value = true;
    editorError.value = '';

    try {
        const url = ServerFilesController.write.url({ server: props.server.id });
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                path: editorFile.value.path,
                content: editorContent.value,
            }),
        });
        const data = await res.json();
        if (!res.ok) {
            editorError.value = data.error || 'Failed to save file';
        } else {
            editorOpen.value = false;
        }
    } catch (e: any) {
        editorError.value = e.message;
    } finally {
        editorSaving.value = false;
    }
}
</script>

<template>
    <Head :title="`Files - ${server.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Files" :description="`Browse files on ${server.name}`" />

        <!-- Breadcrumb Path -->
        <div class="flex items-center gap-1 text-sm">
            <Button
                variant="ghost"
                size="sm"
                class="h-7 px-2 font-mono text-xs"
                @click="navigateTo(basePath)"
            >
                ~
            </Button>
            <span v-if="pathSegments.length == 0">/</span>
            <template v-for="(segment, i) in pathSegments" :key="i">
                <span>/</span>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 font-mono text-xs"
                    :class="{ 'font-bold': i === pathSegments.length - 1 }"
                    @click="navigateToSegment(i)"
                >
                    {{ segment }}
                </Button>
            </template>
        </div>

        <div v-if="error" class="rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive">
            {{ error }}
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Size</TableHead>
                        <TableHead>Modified</TableHead>
                        <TableHead>Permissions</TableHead>
                        <TableHead class="w-16" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!isAtBase" class="cursor-pointer hover:bg-muted/50" @click="navigateTo(parentPath)">
                        <TableCell class="font-medium">
                            <div class="flex items-center gap-2">
                                <ArrowUp class="h-4 w-4 text-muted-foreground" />
                                <span>..</span>
                            </div>
                        </TableCell>
                        <TableCell />
                        <TableCell />
                        <TableCell />
                        <TableCell />
                    </TableRow>

                    <TableRow
                        v-for="entry in entries"
                        :key="entry.name"
                        class="cursor-pointer hover:bg-muted/50"
                        @click="entry.type === 'directory' && navigateTo(entry.path)"
                    >
                        <TableCell class="font-medium">
                            <div class="flex items-center gap-2">
                                <Folder v-if="entry.type === 'directory'" class="h-4 w-4 text-blue-500" />
                                <FileSymlink v-else-if="entry.type === 'link'" class="h-4 w-4 text-purple-500" />
                                <File v-else class="h-4 w-4 text-muted-foreground" />
                                <span :class="{ 'text-blue-600 dark:text-blue-400': entry.type === 'directory' }">
                                    {{ entry.name }}
                                </span>
                            </div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ entry.size }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ entry.modified }}</TableCell>
                        <TableCell>
                            <Badge variant="outline" class="font-mono text-xs">{{ entry.permissions }}</Badge>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1">
                                <Button
                                    v-if="entry.type === 'file'"
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    title="Download"
                                    @click.stop="downloadFile(entry)"
                                >
                                    <Download class="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    v-if="isEditable(entry)"
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    title="Edit"
                                    @click.stop="openEditor(entry)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="entries.length === 0 && !error">
                        <TableCell colspan="5" class="py-8 text-center text-muted-foreground">
                            This directory is empty.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>

    <Sheet v-model:open="editorOpen">
        <SheetContent side="right" class="flex w-[90vw] max-w-[90vw] flex-col gap-0 p-0 sm:max-w-[90vw]">
            <SheetHeader class="shrink-0 border-b px-6 pr-14 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <SheetTitle class="font-mono text-sm">{{ editorFile?.name }}</SheetTitle>
                        <SheetDescription class="font-mono text-xs">{{ editorFile?.path }}</SheetDescription>
                    </div>
                    <Button size="sm" :disabled="editorSaving || editorLoading" @click="saveEditor">
                        <Loader2 v-if="editorSaving" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        Save
                    </Button>
                </div>
                <div v-if="editorError" class="mt-2 rounded-md border border-red-200 bg-red-50 p-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                    {{ editorError }}
                </div>
            </SheetHeader>

            <div class="min-h-0 flex-1">
                <div v-if="editorLoading" class="flex h-full items-center justify-center">
                    <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
                <MonacoEditor
                    v-else
                    v-model:value="editorContent"
                    :language="getLanguage(editorFile?.name ?? '')"
                    theme="vs-dark"
                    :options="{
                        minimap: { enabled: true },
                        fontSize: 13,
                        lineNumbers: 'on',
                        scrollBeyondLastLine: false,
                        wordWrap: 'on',
                        automaticLayout: true,
                    }"
                    class="h-full w-full"
                />
            </div>
        </SheetContent>
    </Sheet>
</template>
