<script>
    import { Link, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import api from '@/services/api';

    export let auth = null;
    export let integrations = [];
    export let syncHistory = [];
    // These props are sent by Inertia but may not be used directly
    export let errors = {};
    export let flash = {};

    let loading = false;
    let error = null;
    let selectedIntegration = null;
    let testResults = {};

    // Fetch integration status using Axios with Sanctum
    async function testConnection(integrationId) {
        loading = true;
        error = null;

        try {
            const response = await api.post(
                `/erp-integration/test-connection`,
                { integration_id: integrationId }
            );

            testResults = {
                ...testResults,
                [integrationId]: response.data,
            };
        } catch (err) {
            error = err.response?.data?.message || 'Connection test failed';
            console.error('Connection test error:', err);
        } finally {
            loading = false;
        }
    }

    // Sync data using Axios
    async function syncData(integrationId, type = 'products') {
        loading = true;
        error = null;

        try {
            const response = await api.post('/erp-integration/sync', {
                type,
                endpoint: `/api/${type}`,
                priority: 'normal',
            });

            // Refresh page data
            router.reload({ only: ['syncHistory'] });
        } catch (err) {
            error = err.response?.data?.message || 'Sync failed';
            console.error('Sync error:', err);
        } finally {
            loading = false;
        }
    }

    // Poll for sync status
    async function checkSyncStatus(syncId) {
        try {
            await api.get(`/erp-integration/sync/${syncId}/status`);

            // Update sync history
            router.reload({ only: ['syncHistory'] });
        } catch (err) {
            console.error('Status check error:', err);
        }
    }

    onMount(() => {
        // Auto-refresh sync status every 5 seconds for pending syncs
        const interval = setInterval(() => {
            const pendingSyncs = syncHistory.filter(s => s.status === 'pending');
            pendingSyncs.forEach(sync => {
                checkSyncStatus(sync.sync_id);
            });
        }, 5000);

        return () => clearInterval(interval);
    });
</script>

<svelte:head>
    <title>Integration Monitor</title>
</svelte:head>

<AppLayout user={auth?.user}>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Integration Monitor</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Monitor and manage ERP integrations
                </p>
            </div>

            <!-- Error Message -->
            {#if error}
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {error}
                </div>
            {/if}

            <!-- Integration Cards -->
            <div class="grid grid-cols-1 gap-6 mb-8">
                {#each integrations as integration}
                    {@const testResult = testResults[integration.id]}
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        {#if testResult?.connected}
                                            <div class="h-4 w-4 bg-green-400 rounded-full"></div>
                                        {:else if testResult?.connected === false}
                                            <div class="h-4 w-4 bg-red-400 rounded-full"></div>
                                        {:else}
                                            <div class="h-4 w-4 bg-gray-300 rounded-full"></div>
                                        {/if}
                                    </div>
                                    <h3 class="ml-3 text-lg font-medium text-gray-900">
                                        {integration.name || 'SAP Integration'}
                                    </h3>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        on:click={() => testConnection(integration.id)}
                                        disabled={loading}
                                        class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 disabled:opacity-50"
                                    >
                                        {loading ? 'Testing...' : 'Test Connection'}
                                    </button>
                                    <button
                                        on:click={() => syncData(integration.id, 'products')}
                                        disabled={loading}
                                        class="px-4 py-2 text-sm font-medium text-green-600 bg-green-50 rounded-md hover:bg-green-100 disabled:opacity-50"
                                    >
                                        Sync Products
                                    </button>
                                </div>
                            </div>

                            <!-- Connection Details -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Type:</span>
                                    <span class="ml-2 font-medium text-gray-900">{integration.type || 'OData'}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Status:</span>
                                    <span class="ml-2 font-medium {
                                        testResult?.connected ? 'text-green-600' : 'text-red-600'
                                    }">
                                        {testResult?.connected ? 'Connected' : testResult?.connected === false ? 'Disconnected' : 'Unknown'}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Last Sync:</span>
                                    <span class="ml-2 font-medium text-gray-900">
                                        {integration.last_sync || 'Never'}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total Syncs:</span>
                                    <span class="ml-2 font-medium text-gray-900">
                                        {integration.total_syncs || 0}
                                    </span>
                                </div>
                            </div>

                            <!-- Test Result Message -->
                            {#if testResult?.message}
                                <div class="mt-4 p-3 bg-gray-50 rounded text-sm {
                                    testResult.connected ? 'text-green-700' : 'text-red-700'
                                }">
                                    {testResult.message}
                                </div>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>

            <!-- Sync History -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Sync History</h3>

                    {#if syncHistory && syncHistory.length > 0}
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sync ID
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Records
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Started
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Completed
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    {#each syncHistory as sync}
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                                {sync.sync_id}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {sync.type || 'N/A'}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {
                                                    sync.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                    sync.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                                                    sync.status === 'failed' ? 'bg-red-100 text-red-800' :
                                                    'bg-yellow-100 text-yellow-800'
                                                }">
                                                    {sync.status || 'pending'}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {sync.records_synced || 0}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {sync.started_at || 'N/A'}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {sync.completed_at || '-'}
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {:else}
                        <p class="text-sm text-gray-500">No sync history available.</p>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</AppLayout>

