<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AuthFrame from '@/components/auth/AuthFrame.vue';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '', remember: false });
const failed = ref('');
const loading = ref(false);
const showPassword = ref(false);

/*
 * One line under the fields says everything that can go wrong here — a rejected
 * password reads the same as a missing one. Its space is held whether or not
 * there is anything to say, so the button underneath never moves.
 */
const message = computed(() => failed.value);

async function submit() {
    failed.value = '';
    loading.value = true;

    try {
        await auth.login(form.email, form.password);
        router.push(route.query.redirect || '/');
    } catch (e) {
        // 401 here means bad credentials, not an expired session, so it needs to
        // be shown on the form rather than swallowed by the interceptor.
        failed.value = e.response?.data?.message ?? 'Invalid email or password.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthFrame title="Sign in" note="Use the address your account was opened with.">
        <form novalidate @submit.prevent="submit">
            <!-- The two fields share one rounded shell, divided by a hairline. -->
            <div
                class="divide-y overflow-hidden rounded-[14px] border transition"
                :class="message
                    ? 'divide-[#ce3535]/40 border-[#ce3535]'
                    : 'divide-gray-200 border-gray-200 focus-within:border-[#cc1f1f] focus-within:shadow-[0_0_0_2px_rgba(204,31,31,0.2)]'"
            >
                <div class="flex items-center gap-3 bg-white px-4">
                    <label for="login-email" class="w-[70px] flex-none py-3.5 text-hb-lbl font-bold tracking-[0.12em] text-hb-mut uppercase">
                        Email
                    </label>
                    <input
                        id="login-email"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        autofocus
                        placeholder="you@firm.com"
                        class="min-w-0 flex-1 rounded-none border-0 bg-transparent px-0 py-3.5 text-[14.5px] font-semibold text-hb-ink shadow-none outline-none focus:border-0 focus:shadow-none placeholder:font-normal placeholder:text-hb-mut/60"
                    />
                </div>
                <div class="flex items-center gap-3 bg-white px-4">
                    <label for="login-password" class="w-[70px] flex-none py-3.5 text-hb-lbl font-bold tracking-[0.12em] text-hb-mut uppercase">
                        Password
                    </label>
                    <input
                        id="login-password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="min-w-0 flex-1 rounded-none border-0 bg-transparent px-0 py-3.5 text-[14.5px] font-semibold text-hb-ink shadow-none outline-none focus:border-0 focus:shadow-none placeholder:text-hb-mut/60"
                    />
                    <button
                        type="button"
                        class="flex-none text-hb-lbl font-bold tracking-[0.12em] text-hb-mut uppercase transition-colors hover:text-hb-ink"
                        @click="showPassword = !showPassword"
                    >
                        {{ showPassword ? 'Hide' : 'Show' }}
                    </button>
                </div>
            </div>

            <p
                class="mt-2 text-hb-sm text-[#ce3535]"
                :class="!message && 'invisible'"
                role="alert"
                aria-live="polite"
            >
                {{ message || ' ' }}
            </p>

            <button
                type="submit"
                class="mt-3 flex w-full items-center justify-center gap-2.5 rounded-[14px] bg-[#cc1f1f] py-3.5 text-hb-body font-extrabold text-white transition-colors hover:bg-[#b70000] focus:shadow-[0_0_0_2px_rgba(204,31,31,0.2)] focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="loading"
            >
                {{ loading ? 'Signing in' : 'Sign in' }}
                <i
                    class="mdi text-base leading-none"
                    :class="loading ? 'mdi-loading animate-spin' : 'mdi-arrow-right'"
                />
            </button>

            <div class="mt-5 flex items-center justify-between">
                <label class="flex items-center gap-2 text-hb-sm text-hb-mut select-none">
                    <input v-model="form.remember" type="checkbox" class="size-4 accent-[#cc1f1f]" />
                    Keep me signed in
                </label>
            </div>
        </form>
    </AuthFrame>
</template>
