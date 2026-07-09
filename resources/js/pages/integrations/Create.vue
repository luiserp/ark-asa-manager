<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import IntegrationController from '@/actions/App/Http/Controllers/IntegrationController';
import Heading from '@/components/Heading.vue';
import IntegrationForm from './IntegrationForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Integrations', href: IntegrationController.index.url() },
            { title: 'Add Integration', href: '#' },
        ],
    },
});

defineProps<{
    providers: Record<string, string>;
}>();
</script>

<template>
    <Head title="Add Integration" />

    <div class="flex flex-col gap-6 p-12">
        <Heading title="Add Integration" description="Connect an external service by providing its API key." />

        <IntegrationForm
            :form-action="IntegrationController.store.form()"
            :values="{}"
            :providers="providers"
            submit-label="Create Integration"
        />
    </div>
</template>
