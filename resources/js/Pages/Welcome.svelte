<script>
    import { Link } from '@inertiajs/svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
</script>

<svelte:head>
    <title>Welcome</title>
</svelte:head>

<AppLayout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-4">Welcome to Laravel 11 LTS</h1>
                    <p class="text-lg mb-4">
                        This project is configured with:
                    </p>
                    <ul class="list-disc list-inside space-y-2 mb-6">
                        <li>Laravel 11 LTS</li>
                        <li>Inertia.js with Svelte</li>
                        <li>Vite for asset bundling</li>
                        <li>TailwindCSS for styling</li>
                        <li>Laravel Breeze for authentication</li>
                        <li>Laravel Sanctum for API authentication</li>
                        <li>Spatie Laravel Permission for role management</li>
                    </ul>
                    <div class="flex gap-4">
                        <Link
                            href="/dashboard"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Dashboard
                        </Link>
                        <Link
                            href="/login"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Login
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>

