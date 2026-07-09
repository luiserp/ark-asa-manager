<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import DynamicConfigController from '@/actions/App/Http/Controllers/DynamicConfigController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { index as profilesIndex } from '@/routes/profiles';
import { Save, Loader2, Copy, Check, Info, HelpCircle } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Profiles', href: profilesIndex() },
            { title: 'Dynamic Config', href: '#' },
        ],
    },
});

interface FieldSchema {
    label: string;
    type: 'float' | 'int' | 'bool' | 'string' | 'select';
    default: number | boolean | string;
    options?: Record<string, string>;
}

interface SectionSchema {
    label: string;
    fields: Record<string, FieldSchema>;
}

const props = defineProps<{
    profile: {
        id: string;
        name: string;
        dynamic_config_enabled: boolean;
        dynamic_config: Record<string, unknown> | null;
    };
    server: { id: string; name: string };
    schema: Record<string, SectionSchema>;
    serveUrl: string;
}>();

const page = usePage();
const csrfToken = (page.props as any)._token
    ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
    ?? '';

const enabled = ref(props.profile.dynamic_config_enabled);
const config = ref<Record<string, unknown>>({ ...props.profile.dynamic_config });
const saving = ref(false);
const dirty = ref(false);
const copied = ref(false);

const hasNonDefaultValues = computed(() => {
    for (const section of Object.values(props.schema)) {
        for (const [key, field] of Object.entries(section.fields)) {
            const value = config.value[key];
            if (value !== undefined && value !== null && value !== '' && value !== field.default) {
                if (field.type === 'float' && Math.abs(Number(value) - Number(field.default)) < 0.0001) continue;
                return true;
            }
        }
    }
    return false;
});

function getValue(key: string, field: FieldSchema): string {
    const val = config.value[key];
    if (val !== undefined && val !== null) return String(val);
    return String(field.default);
}

function setValue(key: string, value: string, field: FieldSchema) {
    if (field.type === 'float') {
        config.value[key] = parseFloat(value) || 0;
    } else if (field.type === 'int') {
        config.value[key] = parseInt(value, 10) || 0;
    } else if (field.type === 'bool') {
        config.value[key] = value === 'true';
    } else {
        config.value[key] = value;
    }
    dirty.value = true;
}

function setBoolValue(key: string, value: boolean) {
    config.value[key] = value;
    dirty.value = true;
}

function toggleEnabled(value: boolean) {
    enabled.value = value;
    dirty.value = true;
}

async function save() {
    saving.value = true;
    try {
        const url = DynamicConfigController.update.url({ profile: props.profile.id });
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                dynamic_config_enabled: enabled.value,
                dynamic_config: config.value,
            }),
        });
        const data = await res.json();
        if (!res.ok) {
            toast.error(data.error || 'Failed to save Dynamic Config');
        } else {
            dirty.value = false;
            toast.success('Dynamic Config saved.');
        }
    } catch (e: any) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
}

async function copyUrl() {
    try {
        await navigator.clipboard.writeText(props.serveUrl);
        copied.value = true;
        toast.success('URL copied to clipboard.');
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        toast.error('Failed to copy URL.');
    }
}

watch(() => props.profile.id, () => {
    enabled.value = props.profile.dynamic_config_enabled;
    config.value = { ...props.profile.dynamic_config };
    dirty.value = false;
});
</script>

<template>

    <Head :title="`Dynamic Config - ${profile.name}`" />

    <div class="flex h-full flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading :title="`Dynamic Config: ${profile.name}`"
                description="Change server rates and settings live without restarting.">
                <template #tooltip>
                    <TooltipProvider :delay-duration="300">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <HelpCircle class="h-4 w-4 text-muted-foreground cursor-help" />
                            </TooltipTrigger>
                            <TooltipContent class="max-w-sm text-left" side="bottom">
                                <p class="font-semibold mb-1">How does Dynamic Config work?</p>
                                <p class="mb-1">Dynamic Config lets you change server rates (XP, taming, harvest,
                                    breeding, etc.) <strong>without restarting</strong> the server or kicking
                                    players.</p>
                                <p class="mb-1">When enabled, the <code
                                        class="rounded bg-muted  text-foreground px-1 text-xs">-UseDynamicConfig</code>
                                    flag is added
                                    to your launch command and the server is pointed to a config URL hosted by this
                                    app.</p>
                                <p class="mb-1">The ARK server fetches this config on every
                                    <strong>autosave</strong> (default: every 15 minutes). To apply changes
                                    immediately, send <code
                                        class="rounded bg-muted  text-foreground px-1 text-xs">ForceUpdateDynamicConfig</code>
                                    via
                                    RCON.
                                </p>
                                <p>Dynamic Config settings <strong>override</strong> matching values in
                                    GameUserSettings.ini. Only non-default values are sent to the server.</p>
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
                            <Button size="sm" class="cursor-pointer" :disabled="saving || !dirty" @click="save">
                                <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                Save
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Save Dynamic Config settings to this profile</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </div>

        <!-- Enable toggle + URL -->
        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">Setup</legend>

            <div class="flex items-center gap-3">
                <Switch id="dynamic_config_enabled" :model-value="enabled" @update:model-value="toggleEnabled" />
                <Label for="dynamic_config_enabled" class="cursor-pointer">
                    Enable Dynamic Config
                    <span class="ml-1 text-xs text-muted-foreground">
                        (adds <code class="rounded bg-muted px-1">-UseDynamicConfig</code> to launch flags)
                    </span>
                </Label>
            </div>

            <div v-if="enabled" class="space-y-2">
                <Label>Config URL</Label>
                <p class="text-xs text-muted-foreground">
                    This URL is automatically set as <code class="rounded bg-muted px-1">CustomDynamicConfigUrl</code>
                    when
                    starting your server.
                    The ARK server fetches it on each autosave. Send <code
                        class="rounded bg-muted px-1">ForceUpdateDynamicConfig</code> via RCON to apply changes
                    immediately.
                </p>
                <div class="flex items-center gap-2">
                    <Input :model-value="serveUrl" readonly class="font-mono text-xs" />
                    <TooltipProvider :delay-duration="500">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="outline" size="icon" class="shrink-0 cursor-pointer" @click="copyUrl">
                                    <Check v-if="copied" class="h-4 w-4 text-green-600" />
                                    <Copy v-else class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Copy URL</TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </div>
        </fieldset>

        <!-- Settings sections -->
        <template v-if="enabled">
            <fieldset v-for="(section, sectionKey) in schema" :key="sectionKey" class="space-y-4 rounded-lg border p-4">
                <legend class="px-2 text-sm font-medium">{{ section.label }}</legend>

                <div class="grid gap-x-6 gap-y-4 md:grid-cols-2 lg:grid-cols-3">
                    <template v-for="(field, fieldKey) in section.fields" :key="fieldKey">
                        <!-- Boolean fields -->
                        <div v-if="field.type === 'bool'" class="flex items-center gap-3 md:col-span-1">
                            <Switch :id="fieldKey"
                                :model-value="config[fieldKey] !== undefined ? Boolean(config[fieldKey]) : Boolean(field.default)"
                                @update:model-value="setBoolValue(fieldKey, $event)" />
                            <Label :for="fieldKey" class="cursor-pointer text-sm">
                                {{ field.label }}
                            </Label>
                        </div>

                        <!-- Select fields -->
                        <div v-else-if="field.type === 'select'" class="grid gap-2">
                            <Label :for="fieldKey">{{ field.label }}</Label>
                            <Select :model-value="getValue(fieldKey, field) || '__none__'"
                                @update:model-value="setValue(fieldKey, $event === '__none__' ? '' : $event, field)">
                                <SelectTrigger :id="fieldKey">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(optLabel, optValue) in field.options" :key="optValue"
                                        :value="String(optValue) || '__none__'">
                                        {{ optLabel }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- String fields -->
                        <div v-else-if="field.type === 'string'" class="grid gap-2">
                            <Label :for="fieldKey">{{ field.label }}</Label>
                            <Input :id="fieldKey" :model-value="getValue(fieldKey, field)"
                                @update:model-value="setValue(fieldKey, $event, field)" />
                        </div>

                        <!-- Numeric fields (float / int) -->
                        <div v-else class="grid gap-2">
                            <Label :for="fieldKey">
                                {{ field.label }}
                                <span class="text-xs text-muted-foreground">(default: {{ field.default }})</span>
                            </Label>
                            <Input :id="fieldKey" type="number" :step="field.type === 'float' ? '0.1' : '1'" :min="0"
                                :model-value="getValue(fieldKey, field)"
                                @update:model-value="setValue(fieldKey, $event, field)" />
                        </div>
                    </template>
                </div>
            </fieldset>
        </template>

        <!-- Info box when disabled -->
        <div v-if="!enabled" class="rounded-lg border border-dashed p-6 text-center">
            <Info class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                Enable Dynamic Config to change server rates and settings live without restarting the server.
            </p>
        </div>
    </div>
</template>
