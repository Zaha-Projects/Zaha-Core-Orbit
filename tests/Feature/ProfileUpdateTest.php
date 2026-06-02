<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_phone_and_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'phone' => '0790000000',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Original Name',
                'phone' => '0791111111',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('0791111111', $user->phone);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }
}
