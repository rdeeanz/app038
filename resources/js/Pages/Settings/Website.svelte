<script>
    import { Link, router, page } from '@inertiajs/svelte';
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

        router.post('/settings/website', formData, {
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
    <div class="py-4 sm:py-6 lg:py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-4 sm:mb-6 lg:mb-8">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">Settings</h1>
                <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">
                    Manage application settings and configuration
                </p>
            </div>

            <!-- Sub Menu Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-4 sm:space-x-8" aria-label="Tabs">
                    <Link
                        href="/settings/website"
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm sm:text-base {($page.url === '/settings' || $page.url === '/settings/website') ? 'border-indigo-500 text-indigo-600' : ''}"
                    >
                        <svg class="inline-block h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Website Settings
                    </Link>
                    <Link
                        href="/settings/users"
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm sm:text-base {$page.url === '/settings/users' ? 'border-indigo-500 text-indigo-600' : ''}"
                    >
                        <svg class="inline-block h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        User Settings
                    </Link>
                </nav>
            </div>

            <!-- Flash Messages -->
            {#if flash.message}
                <div class="mb-4 bg-green-50 border-l-4 border-green-400 text-green-700 px-4 py-3 rounded-r-lg shadow-sm">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        {flash.message}
                    </div>
                </div>
            {/if}

            {#if flash.error}
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 text-red-700 px-4 py-3 rounded-r-lg shadow-sm">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        {flash.error}
                    </div>
                </div>
            {/if}

            <!-- Settings Form -->
            <div class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 sm:px-6 py-3 sm:py-4">
                    <h2 class="text-base sm:text-lg font-semibold text-white flex items-center">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Website Configuration
                    </h2>
                </div>
                
                <form on:submit|preventDefault={submit} class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                    <!-- Application Name -->
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Application Name
                        </label>
                        <input
                            type="text"
                            id="app_name"
                            bind:value={formData.app_name}
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                            required
                            placeholder="Enter application name"
                        />
                        {#if errors.app_name}
                            <p class="mt-1 text-sm text-red-600">{errors.app_name}</p>
                        {/if}
                    </div>

                    <!-- Timezone -->
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">
                            Timezone
                        </label>
                        <select
                            id="timezone"
                            bind:value={formData.timezone}
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                            required
                        >
                            <option value="UTC">UTC</option>
                            <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                            <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                            <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                            <option value="Asia/Singapore">Asia/Singapore</option>
                            <option value="America/New_York">America/New_York (EST)</option>
                            <option value="Europe/London">Europe/London (GMT)</option>
                        </select>
                        {#if errors.timezone}
                            <p class="mt-1 text-sm text-red-600">{errors.timezone}</p>
                        {/if}
                    </div>

                    <!-- Locale -->
                    <div>
                        <label for="locale" class="block text-sm font-medium text-gray-700 mb-1">
                            Locale / Language
                        </label>
                        <select
                            id="locale"
                            bind:value={formData.locale}
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                            required
                        >
                            <option value="en">English</option>
                            <option value="id">Indonesian (Bahasa Indonesia)</option>
                            <option value="es">Spanish (Español)</option>
                            <option value="fr">French (Français)</option>
                            <option value="de">German (Deutsch)</option>
                        </select>
                        {#if errors.locale}
                            <p class="mt-1 text-sm text-red-600">{errors.locale}</p>
                        {/if}
                    </div>

                    <!-- Read-only Info -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">System Information</h3>
                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-gray-500">Environment</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{settings.app_env || 'N/A'}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Debug Mode</dt>
                                <dd class="mt-1 text-sm font-medium">
                                    <span class="px-2 py-1 rounded-full text-xs {settings.app_debug ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'}">
                                        {settings.app_debug ? 'Enabled' : 'Disabled'}
                                    </span>
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-gray-500">Application URL</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 break-all">{settings.app_url || 'N/A'}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button
                            type="submit"
                            disabled={processing}
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm sm:text-base font-medium"
                        >
                            {#if processing}
                                <span class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
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

