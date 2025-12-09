<script>
    import { Link, router, page } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';

    export let auth = null;
    export let users = {};
    export let roles = [];
    export let filters = {};
    export let errors = {};
    export let flash = {};

    let showCreateModal = false;
    let showEditModal = false;
    let showDeleteModal = false;
    let selectedUser = null;
    let searchQuery = filters.search || '';

    let createForm = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [],
    };

    let editForm = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [],
    };

    let processing = false;

    function openCreateModal() {
        showCreateModal = true;
        createForm = {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            roles: [],
        };
    }

    function closeCreateModal() {
        showCreateModal = false;
        createForm = {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            roles: [],
        };
    }

    function openEditModal(user) {
        selectedUser = user;
        showEditModal = true;
        editForm = {
            name: user.name,
            email: user.email,
            password: '',
            password_confirmation: '',
            roles: user.roles ? user.roles.map(r => r.name) : [],
        };
    }

    function closeEditModal() {
        showEditModal = false;
        selectedUser = null;
        editForm = {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            roles: [],
        };
    }

    function openDeleteModal(user) {
        selectedUser = user;
        showDeleteModal = true;
    }

    function closeDeleteModal() {
        showDeleteModal = false;
        selectedUser = null;
    }

    function submitCreate(e) {
        e.preventDefault();
        processing = true;

        router.post('/settings/users', createForm, {
            onSuccess: () => {
                closeCreateModal();
                processing = false;
            },
            onError: () => {
                processing = false;
            },
        });
    }

    function submitEdit(e) {
        e.preventDefault();
        processing = true;

        router.put(`/settings/users/${selectedUser.id}`, editForm, {
            onSuccess: () => {
                closeEditModal();
                processing = false;
            },
            onError: () => {
                processing = false;
            },
        });
    }

    function deleteUser() {
        processing = true;

        router.delete(`/settings/users/${selectedUser.id}`, {
            onSuccess: () => {
                closeDeleteModal();
                processing = false;
            },
            onError: () => {
                processing = false;
            },
        });
    }

    function handleSearch() {
        router.get('/settings/users', { search: searchQuery }, {
            preserveState: true,
            replace: true,
        });
    }

    function toggleRole(form, roleName) {
        if (form.roles.includes(roleName)) {
            form.roles = form.roles.filter(r => r !== roleName);
        } else {
            form.roles = [...form.roles, roleName];
        }
    }
</script>

<svelte:head>
    <title>User Settings</title>
</svelte:head>

<AppLayout user={auth?.user}>
    <div class="py-4 sm:py-6 lg:py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-4 sm:mb-6 lg:mb-8">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">Settings</h1>
                <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">
                    Manage users and their roles
                </p>
            </div>

            <!-- Sub Menu Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-4 sm:space-x-8" aria-label="Tabs">
                    <Link
                        href="/settings/website"
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm sm:text-base {$page.url === '/settings/website' ? 'border-indigo-500 text-indigo-600' : ''}"
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

            <!-- Actions Bar -->
            <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Search -->
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input
                            type="text"
                            bind:value={searchQuery}
                            on:keydown={(e) => e.key === 'Enter' && handleSearch()}
                            placeholder="Search users..."
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-4 py-2 pl-10 border"
                        />
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Create Button -->
                <button
                    on:click={openCreateModal}
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors text-sm sm:text-base font-medium flex items-center"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create User
                </button>
            </div>

            <!-- Users Table -->
            <div class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Roles
                                </th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Created
                                </th>
                                <th class="px-3 sm:px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            {#if users.data && users.data.length > 0}
                                {#each users.data as user}
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-2 sm:mr-3">
                                                    <span class="text-indigo-600 font-medium text-xs sm:text-sm">
                                                        {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                                                    </span>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">{user.name}</div>
                                            </div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-700">
                                            {user.email}
                                        </td>
                                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                            <div class="flex flex-wrap gap-1">
                                                {#if user.roles && user.roles.length > 0}
                                                    {#each user.roles as role}
                                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                            {role.name}
                                                        </span>
                                                    {/each}
                                                {:else}
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        No Role
                                                    </span>
                                                {/if}
                                            </div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500">
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </td>
                                        <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <button
                                                    on:click={() => openEditModal(user)}
                                                    class="text-indigo-600 hover:text-indigo-900 transition-colors"
                                                    title="Edit"
                                                >
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                {#if user.id !== auth?.user?.id}
                                                    <button
                                                        on:click={() => openDeleteModal(user)}
                                                        class="text-red-600 hover:text-red-900 transition-colors"
                                                        title="Delete"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                {/if}
                                            </div>
                                        </td>
                                    </tr>
                                {/each}
                            {:else}
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="mt-4 text-sm text-gray-500">No users found.</p>
                                    </td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                {#if users.links && users.links.length > 3}
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-xs sm:text-sm text-gray-700">
                            Showing {users.from || 0} to {users.to || 0} of {users.total || 0} results
                        </div>
                        <div class="flex space-x-2">
                            {#each users.links as link}
                                {#if link.url}
                                    <Link
                                        href={link.url}
                                        class="px-3 py-2 text-xs sm:text-sm border border-gray-300 rounded-md hover:bg-gray-50 {link.active ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'text-gray-700'}"
                                        innerHTML={link.label}
                                    />
                                {:else}
                                    <span class="px-3 py-2 text-xs sm:text-sm border border-gray-300 rounded-md text-gray-400 cursor-not-allowed" innerHTML={link.label} />
                                {/if}
                            {/each}
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    {#if showCreateModal}
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div 
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                    on:click={closeCreateModal}
                    role="button"
                    tabindex="0"
                    on:keydown={(e) => e.key === 'Escape' && closeCreateModal()}
                ></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form on:submit|preventDefault={submitCreate} class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Create New User</h3>
                                
                                <!-- Name -->
                                <div class="mb-4">
                                    <label for="create_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Name
                                    </label>
                                    <input
                                        type="text"
                                        id="create_name"
                                        bind:value={createForm.name}
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                        required
                                    />
                                    {#if errors.name}
                                        <p class="mt-1 text-sm text-red-600">{errors.name}</p>
                                    {/if}
                                </div>

                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="create_email" class="block text-sm font-medium text-gray-700 mb-1">
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        id="create_email"
                                        bind:value={createForm.email}
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                        required
                                    />
                                    {#if errors.email}
                                        <p class="mt-1 text-sm text-red-600">{errors.email}</p>
                                    {/if}
                                </div>

                                <!-- Password -->
                                <div class="mb-4">
                                    <label for="create_password" class="block text-sm font-medium text-gray-700 mb-1">
                                        Password
                                    </label>
                                    <input
                                        type="password"
                                        id="create_password"
                                        bind:value={createForm.password}
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                        required
                                    />
                                    {#if errors.password}
                                        <p class="mt-1 text-sm text-red-600">{errors.password}</p>
                                    {/if}
                                </div>

                                <!-- Password Confirmation -->
                                <div class="mb-4">
                                    <label for="create_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                        Confirm Password
                                    </label>
                                    <input
                                        type="password"
                                        id="create_password_confirmation"
                                        bind:value={createForm.password_confirmation}
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                        required
                                    />
                                </div>

                                <!-- Roles -->
                                <div class="mb-4">
                                    <div class="block text-sm font-medium text-gray-700 mb-2">
                                        Roles
                                    </div>
                                    <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-3">
                                        {#each roles as role}
                                            <label class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={createForm.roles.includes(role.name)}
                                                    on:change={() => toggleRole(createForm, role.name)}
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span class="ml-2 text-sm text-gray-700">{role.name}</span>
                                            </label>
                                        {/each}
                                    </div>
                                    {#if errors.roles}
                                        <p class="mt-1 text-sm text-red-600">{errors.roles}</p>
                                    {/if}
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                            <button
                                type="submit"
                                disabled={processing}
                                class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:text-sm disabled:opacity-50"
                            >
                                {processing ? 'Creating...' : 'Create User'}
                            </button>
                            <button
                                type="button"
                                on:click={closeCreateModal}
                                class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    {/if}

    <!-- Edit User Modal -->
    {#if showEditModal && selectedUser}
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                    on:click={closeEditModal}
                    role="button"
                    tabindex="0"
                    on:keydown={(e) => e.key === 'Escape' && closeEditModal()}
                ></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form on:submit|preventDefault={submitEdit} class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit User</h3>
                                
                                <!-- Name -->
                                <div class="mb-4">
                                    <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Name
                                    </label>
                                    <input
                                        type="text"
                                        id="edit_name"
                                        bind:value={editForm.name}
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                        required
                                    />
                                    {#if errors.name}
                                        <p class="mt-1 text-sm text-red-600">{errors.name}</p>
                                    {/if}
                                </div>

                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-1">
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        id="edit_email"
                                        bind:value={editForm.email}
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                        required
                                    />
                                    {#if errors.email}
                                        <p class="mt-1 text-sm text-red-600">{errors.email}</p>
                                    {/if}
                                </div>

                                <!-- Password (Optional) -->
                                <div class="mb-4">
                                    <label for="edit_password" class="block text-sm font-medium text-gray-700 mb-1">
                                        New Password (leave blank to keep current)
                                    </label>
                                    <input
                                        type="password"
                                        id="edit_password"
                                        bind:value={editForm.password}
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                    />
                                    {#if errors.password}
                                        <p class="mt-1 text-sm text-red-600">{errors.password}</p>
                                    {/if}
                                </div>

                                <!-- Password Confirmation -->
                                {#if editForm.password}
                                    <div class="mb-4">
                                        <label for="edit_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                            Confirm New Password
                                        </label>
                                        <input
                                            type="password"
                                            id="edit_password_confirmation"
                                            bind:value={editForm.password_confirmation}
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                        />
                                    </div>
                                {/if}

                                <!-- Roles -->
                                <div class="mb-4">
                                    <div class="block text-sm font-medium text-gray-700 mb-2">
                                        Roles
                                    </div>
                                    <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-3">
                                        {#each roles as role}
                                            <label class="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={editForm.roles.includes(role.name)}
                                                    on:change={() => toggleRole(editForm, role.name)}
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span class="ml-2 text-sm text-gray-700">{role.name}</span>
                                            </label>
                                        {/each}
                                    </div>
                                    {#if errors.roles}
                                        <p class="mt-1 text-sm text-red-600">{errors.roles}</p>
                                    {/if}
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                            <button
                                type="submit"
                                disabled={processing}
                                class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:text-sm disabled:opacity-50"
                            >
                                {processing ? 'Updating...' : 'Update User'}
                            </button>
                            <button
                                type="button"
                                on:click={closeEditModal}
                                class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    {/if}

    <!-- Delete Confirmation Modal -->
    {#if showDeleteModal && selectedUser}
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                    on:click={closeDeleteModal}
                    role="button"
                    tabindex="0"
                    on:keydown={(e) => e.key === 'Escape' && closeDeleteModal()}
                ></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Delete User</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete <strong>{selectedUser.name}</strong>? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                            <button
                                type="button"
                                on:click={deleteUser}
                                disabled={processing}
                                class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:text-sm disabled:opacity-50"
                            >
                                {processing ? 'Deleting...' : 'Delete User'}
                            </button>
                            <button
                                type="button"
                                on:click={closeDeleteModal}
                                class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
</AppLayout>

