<?php

namespace Tests\Unit;

use App\Modules\Events\Support\PostExecutionVerificationIdentity;
use PHPUnit\Framework\TestCase;

class PostExecutionVerificationIdentityTest extends TestCase
{
    public function test_transition_reads_accept_exactly_the_legacy_and_canonical_identities(): void
    {
        $this->assertSame([
            'App\\Models\\PostExecutionVerification',
            'App\\Modules\\Events\\Models\\PostExecutionVerification',
        ], PostExecutionVerificationIdentity::acceptedTypes());

        foreach (PostExecutionVerificationIdentity::acceptedTypes() as $type) {
            $this->assertTrue(PostExecutionVerificationIdentity::accepts($type));
        }

        $this->assertFalse(PostExecutionVerificationIdentity::accepts('PostExecutionVerification'));
    }

    public function test_writer_remains_on_the_legacy_identity_before_cutover(): void
    {
        $this->assertSame(
            'App\\Models\\PostExecutionVerification',
            PostExecutionVerificationIdentity::currentWriteType()
        );
    }
}
