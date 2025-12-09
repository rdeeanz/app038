<script>
    import { Link, router } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';

    export let auth = null;
    export let errors = {};
    export let flash = {};

    let email = '';
    let password = '';
    let remember = false;
    let processing = false;

    function submit(e) {
        e.preventDefault();
        processing = true;

        router.post('/login', {
            email,
            password,
            remember,
        }, {
            onFinish: () => {
                processing = false;
            },
        });
    }
</script>

<svelte:head>
    <title>Login</title>
</svelte:head>

<AppLayout user={auth?.user}>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Sign in to your account
                </h2>
            </div>
            <form class="mt-8 space-y-6" on:submit|preventDefault={submit}>
                {#if flash.error}
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        {flash.error}
                    </div>
                {/if}

                {#if flash.message}
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        {flash.message}
                    </div>
                {/if}

                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            bind:value={email}
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Email address"
                        />
                        {#if errors.email}
                            <p class="mt-1 text-sm text-red-600">{errors.email}</p>
                        {/if}
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            bind:value={password}
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Password"
                        />
                        {#if errors.password}
                            <p class="mt-1 text-sm text-red-600">{errors.password}</p>
                        {/if}
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            bind:checked={remember}
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <Link href="/forgot-password" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Forgot your password?
                        </Link>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        disabled={processing}
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {#if processing}
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Signing in...
                        {:else}
                            Sign in
                        {/if}
                    </button>
                </div>

                <div class="text-center">
                    <span class="text-sm text-gray-600">
                        Don't have an account?
                        <Link href="/register" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Register
                        </Link>
                    </span>
                </div>
            </form>
        </div>
    </div>
</AppLayout>

