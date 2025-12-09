<?php

namespace Tests\Feature\Inertia;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MappingEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapping_editor_page_renders_with_inertia(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get('/mapping-editor');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('MappingEditor')
            ->has('auth.user')
            ->has('availableMappings')
        );
    }

    public function test_mapping_editor_loads_specific_file(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get('/mapping-editor?file=order-to-sap.yaml');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('MappingEditor')
            ->where('mappingFile', 'order-to-sap.yaml')
            ->has('mappingContent')
        );
    }
}

