<script>
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AppLayout from '@/Layouts/AppLayout.svelte';
    import api from '@/services/api';

    export let auth = null;
    export let mappingFile = null;
    export let mappingContent = '';
    export let availableMappings = [];
    // These props are sent by Inertia but may not be used directly
    export let errors = {};
    export let flash = {};

    let content = mappingContent;
    let selectedFile = mappingFile;
    let loading = false;
    let saving = false;
    let error = null;
    let success = null;
    let validationErrors = {};

    // Load mapping file using Axios
    async function loadMapping(fileName) {
        loading = true;
        error = null;
        validationErrors = {};

        try {
            const response = await api.get(`/mappings/${fileName}`);
            content = response.data.content || '';
            selectedFile = fileName;
        } catch (err) {
            error = err.response?.data?.message || 'Failed to load mapping file';
            console.error('Load mapping error:', err);
        } finally {
            loading = false;
        }
    }

    // Save mapping file using Axios
    async function saveMapping() {
        if (!selectedFile) {
            error = 'Please select a mapping file';
            return;
        }

        saving = true;
        error = null;
        success = null;
        validationErrors = {};

        try {
            await api.put(`/mappings/${selectedFile}`, {
                content,
            });

            success = 'Mapping file saved successfully';
            
            // Clear success message after 3 seconds
            setTimeout(() => {
                success = null;
            }, 3000);
        } catch (err) {
            if (err.response?.status === 422) {
                validationErrors = err.response.data.errors || {};
            }
            error = err.response?.data?.message || 'Failed to save mapping file';
            console.error('Save mapping error:', err);
        } finally {
            saving = false;
        }
    }

    // Validate YAML syntax
    function validateYaml() {
        try {
            // Basic YAML validation (in production, use a YAML parser)
            if (!content.trim()) {
                error = 'Mapping content cannot be empty';
                return false;
            }

            // Check for basic YAML structure
            if (!content.includes('fields:') && !content.includes('target:')) {
                error = 'Invalid YAML structure: missing required fields';
                return false;
            }

            return true;
        } catch (err) {
            error = 'YAML validation failed: ' + err.message;
            return false;
        }
    }

    // Create new mapping file
    async function createNewMapping() {
        const fileName = prompt('Enter mapping file name (e.g., product-to-sap.yaml):');
        
        if (!fileName || !fileName.endsWith('.yaml')) {
            error = 'Invalid file name. Must end with .yaml';
            return;
        }

        loading = true;
        error = null;

        try {
            const response = await api.post('/mappings', {
                filename: fileName,
                content: `target: ${fileName.replace('.yaml', '')}\n\nfields:\n  # Add your field mappings here\n`,
            });

            selectedFile = fileName;
            content = response.data.content || '';
            
            // Reload available mappings
            router.reload({ only: ['availableMappings'] });
        } catch (err) {
            error = err.response?.data?.message || 'Failed to create mapping file';
            console.error('Create mapping error:', err);
        } finally {
            loading = false;
        }
    }

    // Test mapping transformation
    async function testMapping() {
        if (!selectedFile) {
            error = 'Please select a mapping file';
            return;
        }

        if (!validateYaml()) {
            return;
        }

        loading = true;
        error = null;

        try {
            const testData = {
                order_number: 'ORD-001',
                order_date: '2024-01-15',
                total_amount: 1250.50,
                currency: 'USD',
            };

            const response = await api.post('/mappings/test', {
                mapping_file: selectedFile,
                test_data: testData,
            });

            // Show transformation result
            alert('Transformation Result:\n\n' + JSON.stringify(response.data.result, null, 2));
        } catch (err) {
            error = err.response?.data?.message || 'Mapping test failed';
            console.error('Test mapping error:', err);
        } finally {
            loading = false;
        }
    }

    onMount(() => {
        if (mappingFile && mappingContent) {
            selectedFile = mappingFile;
            content = mappingContent;
        }
    });
</script>

<svelte:head>
    <title>Mapping Editor</title>
</svelte:head>

<AppLayout user={auth?.user}>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Mapping Editor</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Edit YAML mapping files for SAP data transformation
                    </p>
                </div>
                <div class="flex space-x-2">
                    <button
                        on:click={createNewMapping}
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                    >
                        New Mapping
                    </button>
                </div>
            </div>

            <!-- Messages -->
            {#if error}
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {error}
                </div>
            {/if}

            {#if success}
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {success}
                </div>
            {/if}

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar: File List -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 mb-4">Mapping Files</h3>
                        <div class="space-y-2">
                            {#each availableMappings as mapping}
                                <button
                                    on:click={() => loadMapping(mapping)}
                                    class="w-full text-left px-3 py-2 text-sm rounded-md {
                                        selectedFile === mapping
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-700 hover:bg-gray-50'
                                    }"
                                >
                                    {mapping}
                                </button>
                            {/each}
                        </div>
                    </div>
                </div>

                <!-- Main Content: Editor -->
                <div class="lg:col-span-3">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <!-- Editor Header -->
                            <div class="mb-4 flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {selectedFile || 'Select a mapping file'}
                                    </h3>
                                    {#if selectedFile}
                                        <p class="text-sm text-gray-500 mt-1">
                                            Edit YAML mapping configuration
                                        </p>
                                    {/if}
                                </div>
                                <div class="flex space-x-2">
                                    {#if selectedFile}
                                        <button
                                            on:click={testMapping}
                                            disabled={loading || saving}
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 disabled:opacity-50"
                                        >
                                            Test Mapping
                                        </button>
                                        <button
                                            on:click={saveMapping}
                                            disabled={loading || saving}
                                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                                        >
                                            {saving ? 'Saving...' : 'Save'}
                                        </button>
                                    {/if}
                                </div>
                            </div>

                            <!-- Editor -->
                            {#if loading}
                                <div class="text-center py-12">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                    <p class="mt-2 text-sm text-gray-500">Loading mapping file...</p>
                                </div>
                            {:else if selectedFile}
                                <div class="border border-gray-300 rounded-md">
                                    <textarea
                                        bind:value={content}
                                        class="w-full h-96 p-4 font-mono text-sm border-0 focus:ring-0 focus:outline-none resize-none"
                                        placeholder="Enter YAML mapping configuration..."
                                    ></textarea>
                                </div>

                                <!-- YAML Help -->
                                <div class="mt-4 p-4 bg-gray-50 rounded-md">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">YAML Mapping Syntax</h4>
                                    <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                        <li>Use <code class="bg-gray-200 px-1 rounded">source</code> to map from source field</li>
                                        <li>Use <code class="bg-gray-200 px-1 rounded">transform</code> for data transformation</li>
                                        <li>Use <code class="bg-gray-200 px-1 rounded">default</code> for default values</li>
                                        <li>Use <code class="bg-gray-200 px-1 rounded">required: true</code> for required fields</li>
                                    </ul>
                                </div>
                            {:else}
                                <div class="text-center py-12 text-gray-500">
                                    <p>Select a mapping file from the sidebar to start editing</p>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>

