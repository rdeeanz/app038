<script>
    export let status = '';
    export let variant = 'default'; // default, success, warning, error

    const variants = {
        default: 'bg-gray-100 text-gray-800',
        success: 'bg-green-100 text-green-800',
        warning: 'bg-yellow-100 text-yellow-800',
        error: 'bg-red-100 text-red-800',
        info: 'bg-blue-100 text-blue-800',
    };

    const statusColors = {
        completed: 'success',
        success: 'success',
        active: 'success',
        connected: 'success',
        pending: 'warning',
        processing: 'info',
        failed: 'error',
        error: 'error',
        disconnected: 'error',
    };

    $: colorClass = variants[statusColors[status?.toLowerCase()] || variant] || variants.default;
</script>

<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {colorClass}">
    {status || 'Unknown'}
</span>

