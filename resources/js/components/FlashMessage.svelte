<script>
    import { page } from '@inertiajs/svelte';
    import { onMount } from 'svelte';

    let show = false;
    let message = '';
    let type = 'success'; // success, error, warning, info

    $: {
        if ($page.props.flash?.message) {
            message = $page.props.flash.message;
            type = $page.props.flash.type || 'success';
            show = true;

            // Auto-hide after 5 seconds
            setTimeout(() => {
                show = false;
            }, 5000);
        }

        if ($page.props.flash?.error) {
            message = $page.props.flash.error;
            type = 'error';
            show = true;

            setTimeout(() => {
                show = false;
            }, 5000);
        }
    }

    function close() {
        show = false;
    }

    const typeClasses = {
        success: 'bg-green-50 border-green-200 text-green-700',
        error: 'bg-red-50 border-red-200 text-red-700',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-700',
        info: 'bg-blue-50 border-blue-200 text-blue-700',
    };
</script>

{#if show && message}
    <div class="fixed top-4 right-4 z-50 max-w-md w-full">
        <div class="border rounded-lg shadow-lg p-4 {typeClasses[type]}">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium">{message}</p>
                <button
                    on:click={close}
                    class="ml-4 text-gray-400 hover:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
{/if}

