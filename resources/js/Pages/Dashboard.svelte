<script>
    import { Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import api from '@/services/api';

    export let auth = null;
    export let stats = {};
    export let recentOrders = [];
    export let integrationStatus = {};
    // These props are sent by Inertia but may not be used directly
    export let errors = {};
    export let flash = {};

    let loading = false;
    let error = null;
    let refreshStats = stats;

    // Fetch additional data using Axios with Sanctum
    async function fetchDashboardData() {
        loading = true;
        error = null;

        try {
            const response = await api.get('/dashboard/data');
            refreshStats = response.data.stats || {};
        } catch (err) {
            error = err.response?.data?.message || 'Failed to fetch dashboard data';
            console.error('Dashboard data fetch error:', err);
        } finally {
            loading = false;
        }
    }

    onMount(() => {
        // Optionally fetch fresh data on mount
        // fetchDashboardData();
    });
</script>

<svelte:head>
    <title>Dashboard</title>
</svelte:head>

<AppLayout user={auth?.user}>
    <div class="py-4 sm:py-6 lg:py-8 bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-4 sm:mb-6 lg:mb-8">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                    Dashboard
                </h1>
                <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">
                    Welcome back, <span class="font-semibold text-indigo-600">{auth?.user?.name || 'User'}</span>! 👋
                </p>
            </div>

            <!-- Error Message -->
            {#if error}
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 text-red-700 px-4 py-3 rounded-r-lg shadow-sm">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        {error}
                    </div>
                </div>
            {/if}

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-4 sm:mb-6 lg:mb-8">
                <!-- Total Orders -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-lg rounded-xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-blue-100 text-sm font-medium mb-1">Total Orders</p>
                                <p class="text-3xl font-bold text-white">
                                    {refreshStats.total_orders || stats.total_orders || 0}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="bg-gradient-to-br from-emerald-500 to-green-600 overflow-hidden shadow-lg rounded-xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-emerald-100 text-sm font-medium mb-1">Total Revenue</p>
                                <p class="text-3xl font-bold text-white">
                                    ${(refreshStats.total_revenue || stats.total_revenue || 0).toLocaleString()}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Integrations -->
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 overflow-hidden shadow-lg rounded-xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-purple-100 text-sm font-medium mb-1">Active Integrations</p>
                                <p class="text-3xl font-bold text-white">
                                    {refreshStats.active_integrations || stats.active_integrations || 0}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 overflow-hidden shadow-lg rounded-xl transform hover:scale-105 transition-all duration-300">
                    <div class="p-4 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-amber-100 text-sm font-medium mb-1">Low Stock Alerts</p>
                                <p class="text-3xl font-bold text-white">
                                    {refreshStats.low_stock_count || stats.low_stock_count || 0}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integration Status -->
            {#if integrationStatus && Object.keys(integrationStatus).length > 0}
                <div class="mb-4 sm:mb-6 lg:mb-8 bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 sm:px-6 py-3 sm:py-4">
                        <h3 class="text-base sm:text-lg font-semibold text-white flex items-center">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Integration Status
                        </h3>
                    </div>
                    <div class="px-4 sm:px-6 py-4 sm:py-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            {#each Object.entries(integrationStatus) as [name, status]}
                                <div class="flex items-center p-4 rounded-lg {status.connected ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'} transition-all hover:shadow-md">
                                    <div class="flex-shrink-0">
                                        {#if status.connected}
                                            <div class="h-4 w-4 bg-green-500 rounded-full ring-2 ring-green-200 animate-pulse"></div>
                                        {:else}
                                            <div class="h-4 w-4 bg-red-500 rounded-full ring-2 ring-red-200"></div>
                                        {/if}
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <p class="text-sm font-semibold {status.connected ? 'text-green-900' : 'text-red-900'} capitalize">{name}</p>
                                        <p class="text-xs {status.connected ? 'text-green-700' : 'text-red-700'} mt-1">{status.message}</p>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                </div>
            {/if}

            <!-- Recent Orders -->
            <div class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-4 sm:px-6 py-3 sm:py-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-base sm:text-lg font-semibold text-white flex items-center">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Recent Orders
                        </h3>
                        <Link
                            href="/sales/orders"
                            class="text-sm font-medium text-white hover:text-blue-100 transition-colors flex items-center"
                        >
                            View All
                            <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
                <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-x-auto">

                    {#if recentOrders && recentOrders.length > 0}
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Order Number
                                        </th>
                                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Customer
                                        </th>
                                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    {#each recentOrders as order}
                                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm font-semibold text-gray-900">
                                                {order.order_number || order.id}
                                            </td>
                                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-700">
                                                {order.customer_name || order.customer_id}
                                            </td>
                                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm font-semibold text-indigo-600">
                                                ${(order.total || order.total_amount || 0).toLocaleString()}
                                            </td>
                                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {
                                                    order.status === 'completed' ? 'bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow-sm' :
                                                    order.status === 'pending' ? 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white shadow-sm' :
                                                    'bg-gradient-to-r from-gray-400 to-gray-500 text-white shadow-sm'
                                                }">
                                                    {order.status || 'pending'}
                                                </span>
                                            </td>
                                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600">
                                                {order.created_at || order.order_date}
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {:else}
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-4 text-sm text-gray-500">No recent orders found.</p>
                        </div>
                    {/if}
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-4 sm:mt-6 lg:mt-8 grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-3">
                <Link
                    href="/integration-monitor"
                    class="group bg-gradient-to-br from-indigo-500 to-purple-600 p-4 sm:p-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300"
                >
                    <div class="flex items-center mb-3">
                        <div class="bg-white bg-opacity-20 rounded-lg p-2 mr-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <h4 class="text-base sm:text-lg font-semibold text-white">ERP Integration</h4>
                    </div>
                    <p class="text-sm text-indigo-100">Manage SAP integrations and syncs</p>
                </Link>

                <Link
                    href="/inventory"
                    class="group bg-gradient-to-br from-emerald-500 to-teal-600 p-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300"
                >
                    <div class="flex items-center mb-3">
                        <div class="bg-white bg-opacity-20 rounded-lg p-2 mr-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h4 class="text-base sm:text-lg font-semibold text-white">Inventory</h4>
                    </div>
                    <p class="text-sm text-emerald-100">View and manage product inventory</p>
                </Link>

                <Link
                    href="/monitoring"
                    class="group bg-gradient-to-br from-cyan-500 to-blue-600 p-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300"
                >
                    <div class="flex items-center mb-3">
                        <div class="bg-white bg-opacity-20 rounded-lg p-2 mr-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h4 class="text-base sm:text-lg font-semibold text-white">Monitoring</h4>
                    </div>
                    <p class="text-sm text-cyan-100">System health and metrics</p>
                </Link>
            </div>
        </div>
    </div>
</AppLayout>
