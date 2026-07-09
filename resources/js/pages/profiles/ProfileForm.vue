<script setup lang="ts">
import { ref } from 'vue';
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

interface ProfileFormData {
    name?: string;
    map?: string;
    game_port?: number;
    query_port?: number;
    rcon_port?: number;
    session_name?: string;
    admin_password?: string | null;
    server_password?: string | null;
    max_players?: number;
    battleye_enabled?: boolean;
}

const props = defineProps<{
    formAction: { action: string; method: string };
    values: ProfileFormData;
    maps: Record<string, string>;
    submitLabel: string;
}>();

const battleyeEnabled = ref(props.values.battleye_enabled ?? true);
</script>

<template>
    <Form
        v-bind="formAction"
        class="space-y-8"
        v-slot="{ errors, processing }"
    >
        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">General</legend>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Profile Name</Label>
                    <Input id="name" name="name" :default-value="values.name" required placeholder="PvE Chill" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="map">Map</Label>
                    <div class="relative">
                        <Input
                            id="map"
                            name="map"
                            :default-value="values.map"
                            required
                            list="map-options"
                            placeholder="TheIsland_WP"
                        />
                        <datalist id="map-options">
                            <option v-for="(label, value) in maps" :key="value" :value="value">{{ label }}</option>
                        </datalist>
                    </div>
                    <InputError :message="errors.map" />
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">Network</legend>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="game_port">Game Port (UDP)</Label>
                    <Input id="game_port" name="game_port" type="number" :default-value="String(values.game_port)" required />
                    <InputError :message="errors.game_port" />
                </div>

                <div class="grid gap-2">
                    <Label for="query_port">Query Port (UDP)</Label>
                    <Input id="query_port" name="query_port" type="number" :default-value="String(values.query_port)" required />
                    <InputError :message="errors.query_port" />
                </div>

                <div class="grid gap-2">
                    <Label for="rcon_port">RCON Port (TCP)</Label>
                    <Input id="rcon_port" name="rcon_port" type="number" :default-value="String(values.rcon_port)" required />
                    <InputError :message="errors.rcon_port" />
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <legend class="px-2 text-sm font-medium">Server Settings</legend>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="session_name">Session Name</Label>
                    <Input id="session_name" name="session_name" :default-value="values.session_name" required placeholder="My ARK Server" />
                    <InputError :message="errors.session_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="max_players">Max Players</Label>
                    <Input id="max_players" name="max_players" type="number" :default-value="String(values.max_players)" required />
                    <InputError :message="errors.max_players" />
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="admin_password">Admin Password</Label>
                    <Input id="admin_password" name="admin_password" type="password" :default-value="values.admin_password ?? ''" placeholder="Leave blank for none" />
                    <InputError :message="errors.admin_password" />
                </div>

                <div class="grid gap-2">
                    <Label for="server_password">Server Password</Label>
                    <Input id="server_password" name="server_password" type="password" :default-value="values.server_password ?? ''" placeholder="Leave blank for public" />
                    <InputError :message="errors.server_password" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Switch
                    id="battleye_enabled"
                    v-model="battleyeEnabled"
                />
                <input type="hidden" name="battleye_enabled" :value="battleyeEnabled ? '1' : '0'" />
                <Label for="battleye_enabled" class="cursor-pointer">
                    BattlEye Anti-Cheat
                    <span class="ml-1 text-xs text-muted-foreground">(disable for modding/dev)</span>
                </Label>
            </div>
        </fieldset>

        <div class="flex justify-end">
            <Button type="submit" :disabled="processing">
                {{ submitLabel }}
            </Button>
        </div>
    </Form>
</template>
