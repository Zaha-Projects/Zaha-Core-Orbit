<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Movement Phase 4B (plan §18.3, Revision 5): old B URLs redirect to the new request list for one release.
 * Database test (the web stack writes audit rows) — disposable database only.
 */
class MovementLegacyTransportRequestRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_b_urls_redirect_to_the_new_request_list(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        foreach ([
            ['get', '/dashboard/transport/requests'],
            ['post', '/dashboard/transport/requests'],
            ['patch', '/dashboard/transport/requests/5/process'],
            ['patch', '/dashboard/transport/requests/5/feedback'],
            ['get', '/dashboard/transport/requests/5/feedback'],
        ] as [$method, $uri]) {
            $this->actingAs($user)->{$method}($uri)->assertRedirect('/dashboard/movement/requests');
        }

        // Route names used by stored links keep resolving, as redirects.
        $this->assertSame(url('/dashboard/transport/requests'), route('role.transport.requests.index'));
        $this->assertSame(url('/dashboard/transport/requests/7/process'), route('role.transport.requests.process', 7));
        $this->assertSame(url('/dashboard/transport/requests/7/feedback'), route('role.transport.requests.feedback', 7));
    }
}
