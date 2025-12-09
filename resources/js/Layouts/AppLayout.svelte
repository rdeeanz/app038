<script>
    import { Link, page, router } from '@inertiajs/svelte';
    import { slide } from 'svelte/transition';
    import FlashMessage from '@/components/FlashMessage.svelte';

    export let user = null;

    let sidebarOpen = false;
    let settingsExpanded = false;

    function toggleSidebar() {
        sidebarOpen = !sidebarOpen;
    }

    function closeSidebar() {
        sidebarOpen = false;
    }

    function toggleSettings() {
        settingsExpanded = !settingsExpanded;
    }

    // Helper function to check if current route matches
    function isActive(href) {
        return $page.url === href || $page.url.startsWith(href + '/');
    }

    // Auto-expand settings submenu if any submenu is active
    $: {
        const isSettingsActive = isActive('/settings');
        if (isSettingsActive && !settingsExpanded) {
            settingsExpanded = true;
        }
    }

    function handleLogout() {
        router.post('/logout', {}, {
            onSuccess: () => {
                closeSidebar();
            }
        });
    }
</script>

<div class="min-h-screen bg-gray-100 flex">
    {#if user}
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 {sidebarOpen ? 'translate-x-0' : '-translate-x-full'}">
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                <Link href="/dashboard" class="text-xl font-bold text-gray-800">
                    ERP System
                </Link>
                <!-- Close button for mobile -->
                <button
                    type="button"
                    class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                    on:click={closeSidebar}
                    aria-label="Close sidebar"
                >
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <Link
                    href="/dashboard"
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {isActive('/dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'}"
                    on:click={closeSidebar}
                >
                    <svg class="mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </Link>

                <Link
                    href="/integration-monitor"
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {isActive('/integration-monitor') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'}"
                    on:click={closeSidebar}
                >
                    <svg class="mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Integration Monitor
                </Link>

                <Link
                    href="/mapping-editor"
                    class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {isActive('/mapping-editor') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'}"
                    on:click={closeSidebar}
                >
                    <svg class="mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Mapping Editor
                </Link>

                {#if user.roles && user.roles.some(role => role.name === 'Super Admin')}
                    <!-- Settings Menu with Sub Menu -->
                    <div>
                        <button
                            type="button"
                            on:click={toggleSettings}
                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-colors {isActive('/settings') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'}"
                        >
                            <div class="flex items-center">
                                <svg class="mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Settings
                            </div>
                            <svg 
                                class="h-4 w-4 transition-transform duration-200 {settingsExpanded ? 'transform rotate-180' : ''}" 
                                xmlns="http://www.w3.org/2000/svg" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        {#if settingsExpanded}
                            <div transition:slide={{ duration: 200 }} class="ml-4 mt-1 space-y-1">
                                <Link
                                    href="/settings/website"
                                    class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {isActive('/settings/website') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}"
                                    on:click={closeSidebar}
                                >
                                    <svg class="mr-3 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Website Settings
                                </Link>
                                <Link
                                    href="/settings/users"
                                    class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {isActive('/settings/users') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}"
                                    on:click={closeSidebar}
                                >
                                    <svg class="mr-3 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    User Settings
                                </Link>
                            </div>
                        {/if}
                    </div>
                {/if}
            </nav>

            <!-- User Section at Bottom -->
            <div class="border-t border-gray-200 p-4">
                <div class="flex items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-indigo-600 font-medium text-sm">
                                {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                            </span>
                        </div>
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{user.name}</p>
                        <p class="text-xs text-gray-500 truncate">{user.email}</p>
                    </div>
                </div>
                <button
                    type="button"
                    on:click={handleLogout}
                    class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors"
                >
                    <svg class="mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </button>
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        {#if sidebarOpen}
            <div
                class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
                on:click={closeSidebar}
                on:keydown={(e) => e.key === 'Escape' && closeSidebar()}
                role="button"
                tabindex="0"
                aria-label="Close sidebar"
            ></div>
        {/if}
    {/if}

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col {user ? 'lg:ml-64' : ''}">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center">
                {#if user}
                    <!-- Mobile Menu Button -->
                    <button
                        type="button"
                        class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                        on:click={toggleSidebar}
                        aria-label="Open sidebar"
                    >
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                {:else}
                    <Link href="/" class="text-xl font-bold text-gray-800">
                        Laravel
                    </Link>
                {/if}
            </div>

            <div class="flex items-center space-x-4">
                {#if !user}
                    <Link
                        href="/login"
                        class="text-gray-600 hover:text-gray-900 text-sm font-medium"
                    >
                        Login
                    </Link>
                    <Link
                        href="/register"
                        class="text-gray-600 hover:text-gray-900 text-sm font-medium"
                    >
                        Register
                    </Link>
                {/if}
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-gray-100">
            <div class="py-4 sm:py-6">
                <slot />
            </div>
        </main>
    </div>

    <FlashMessage />
</div>
