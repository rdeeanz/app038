<script>
    import { Link, router } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';

    export let auth = null;
    export let settings = {};
    export let errors = {};
    export let flash = {};

    let formData = {
        app_name: settings.app_name || '',
        timezone: settings.timezone || 'UTC',
        locale: settings.locale || 'en',
    };

    let processing = false;

    function submit(e) {
        e.preventDefault();
        processing = true;

        router.post('/settings', formData, {
            onFinish: () => {
                processing = false;
            },
        });
    }
</script>

<svelte:head>
    <title>Website Settings</title>
</svelte:head>

<AppLayout user={auth?.user}>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Website Settings</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Manage website configuration and settings
                </p>
            </div>

            <!-- Flash Messages -->
            {#if flash.message}
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {flash.message}
                </div>
            {/if}

            {#if flash.error}
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {flash.error}
                </div>
            {/if}

            <!-- Settings Form -->
            <div class="bg-white shadow rounded-lg">
                <form on:submit|preventDefault={submit} class="p-6 space-y-6">
                    <!-- Application Name -->
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700">
                            Application Name
                        </label>
                        <input
                            type="text"
                            id="app_name"
                            bind:value={formData.app_name}
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        />
                        {#if errors.app_name}
                            <p class="mt-1 text-sm text-red-600">{errors.app_name}</p>
                        {/if}
                    </div>

                    <!-- Timezone -->
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700">
                            Timezone
                        </label>
                        <select
                            id="timezone"
                            bind:value={formData.timezone}
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        >
                            <option value="UTC">UTC</option>
                            <option value="Asia/Jakarta">Asia/Jakarta</option>
                            <option value="Asia/Singapore">Asia/Singapore</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="Europe/London">Europe/London</option>
                        </select>
                        {#if errors.timezone}
                            <p class="mt-1 text-sm text-red-600">{errors.timezone}</p>
                        {/if}
                    </div>

                    <!-- Locale -->
                    <div>
                        <label for="locale" class="block text-sm font-medium text-gray-700">
                            Locale
                        </label>
                        <select
                            id="locale"
                            bind:value={formData.locale}
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        >
                            <option value="en">English</option>
                            <option value="id">Indonesian</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                        </select>
                        {#if errors.locale}
                            <p class="mt-1 text-sm text-red-600">{errors.locale}</p>
                        {/if}
                    </div>

                    <!-- Read-only Info -->
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">System Information</h3>
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm text-gray-500">Environment</dt>
                                <dd class="mt-1 text-sm text-gray-900">{settings.app_env || 'N/A'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Debug Mode</dt>
                                <dd class="mt-1 text-sm text-gray-900">{settings.app_debug ? 'Enabled' : 'Disabled'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Application URL</dt>
                                <dd class="mt-1 text-sm text-gray-900">{settings.app_url || 'N/A'}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {#if processing}
                                Saving...
                            {:else}
                                Save Settings
                            {/if}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</AppLayout>

