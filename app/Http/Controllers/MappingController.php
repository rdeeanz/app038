<?php

namespace App\Http\Controllers;

use App\Modules\ERPIntegration\Services\YamlMappingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MappingController extends Controller
{
    public function __construct(
        protected YamlMappingService $mappingService
    ) {}

    /**
     * Display the mapping editor
     */
    public function index(Request $request): Response
    {
        $mappingFile = $request->query('file');
        $mappingContent = '';

        if ($mappingFile) {
            $mappingContent = $this->loadMappingContent($mappingFile);
        }

        $availableMappings = $this->getAvailableMappings();

        return Inertia::render('MappingEditor', [
            'mappingFile' => $mappingFile,
            'mappingContent' => $mappingContent,
            'availableMappings' => $availableMappings,
        ]);
    }

    /**
     * Get mapping file content via API
     */
    public function show(string $filename)
    {
        $content = $this->loadMappingContent($filename);

        if ($content === null) {
            return response()->json([
                'message' => 'Mapping file not found',
            ], 404);
        }

        return response()->json([
            'content' => $content,
        ]);
    }

    /**
     * Create new mapping file
     */
    public function store(Request $request)
    {
        $request->validate([
            'filename' => ['required', 'string', 'regex:/^[a-z0-9\-]+\.yaml$/'],
            'content' => ['required', 'string'],
        ]);

        $filename = $request->input('filename');
        $content = $request->input('content');
        $path = base_path("config/mappings/{$filename}");

        File::put($path, $content);

        return response()->json([
            'message' => 'Mapping file created successfully',
            'content' => $content,
        ], 201);
    }

    /**
     * Update mapping file
     */
    public function update(Request $request, string $filename)
    {
        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $content = $request->input('content');
        $path = base_path("config/mappings/{$filename}");

        if (!File::exists($path)) {
            return response()->json([
                'message' => 'Mapping file not found',
            ], 404);
        }

        File::put($path, $content);

        return response()->json([
            'message' => 'Mapping file updated successfully',
        ]);
    }

    /**
     * Test mapping transformation
     */
    public function test(Request $request)
    {
        $request->validate([
            'mapping_file' => ['required', 'string'],
            'test_data' => ['required', 'array'],
        ]);

        try {
            $result = $this->mappingService->transform(
                $request->input('test_data'),
                $request->input('mapping_file')
            );

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Load mapping content from file
     */
    protected function loadMappingContent(string $filename): ?string
    {
        $path = base_path("config/mappings/{$filename}");

        if (!File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    /**
     * Get list of available mapping files
     */
    protected function getAvailableMappings(): array
    {
        $path = base_path('config/mappings');
        
        if (!File::isDirectory($path)) {
            return [];
        }

        $files = File::files($path);
        $mappings = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'yaml' || $file->getExtension() === 'yml') {
                $mappings[] = $file->getFilename();
            }
        }

        return $mappings;
    }
}

