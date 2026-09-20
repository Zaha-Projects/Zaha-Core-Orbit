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

    public function test_writer_uses_canonical_identity_after_cutover(): void
    {
        $this->assertSame(
            'App\\Modules\\Events\\Models\\PostExecutionVerification',
            PostExecutionVerificationIdentity::currentWriteType()
        );
    }

    public function test_only_the_canonical_model_class_is_installed(): void
    {
        $this->assertFalse(class_exists(PostExecutionVerificationIdentity::LEGACY));
        $this->assertTrue(class_exists(PostExecutionVerificationIdentity::CANONICAL));
        $model = new (PostExecutionVerificationIdentity::CANONICAL)();
        $this->assertSame('post_execution_verifications', $model->getTable());
    }

}
