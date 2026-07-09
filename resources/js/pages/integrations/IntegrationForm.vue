<script setup lang="ts">
import { ref } from 'vue';
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Eye, EyeOff, Copy, Check } from '@lucide/vue';

interface IntegrationFormData {
    provider?: string;
    label?: string | null;
    api_key?: string;
}

defineProps<{
    formAction: { action: string; method: string };
    values: IntegrationFormData;
    providers: Record<string, string>;
    submitLabel: string;
    isEdit?: boolean;
}>();

const showKey = ref(false);
const copied = ref(false);

function copyKey() {
    const input = document.getElementById('api_key') as HTMLInputElement | null;
    if (!input?.value) return;
    navigator.clipboard.writeText(input.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}
</script>

<template>
    <Form
        v-bind="formAction"
        :options="{ forceFormData: true }"
        class="space-y-6"
        v-slot="{ errors, processing }"
    >
        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">Provider</legend>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="provider">Provider</Label>
                    <Select name="provider" :default-value="values.provider ?? ''" class="w-full">
                        <SelectTrigger id="provider" class="w-full">
                            <SelectValue placeholder="Select provider" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="(label, key) in providers" :key="key" :value="key">
                                {{ label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.provider" />
                </div>

                <div class="grid gap-2">
                    <Label for="label">Label (optional)</Label>
                    <Input id="label" name="label" :default-value="values.label ?? ''" placeholder="e.g. Production key" />
                    <InputError :message="errors.label" />
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">Credentials</legend>

            <div class="grid gap-2">
                <Label for="api_key">API Key</Label>
                <div class="flex gap-2">
                    <Input
                        id="api_key"
                        name="api_key"
                        :type="showKey ? 'text' : 'password'"
                        :default-value="values.api_key ?? ''"
                        :placeholder="isEdit ? 'Leave blank to keep current key' : 'Enter API key'"
                        :required="!isEdit"
                        class="flex-1"
                    />
                    <Button type="button" variant="outline" size="icon" class="shrink-0 cursor-pointer" @click="showKey = !showKey">
                        <EyeOff v-if="showKey" class="h-4 w-4" />
                        <Eye v-else class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="outline" size="icon" class="shrink-0 cursor-pointer" @click="copyKey">
                        <Check v-if="copied" class="h-4 w-4 text-green-500" />
                        <Copy v-else class="h-4 w-4" />
                    </Button>
                </div>
                <InputError :message="errors.api_key" />
            </div>
        </fieldset>

        <input v-if="isEdit" type="hidden" name="is_active" :value="'1'" />

        <div class="flex gap-2">
            <Button type="submit" :disabled="processing">
                {{ processing ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </Form>
</template>
