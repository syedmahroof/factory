<script setup>
/**
 * The room every signed-out screen stands in.
 *
 * The frame, the watermark and the two lines of corner chrome live here rather
 * than inside the sign-in page, so any later signed-out screen stands in the
 * same room.
 */
import BrandMark from '@/components/BrandMark.vue';

const year = new Date().getFullYear();

defineProps({
    // The line above the heading. Sized and coloured by the frame, worded by the page.
    eyebrow: { type: String, default: '' },
    title: { type: String, default: '' },
    note: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-white flex flex-col relative overflow-hidden">
        <!-- The mark, oversized and cropped by the frame. It is the only artwork. -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -right-24 -bottom-28 w-[420px] text-[#b70000] opacity-[0.04] select-none sm:-right-16 sm:w-[520px]"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke-width="0.7" stroke="currentColor" class="w-full">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" />
            </svg>
        </div>
        <!-- A wash in the opposite corner, so the room is lit from one side. -->
        <div
            class="pointer-events-none absolute inset-0"
            style="background-image: radial-gradient(38rem 28rem at 0% 0%, rgba(206, 53, 53, .05), transparent 60%);"
            aria-hidden="true"
        />

        <header class="relative flex items-center justify-between px-6 py-6 sm:px-9">
            <BrandMark />
            <span class="text-hb-lbl font-bold tracking-[0.18em] text-black uppercase">Manufacturing suite</span>
        </header>

        <main class="relative flex flex-1 items-center justify-center px-6 py-8">
            <div class="w-full max-w-[352px]">
                <p v-if="eyebrow" class="text-center text-hb-lbl font-bold tracking-[0.18em] text-black uppercase">
                    {{ eyebrow }}
                </p>
                <h1 class="mt-2 text-center text-[27px] leading-tight font-extrabold tracking-[-0.015em] text-black">
                    {{ title }}
                </h1>
                <p v-if="note" class="mt-2 text-center text-[13px] text-black">{{ note }}</p>

                <div class="mt-9">
                    <slot />
                </div>
            </div>
        </main>

        <footer class="relative flex items-center justify-between px-6 py-6 text-hb-sm text-black sm:px-9">
            <span>&copy; {{ year }} Factory ERP</span>
            <span class="hidden sm:block">Access is logged</span>
        </footer>
    </div>
</template>
