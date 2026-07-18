# Email sender identity comes from authentication results, not the From header

The inbound-email door files a memo by its From address, and a delivered memo
feeds the sender's voice profile — so a forged From header would let anyone
inject articles into a victim's account and permanently poison their voice
model. We decided the Postmark webhook must treat a sender as identified only
when the message passes DMARC-style alignment (DKIM valid for the From domain
itself, or SPF pass with an aligned envelope domain), enforced regardless of
whether the From domain publishes a DMARC policy. Authentication is the first
gate in the webhook, before the audio and account checks.

## Considered Options

- **Per-account secret intake addresses** — cryptographically stronger, but
  forces every gardener to save an unguessable address and breaks the
  "just email your memo in" simplicity. Rejected.
- **Confirmation loop** (hold each emailed memo until the account holder
  clicks a link) — kills the send-and-forget point of the email door. Rejected.
- **Quarantine state** for unauthenticated memos — gentlest UX but adds a
  Submission state, desk UI, and an approval flow for what should be a rare
  edge. Rejected for now; can be added if real users hit auth failures often.

## Consequences

- **Failure policy is notify-and-drop.** An unauthenticated memo whose From
  matches an account is discarded; the account holder gets a rate-limited
  "couldn't verify this was you" notice. This is a deliberate, narrow
  exception to backscatter avoidance: the notice goes only to our own user's
  address, never to an arbitrary one.
- **Replies are gated on authentication.** `NoAccountFound` / `NoAudioFound`
  are sent only to authenticated senders; unauthenticated mail from a
  non-account address is silently dropped (logged only). The deliberate
  silence is an exception to the "pipeline never goes silent" principle —
  unauthenticated strangers are not owed a reply, and answering them would
  make the app a backscatter source.
- **Custom domains without SPF/DKIM lose the email door.** Since an account
  has exactly one email address, a gardener whose address is on a domain with
  no auth records cannot pass alignment and must use the web doors until
  their domain gains basic auth. Consumer providers (Gmail, iCloud, Yahoo,
  Outlook) all pass today.
- **Duplicate evidence fails closed.** The inbound `Headers` array mixes the
  sender's own headers with the ones Postmark adds, and nothing marks whose
  is whose — so a trusted header (`Received-SPF`, `X-Spam-Tests`) counts only
  when it appears exactly once. An attacker shipping their own copy of the
  evidence kills the pass instead of granting it.
- **SPF alignment is exact, not relaxed.** DMARC's relaxed mode compares
  organizational domains via the public-suffix list; without a PSL, suffix
  matching would let `victim.github.io` align with an SPF pass for
  `github.io`. The envelope domain must equal the From domain; senders using
  bounce subdomains authenticate via the DKIM path instead.
- The exact Postmark carriers of the verdicts (`Received-SPF`, SpamAssassin
  `X-Spam-Tests` flags such as `DKIM_VALID_AU`) are an implementation detail
  to verify against the live payload, not part of this decision. Until that
  verification happens, note the failure mode: if Postmark's headers are
  absent or named differently, authentication fails closed for **every**
  sender and the email door goes quiet.
