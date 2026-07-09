<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Editor as MonacoEditor } from '@guolao/vue-monaco-editor';
import ProfileConfigController from '@/actions/App/Http/Controllers/ProfileConfigController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
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
import { index as profilesIndex } from '@/routes/profiles';
import { Download, Save, Loader2, ServerCog, HardDriveDownload, HelpCircle } from '@lucide/vue';

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
const loading = ref(false);
const saving = ref(false);
const importing = ref(false);
const applying = ref(false);
const dirty = ref(false);

const confirmImport = ref(false);
const confirmReload = ref(false);
const confirmSave = ref(false);
const confirmApply = ref(false);

const csrfToken = (page.props as any)._token
    ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    ?? '';

function markDirty() {
    dirty.value = true;
}

async function loadFromDb() {
    loading.value = true;
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
            toast.error(data.error || 'Failed to load config');
        } else {
            editorContent.value = data.content;
            dirty.value = false;
        }
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        loading.value = false;
    }
}

async function saveToDb() {
    saving.value = true;
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
            toast.error(data.error || 'Failed to save config');
        } else {
            dirty.value = false;
            toast.success('Config saved to profile.');
        }
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
}

async function importFromServer() {
    importing.value = true;
    try {
        const url = ProfileConfigController.importFromServer.url({ profile: props.profile.id });
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ file: activeFile.value }),
        });
        const data = await res.json();
        if (!res.ok) {
            toast.error(data.error || 'Failed to import from server');
        } else {
            editorContent.value = data.content;
            dirty.value = false;
            toast.success('Config imported from server and saved to profile.');
        }
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        importing.value = false;
    }
}

async function applyToServer() {
    applying.value = true;
    try {
        const url = ProfileConfigController.apply.url({ profile: props.profile.id });
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const data = await res.json();
        if (!res.ok) {
            toast.error(data.error || 'Failed to apply config to server');
        } else {
            const deployed = data.deployed?.join(', ') || 'none';
            toast.success(`Config deployed to server: ${deployed}`);
        }
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        applying.value = false;
    }
}

watch(activeFile, () => {
    editorContent.value = '';
    dirty.value = false;
    loadFromDb();
});

watch(() => props.profile.id, () => {
    activeFile.value = props.files[0];
    editorContent.value = '';
    dirty.value = false;
    loadFromDb();
});

onMounted(() => {
    loadFromDb();
});
</script>

<template>
    <Head :title="`Config - ${profile.name}`" />

    <div class="flex h-full flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading :title="`Config: ${profile.name}`" description="Edit and manage configuration files for this profile.">
                <template #tooltip>
                    <TooltipProvider :delay-duration="300">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <HelpCircle class="h-4 w-4 text-muted-foreground cursor-help" />
                            </TooltipTrigger>
                            <TooltipContent class="max-w-sm text-left" side="bottom">
                                <p class="font-semibold mb-1">Config Editor</p>
                                <p class="mb-1">Edit <strong>GameUserSettings.ini</strong> and <strong>Game.ini</strong> for this profile. These files control hundreds of server settings like PvE mode, damage multipliers, stack sizes, and more.</p>
                                <p class="mb-1"><strong>Import</strong> pulls the current file from the server. <strong>Save</strong> stores it in the profile. <strong>Apply</strong> writes it to the server (server must be stopped).</p>
                                <p>Changes here require a server restart to take effect.</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </template>
            </Heading>
            <div class="flex items-center gap-2">
                <Badge v-if="dirty" variant="destructive">Unsaved</Badge>
                <TooltipProvider :delay-duration="500">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="outline" size="sm" class="cursor-pointer" :disabled="importing" @click="confirmImport = true">
                                <Loader2 v-if="importing" class="mr-2 h-4 w-4 animate-spin" />
                                <HardDriveDownload v-else class="mr-2 h-4 w-4" />
                                Import from Server
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Pull the current config file from the remote server and save it to this profile</p>
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="outline" size="sm" class="cursor-pointer" :disabled="loading" @click="confirmReload = true">
                                <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                                <Download v-else class="mr-2 h-4 w-4" />
                                Reload
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Reload the saved config from this profile (discard unsaved changes)</p>
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button size="sm" class="cursor-pointer" :disabled="saving || !dirty" @click="confirmSave = true">
                                <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                Save
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Save changes to this profile's stored config</p>
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="secondary" size="sm" class="cursor-pointer" :disabled="applying || dirty" @click="confirmApply = true">
                                <Loader2 v-if="applying" class="mr-2 h-4 w-4 animate-spin" />
                                <ServerCog v-else class="mr-2 h-4 w-4" />
                                Apply to Server
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Deploy this profile's configs to the server (server must be stopped)</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
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
                @change="markDirty"
            />
        </div>
    </div>

    <!-- Import from Server Confirmation -->
    <AlertDialog v-model:open="confirmImport">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Import from Server?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will pull <strong>{{ activeFile }}</strong> from the remote server and overwrite the config stored in this profile. Any unsaved editor changes will be lost.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="importFromServer">Import</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- Reload Confirmation -->
    <AlertDialog v-model:open="confirmReload">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Reload Config?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will reload <strong>{{ activeFile }}</strong> from the profile's stored config. Any unsaved changes in the editor will be discarded.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="loadFromDb">Reload</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- Save Confirmation -->
    <AlertDialog v-model:open="confirmSave">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Save Config?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will save the current editor content as <strong>{{ activeFile }}</strong> for this profile. The previous stored version will be overwritten.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="saveToDb">Save</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- Apply to Server Confirmation -->
    <AlertDialog v-model:open="confirmApply">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Apply Config to Server?</AlertDialogTitle>
                <AlertDialogDescription>
                    This will deploy all stored config files (GameUserSettings.ini and Game.ini) from this profile to the server. The server must be stopped. Existing files on the server will be overwritten.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="applyToServer">Apply</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
