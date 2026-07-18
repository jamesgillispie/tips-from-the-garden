<?php

namespace App\Support;

/**
 * Decides whether an inbound Postmark message genuinely came from its From
 * address. A sender is authenticated only on DMARC-style alignment: DKIM
 * valid for the From domain itself, or an SPF pass whose envelope domain
 * aligns with the From domain — regardless of the domain's published DMARC
 * policy (ADR 0001). The verdicts are read from the headers Postmark adds to
 * every inbound payload (SpamAssassin's X-Spam-Tests and Received-SPF).
 */
class SenderAuthentication
{
    public static function passes(array $headers, string $fromEmail): bool
    {
        $fromDomain = self::domainOf($fromEmail);

        if ($fromDomain === null) {
            return false;
        }

        return self::dkimAlignedPass($headers)
            || self::spfAlignedPass($headers, $fromDomain);
    }

    /**
     * DKIM_VALID_AU = SpamAssassin verified a signature from the author's
     * (From) domain, so alignment is baked into the test itself.
     */
    protected static function dkimAlignedPass(array $headers): bool
    {
        $tests = self::header($headers, 'X-Spam-Tests') ?? '';

        return in_array('DKIM_VALID_AU', preg_split('/[\s,]+/', strtoupper($tests)), true);
    }

    /**
     * An SPF pass vouches only for the envelope sender, so it counts just
     * when the envelope domain equals the From domain. Exact match, not
     * relaxed subdomain alignment: without a public-suffix list, subdomain
     * matching would let victim.github.io align with a pass for github.io.
     * Subdomain-envelope senders authenticate via the DKIM path instead.
     */
    protected static function spfAlignedPass(array $headers, string $fromDomain): bool
    {
        $spf = self::header($headers, 'Received-SPF');

        if ($spf === null || ! preg_match('/^\s*pass\b/i', $spf)) {
            return false;
        }

        if (! preg_match('/envelope-from=<?([^;>\s]+)>?/i', $spf, $matches)) {
            return false;
        }

        return self::domainOf($matches[1]) === $fromDomain;
    }

    protected static function domainOf(string $email): ?string
    {
        $at = strrpos($email, '@');

        if ($at === false) {
            return null;
        }

        return strtolower(substr($email, $at + 1)) ?: null;
    }

    /**
     * The Headers array carries the sender's own message headers alongside
     * the ones Postmark adds, and nothing marks whose is whose. A trusted
     * header is only usable when it appears exactly once — a duplicate means
     * someone shipped their own copy of the evidence, and we fail closed
     * rather than guess which instance to believe.
     */
    protected static function header(array $headers, string $name): ?string
    {
        $matches = array_values(array_filter(
            $headers,
            fn ($header) => strcasecmp($header['Name'] ?? '', $name) === 0,
        ));

        if (count($matches) !== 1) {
            return null;
        }

        return $matches[0]['Value'] ?? null;
    }
}
