<script>
    export let size = 'md'; // sm, md, lg
    export let text = '';

    const sizes = {
        sm: 'h-4 w-4',
        md: 'h-8 w-8',
        lg: 'h-12 w-12',
    };
</script>

<div class="flex flex-col items-center justify-center">
    <div class="inline-block animate-spin rounded-full border-b-2 border-blue-600 {sizes[size]}"></div>
    {#if text}
        <p class="mt-2 text-sm text-gray-500">{text}</p>
    {/if}
</div>

