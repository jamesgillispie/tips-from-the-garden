<?php

namespace Tests\Feature;

use App\Support\SenderAuthentication;
use Tests\TestCase;

/**
 * The "authenticated sender" boundary from ADR 0001: a From address counts as
 * authenticated only on DMARC-style alignment — DKIM valid for the From domain
 * itself, or an SPF pass whose envelope domain aligns with the From domain.
 */
class SenderAuthenticationTest extends TestCase
{
    public function test_dkim_valid_for_the_from_domain_authenticates(): void
    {
        // SpamAssassin's DKIM_VALID_AU = valid signature from the author's
        // (From) domain — alignment is baked into the test itself.
        $headers = [
            ['Name' => 'X-Spam-Tests', 'Value' => 'DKIM_SIGNED,DKIM_VALID,DKIM_VALID_AU,SPF_PASS'],
        ];

        $this->assertTrue(SenderAuthentication::passes($headers, 'gardener@gmail.com'));
    }

    public function test_a_message_with_no_authentication_evidence_fails(): void
    {
        $this->assertFalse(SenderAuthentication::passes([], 'gardener@gmail.com'));
    }

    public function test_a_dkim_signature_from_some_other_domain_is_not_enough(): void
    {
        // DKIM_VALID without DKIM_VALID_AU: a valid signature exists, but not
        // from the From domain — exactly what a spoofer signing with their own
        // domain produces.
        $headers = [
            ['Name' => 'X-Spam-Tests', 'Value' => 'DKIM_SIGNED,DKIM_VALID'],
        ];

        $this->assertFalse(SenderAuthentication::passes($headers, 'victim@gmail.com'));
    }

    public function test_an_spf_pass_with_an_aligned_envelope_domain_authenticates(): void
    {
        // A personal domain with SPF but no DKIM signing: envelope domain
        // matches the From domain, so the pass speaks for the From address.
        $headers = [
            ['Name' => 'Received-SPF', 'Value' => 'pass (manorhousegardens.org: Sender is authorized to use gardener@manorhousegardens.org) client-ip=203.0.113.7; envelope-from=gardener@manorhousegardens.org; helo=mail.manorhousegardens.org;'],
        ];

        $this->assertTrue(SenderAuthentication::passes($headers, 'gardener@manorhousegardens.org'));
    }

    public function test_an_spf_pass_for_the_attackers_own_envelope_does_not_vouch_for_a_forged_from(): void
    {
        // The classic spoof: envelope from attacker.example (their SPF passes),
        // From header forged as the victim. Without alignment this would sail
        // through — it must not.
        $headers = [
            ['Name' => 'Received-SPF', 'Value' => 'pass (attacker.example: Sender is authorized) client-ip=203.0.113.66; envelope-from=bulk@attacker.example;'],
        ];

        $this->assertFalse(SenderAuthentication::passes($headers, 'victim@gmail.com'));
    }

    public function test_an_spf_softfail_is_not_a_pass_even_when_aligned(): void
    {
        $headers = [
            ['Name' => 'Received-SPF', 'Value' => 'softfail (transitioning) envelope-from=gardener@manorhousegardens.org;'],
        ];

        $this->assertFalse(SenderAuthentication::passes($headers, 'gardener@manorhousegardens.org'));
    }

    public function test_alignment_is_exact_a_bounce_subdomain_envelope_is_not_enough(): void
    {
        // Alignment means the envelope domain EQUALS the From domain. Relaxed
        // subdomain matching without a public-suffix list would let
        // victim.github.io "align" with an SPF pass for github.io — so
        // subdomain senders must come through the DKIM path instead.
        $headers = [
            ['Name' => 'Received-SPF', 'Value' => 'pass (authorized) envelope-from=gardener@bounces.manorhousegardens.org;'],
        ];

        $this->assertFalse(SenderAuthentication::passes($headers, 'gardener@manorhousegardens.org'));
    }

    public function test_duplicate_spam_tests_headers_fail_closed(): void
    {
        // The Headers array carries the attacker's own message headers too.
        // If a message arrives with two X-Spam-Tests headers we cannot know
        // which one Postmark wrote — so forged evidence kills the pass
        // instead of granting it.
        $headers = [
            ['Name' => 'X-Spam-Tests', 'Value' => 'DKIM_SIGNED,DKIM_VALID,DKIM_VALID_AU'],
            ['Name' => 'X-Spam-Tests', 'Value' => 'SPF_FAIL'],
        ];

        $this->assertFalse(SenderAuthentication::passes($headers, 'victim@gmail.com'));
    }

    public function test_duplicate_received_spf_headers_fail_closed(): void
    {
        $headers = [
            ['Name' => 'Received-SPF', 'Value' => 'pass (authorized) envelope-from=victim@gmail.com;'],
            ['Name' => 'Received-SPF', 'Value' => 'fail (unauthorized) envelope-from=bulk@attacker.example;'],
        ];

        $this->assertFalse(SenderAuthentication::passes($headers, 'victim@gmail.com'));
    }
}
