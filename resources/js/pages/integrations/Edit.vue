<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import IntegrationController from '@/actions/App/Http/Controllers/IntegrationController';
import Heading from '@/components/Heading.vue';
import IntegrationForm from './IntegrationForm.vue';

interface Integration {
    id: string;
    provider: string;
    label: string | null;
    is_active: boolean;
}

const props = defineProps<{
    integration: Integration;
    providers: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrations', href: IntegrationController.index.url() },
            { title: 'Edit Integration', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit Integration`" />

    <div class="flex flex-col gap-6 p-12">
        <Heading title="Edit Integration" description="Update the provider credentials." />

        <IntegrationForm
            :form-action="IntegrationController.update.form({ integration: integration.id })"
            :values="integration"
            :providers="providers"
            submit-label="Save Changes"
            is-edit
        />
    </div>
</template>
