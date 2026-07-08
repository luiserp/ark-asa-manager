<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import { Editor as MonacoEditor } from '@guolao/vue-monaco-editor';
import ProfileConfigController from '@/actions/App/Http/Controllers/ProfileConfigController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { index as profilesIndex } from '@/routes/profiles';
import { Download, Upload, Loader2 } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profiles', href: profilesIndex() },
            { title: 'Config', href: '#' },
        ],
    },
});

const props = defineProps<{
    profile: { id: string; name: string };
    server: { id: string; name: string };
    files: string[];
}>();

const page = usePage();
const activeFile = ref(props.files[0]);
const editorContent = ref('');
const pulling = ref(false);
const pushing = ref(false);
const error = ref('');
const lastSynced = ref('');

const csrfToken = (page.props as any)._token
    ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    ?? '';

async function pullFile() {
    pulling.value = true;
    error.value = '';
    try {
        const url = ProfileConfigController.pull.url(
            { profile: props.profile.id },
            { query: { file: activeFile.value } },
        );
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (!res.ok) {
            error.value = data.error || 'Failed to pull file';
            editorContent.value = data.content ?? '';
        } else {
            editorContent.value = data.content;
            lastSynced.value = new Date().toLocaleTimeString();
        }
    } catch (e: any) {
        error.value = e.message;
    } finally {
        pulling.value = false;
    }
}

async function pushFile() {
    pushing.value = true;
    error.value = '';
    try {
        const url = ProfileConfigController.push.url({ profile: props.profile.id });
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                file: activeFile.value,
                content: editorContent.value,
            }),
        });
        const data = await res.json();
        if (!res.ok) {
            error.value = data.error || 'Failed to push file';
        } else {
            lastSynced.value = new Date().toLocaleTimeString();
        }
    } catch (e: any) {
        error.value = e.message;
    } finally {
        pushing.value = false;
    }
}

watch(activeFile, () => {
    editorContent.value = '';
    pullFile();
});

onMounted(() => {
    pullFile();
});
</script>

<template>
    <Head :title="`Config - ${profile.name}`" />

    <div class="flex h-full flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading :title="`Config: ${profile.name}`" :description="`Edit configuration files on ${server.name}`" />
            <div class="flex items-center gap-2">
                <Badge v-if="lastSynced" variant="secondary">
                    Last sync: {{ lastSynced }}
                </Badge>
                <Button variant="outline" size="sm" :disabled="pulling" @click="pullFile">
                    <Loader2 v-if="pulling" class="mr-2 h-4 w-4 animate-spin" />
                    <Download v-else class="mr-2 h-4 w-4" />
                    Pull
                </Button>
                <Button size="sm" :disabled="pushing" @click="pushFile">
                    <Loader2 v-if="pushing" class="mr-2 h-4 w-4 animate-spin" />
                    <Upload v-else class="mr-2 h-4 w-4" />
                    Push
                </Button>
            </div>
        </div>

        <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
            {{ error }}
        </div>

        <div class="flex gap-1 border-b pb-1">
            <button
                v-for="file in files"
                :key="file"
                :class="[
                    'rounded-t-md px-3 py-1.5 text-sm font-medium transition-colors',
                    activeFile === file
                        ? 'border border-b-0 bg-background text-foreground'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="activeFile = file"
            >
                {{ file }}
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-hidden rounded-md border">
            <MonacoEditor
                v-model:value="editorContent"
                language="ini"
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
    </div>
</template>
