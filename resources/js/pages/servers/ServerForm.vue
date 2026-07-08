<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ServerFormData {
    name?: string;
    host?: string;
    port?: number;
    username?: string;
    ssh_key_path?: string;
    server_dir?: string;
    backups_dir?: string;
    logs_dir?: string;
    binary_path?: string;
    proton_path?: string;
    steam_app_id?: string;
}

defineProps<{
    formAction: { action: string; method: string };
    values: ServerFormData;
    submitLabel: string;
    isEdit?: boolean;
}>();
</script>

<template>
    <Form
        v-bind="formAction"
        :options="{ forceFormData: true }"
        class="space-y-8"
        v-slot="{ errors, processing }"
    >
        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">General</legend>

            <div class="grid gap-2">
                <Label for="name">Server Name</Label>
                <Input id="name" name="name" :default-value="values.name" required placeholder="My ARK Server" />
                <InputError :message="errors.name" />
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">SSH Connection</legend>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="host">Host</Label>
                    <Input id="host" name="host" :default-value="values.host" required placeholder="192.168.1.50" />
                    <InputError :message="errors.host" />
                </div>

                <div class="grid gap-2">
                    <Label for="port">Port</Label>
                    <Input id="port" name="port" type="number" :default-value="String(values.port)" required />
                    <InputError :message="errors.port" />
                </div>

                <div class="grid gap-2">
                    <Label for="username">Username</Label>
                    <Input id="username" name="username" :default-value="values.username" required placeholder="ark-sa" />
                    <InputError :message="errors.username" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="ssh_key">Private Key</Label>
                <Input id="ssh_key" name="ssh_key" type="file" :required="!isEdit" accept=".pem,.key,*" />
                <p v-if="isEdit && values.ssh_key_path" class="text-xs text-muted-foreground">
                    Current key: {{ values.ssh_key_path.split('/').pop() }} — upload a new file to replace it.
                </p>
                <InputError :message="errors.ssh_key" />
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">Server Paths</legend>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="server_dir">Server Directory</Label>
                    <Input id="server_dir" name="server_dir" :default-value="values.server_dir" required />
                    <InputError :message="errors.server_dir" />
                </div>

                <div class="grid gap-2">
                    <Label for="backups_dir">Backups Directory</Label>
                    <Input id="backups_dir" name="backups_dir" :default-value="values.backups_dir" required />
                    <InputError :message="errors.backups_dir" />
                </div>

                <div class="grid gap-2">
                    <Label for="logs_dir">Logs Directory</Label>
                    <Input id="logs_dir" name="logs_dir" :default-value="values.logs_dir" required />
                    <InputError :message="errors.logs_dir" />
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">Runtime</legend>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="binary_path">Binary Path</Label>
                    <Input id="binary_path" name="binary_path" :default-value="values.binary_path" required />
                    <InputError :message="errors.binary_path" />
                </div>

                <div class="grid gap-2">
                    <Label for="proton_path">Proton Path</Label>
                    <Input id="proton_path" name="proton_path" :default-value="values.proton_path" required />
                    <InputError :message="errors.proton_path" />
                </div>

                <div class="grid gap-2">
                    <Label for="steam_app_id">Steam App ID</Label>
                    <Input id="steam_app_id" name="steam_app_id" :default-value="values.steam_app_id" required />
                    <InputError :message="errors.steam_app_id" />
                </div>
            </div>
        </fieldset>

        <div class="flex justify-end">
            <Button type="submit" :disabled="processing">
                {{ submitLabel }}
            </Button>
        </div>
    </Form>
</template>
