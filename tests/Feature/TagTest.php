<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\HtmlSanitizationMiddleware::class);
        $this->user = User::factory()->create();
    }

    public function test_tags_index_page_loads()
    {
        Tag::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/tags');

        $response->assertStatus(200);
    }

    public function test_tags_data_returns_json()
    {
        Tag::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/tags/data');

        $response->assertStatus(200);
    }

    public function test_tag_autocomplete()
    {
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'Laravel']);

        $response = $this->actingAs($this->user)
            ->getJson('/tags/autocomplete?query=Lar');

        $response->assertStatus(200);
    }

    public function test_tag_requires_authentication()
    {
        $response = $this->get('/tags');
        $this->assertNotEquals(200, $response->status());
    }
}
