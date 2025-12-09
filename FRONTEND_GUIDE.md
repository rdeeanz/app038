# Frontend Guide: Inertia.js + Svelte

This guide explains how to use the Inertia.js + Svelte frontend pages and components with Laravel Sanctum authentication.

## Pages Overview

### 1. Dashboard.svelte
Main dashboard page displaying:
- Statistics cards (orders, revenue, integrations, low stock)
- Integration status indicators
- Recent orders table
- Quick action links

**Route**: `/dashboard`

### 2. IntegrationMonitor.svelte
ERP integration monitoring page with:
- Integration connection status
- Test connection functionality
- Sync data buttons
- Sync history table with real-time updates

**Route**: `/integration-monitor`

### 3. MappingEditor.svelte
YAML mapping file editor with:
- File browser sidebar
- Code editor for YAML content
- Save and test functionality
- Create new mapping files

**Route**: `/mapping-editor`

## Components

### Reusable Components

1. **StatusBadge.svelte** - Status indicator badge
2. **LoadingSpinner.svelte** - Loading indicator
3. **FlashMessage.svelte** - Flash message display

## API Service

The `api.js` service provides a configured Axios instance with:
- Automatic CSRF cookie handling
- Sanctum authentication
- Error handling
- Request/response interceptors

### Usage Example

```javascript
import api from '@/services/api';

// GET request
const response = await api.get('/dashboard/data');

// POST request
const response = await api.post('/erp-integration/sync', {
    type: 'products',
    endpoint: '/api/products',
});

// PUT request
const response = await api.put('/mappings/order-to-sap.yaml', {
    content: yamlContent,
});
```

## Data Fetching Examples

### Using Inertia (Server-side)

```svelte
<script>
    import { Head } from '@inertiajs/svelte';
    
    export let stats = $props();
    export let orders = $props();
</script>

<Head title="Dashboard" />

<div>
    <p>Total Orders: {stats.total_orders}</p>
</div>
```

### Using Axios with Sanctum (Client-side)

```svelte
<script>
    import api from '@/services/api';
    import { onMount } from 'svelte';
    
    let data = $state(null);
    let loading = $state(false);
    
    async function fetchData() {
        loading = true;
        try {
            const response = await api.get('/dashboard/data');
            data = response.data;
        } catch (error) {
            console.error('Error:', error);
        } finally {
            loading = false;
        }
    }
    
    onMount(() => {
        fetchData();
    });
</script>

{#if loading}
    <p>Loading...</p>
{:else if data}
    <p>Data: {JSON.stringify(data)}</p>
{/if}
```

## Routes

### Web Routes

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/integration-monitor', [IntegrationMonitorController::class, 'index']);
    Route::get('/mapping-editor', [MappingController::class, 'index']);
});
```

### API Routes

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard/data', [DashboardController::class, 'data']);
    Route::get('/mappings/{filename}', [MappingController::class, 'show']);
    Route::put('/mappings/{filename}', [MappingController::class, 'update']);
    Route::post('/mappings/test', [MappingController::class, 'test']);
});
```

## Authentication

### Sanctum Setup

1. **CSRF Cookie**: Automatically handled by `api.js` service
2. **Bearer Token**: For API-only requests (optional)
3. **Session Cookie**: For SPA requests (default)

### Example: Authenticated Request

```javascript
import api from '@/services/api';

// The api service automatically:
// 1. Gets CSRF cookie
// 2. Sends session cookie
// 3. Handles authentication

const response = await api.get('/dashboard/data');
```

## Component Patterns

### Props (Svelte 5)

```svelte
<script>
    export let user = $props();
    export let data = $props() || [];
</script>
```

### Reactive State

```svelte
<script>
    let count = $state(0);
    let items = $state([]);
</script>
```

### Effects

```svelte
<script>
    import { $effect } from 'svelte';
    
    $effect(() => {
        // Runs when dependencies change
        console.log('Count changed:', count);
    });
</script>
```

## Best Practices

1. **Use Inertia for Initial Load**: Server-side rendering
2. **Use Axios for Updates**: Client-side data fetching
3. **Handle Loading States**: Show spinners during requests
4. **Error Handling**: Display user-friendly error messages
5. **Optimistic Updates**: Update UI before server response
6. **Debounce Requests**: Prevent excessive API calls

## Example: Complete Component

```svelte
<script>
    import { Head, Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import api from '@/services/api';
    import LoadingSpinner from '@/components/LoadingSpinner.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';

    export let auth = $props();
    export let initialData = $props() || [];

    let data = $state(initialData);
    let loading = $state(false);
    let error = $state(null);

    async function refreshData() {
        loading = true;
        error = null;

        try {
            const response = await api.get('/api/data');
            data = response.data;
        } catch (err) {
            error = err.response?.data?.message || 'Failed to fetch data';
        } finally {
            loading = false;
        }
    }

    onMount(() => {
        // Optionally refresh on mount
    });
</script>

<Head title="My Page" />

<AppLayout user={auth?.user}>
    <div class="py-12">
        {#if loading}
            <LoadingSpinner text="Loading data..." />
        {:else if error}
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                {error}
            </div>
        {:else}
            <div class="space-y-4">
                {#each data as item}
                    <div class="bg-white p-4 rounded shadow">
                        <StatusBadge status={item.status} />
                        <p>{item.name}</p>
                    </div>
                {/each}
            </div>
        {/if}

        <button
            on:click={refreshData}
            disabled={loading}
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
        >
            Refresh
        </button>
    </div>
</AppLayout>
```

## Navigation

Use Inertia's `Link` component for navigation:

```svelte
<script>
    import { Link } from '@inertiajs/svelte';
</script>

<Link href="/dashboard">Dashboard</Link>
<Link href="/integration-monitor">Integration Monitor</Link>
```

## Form Handling

```svelte
<script>
    import { router } from '@inertiajs/svelte';
    import api from '@/services/api';

    let formData = $state({
        name: '',
        email: '',
    });

    async function submit() {
        try {
            await api.post('/api/users', formData);
            router.reload();
        } catch (error) {
            console.error('Error:', error);
        }
    }
</script>

<form on:submit|preventDefault={submit}>
    <input bind:value={formData.name} />
    <input bind:value={formData.email} />
    <button type="submit">Submit</button>
</form>
```

## Next Steps

1. Add more reusable components
2. Implement form validation
3. Add error boundaries
4. Create loading skeletons
5. Add toast notifications
6. Implement real-time updates (WebSockets/Polling)

