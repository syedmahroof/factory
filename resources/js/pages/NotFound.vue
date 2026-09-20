<script setup>
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 bg-hb-hover px-4 text-center">
        <p class="text-hb-lbl font-bold tracking-[0.16em] text-hb-mut uppercase">Error 404</p>
        <h1 class="text-2xl font-bold text-hb-ink">Page not found</h1>
        <p class="max-w-sm text-hb-body text-hb-mut">
            That page does not exist, or you do not have access to it.
        </p>
        <RouterLink
            :to="auth.token ? '/' : { name: 'login' }"
            class="mt-2 rounded-hb-btn bg-hb-brand px-4 py-2 text-hb-sm font-semibold text-white hover:bg-hb-brand-2"
        >
            {{ auth.token ? 'Back to the dashboard' : 'Sign in' }}
        </RouterLink>
    </div>
</template>
