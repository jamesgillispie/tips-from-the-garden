# ADR 0005: EU AI Transparency for AI-Assisted Articles

**Status:** Accepted and implemented  
**Date:** 2026-08-15  
**Related:** [ADR 0004](0004-ai-user-consent.md), [implementation reference](../ai-opt-in.md)

## Context

Article 50 of the EU AI Act applies from 2 August 2026 and introduces
transparency duties for certain AI systems and AI-generated or manipulated
content. The exact obligations depend on facts including whether an actor is a
provider or deployer, the kind of content, its purpose, and whether a person
assumes editorial responsibility.

Tips From The Garden turns a gardener’s own recording or notes into a public,
downloadable article. The output is AI-assisted rather than autonomous: the
user supplies the source material and remains the author. Garden writing will
usually not be text published to inform the public on matters of public
interest, and human editorial responsibility may provide an additional
exception where that part of Article 50 would otherwise apply.

Those scope questions are fact-specific and should receive legal review before
the Act’s application date. They do not prevent us from adopting clearer
transparency now.

## Decision

Every article produced by `WriteArticle` will be visibly and
machine-readably declared as AI-assisted. We apply this consistently even when
a particular article may fall outside mandatory disclosure scope.

This is a product-trust and compliance-readiness decision, not a claim that
every Article 50 duty legally applies to this service or that these measures
alone guarantee compliance.

## Implementation

### Provenance in the database

`articles` includes:

```text
is_ai_assisted  Boolean declaration
ai_model        Writer identifier used for generation
```

`WriteArticle` records both fields. Articles predating the migration are
backfilled as AI-assisted because the existing application created all of them
through the writer pipeline. A manually created non-AI article remains
unlabeled.

`submissions.ai_used` separately records whether any model-backed stage was
invoked, including local whisper.cpp transcription.

### Visible declaration

AI-assisted articles display the official EU basic AI icon and the words:

```text
AI-assisted article
```

The label appears before the title so it is visible at first exposure and does
not depend on hover, an overlay, JavaScript or an authenticated session.

The declaration persists in every shareable output:

| Surface | Declaration |
|---|---|
| Public article page | EU icon and visible label before title |
| PDF | EU icon/label near title and “AI-assisted” in footer |
| Markdown download | Visible blockquote plus HTML provenance comment |
| Article-ready email | Plain-language AI-assisted notice |

The visible text carries the meaning; the image has an empty alt attribute to
avoid duplicate screen-reader output, while the containing label has an
accessible description.

### Machine-readable declaration

The public HTML layout receives article provenance and emits:

```html
<meta name="generator:ai-assisted" content="true">
<meta name="generator:ai-model" content="ollama:qwen3:32b">
<meta name="ai-content-declaration" content="AI-assisted">
```

Markdown downloads include an HTML comment with the model identifier. PDF HTML
contains a corresponding provenance comment before rendering.

These are explicit application metadata, not a claim of compatibility with a
formal provenance standard such as C2PA. C2PA or Content Credentials remain a
possible future enhancement.

### Official icon assets

Assets downloaded from the European Commission’s official icon packages are
stored in:

```text
public/icons/eu-ai-labels/
```

The implementation uses:

```text
ai-label-black.svg  Web
ai-label-black.png  PDF
```

AI-generated and AI-modified variants are retained for future content types.
Before redistributing the assets outside this application, re-check the current
terms and usage guidance on the Commission source page.

### Transparency before processing

Output labeling complements, rather than replaces, consent:

- The privacy banner explains transcription, writing and voice learning before
  they are enabled.
- Account Settings explains where audio and transcript text go.
- The upload screen shows which stages are currently available.
- Monthly browser/email check-ins re-state the current choices.

See ADR 0004 for the authorization and reminder design.

## Compliance Readiness Checklist

| Measure | Implementation |
|---|---|
| Inform users before model-backed processing | Privacy banner and account settings |
| Permit refusal and later withdrawal | Conservative default and master switch |
| Separate materially different AI uses | Three granular feature controls |
| Declare AI assistance at first exposure | Icon/text before public article title |
| Preserve declaration in downloads | PDF and Markdown labels |
| Add machine-readable provenance | HTML meta tags and download comments |
| Avoid labeling non-AI output | `is_ai_assisted` conditional rendering |
| Keep an audit signal | `submissions.ai_used`, timestamps and article model identifier |
| Maintain ongoing awareness | Browser/email monthly check-in |
| Make email actions resistant to scanners | Signed GET confirmation page plus CSRF POST |

## Consequences

### Positive

- Readers do not have to infer whether AI shaped an article.
- The declaration survives common sharing and download paths.
- Historical AI output is not accidentally presented as human-only.
- The implementation is ready for future policy or icon-guidance adjustments
  because provenance is stored independently of presentation.

### Negative

- The visible label adds a small amount of interface and PDF chrome.
- Model identifiers become public metadata; configuration names should not
  contain secrets.
- Custom meta tags are not cryptographic proof and can be removed by someone
  who copies the content.
- Legal scope and official guidance can change before or after August 2026, so
  this ADR must be reviewed rather than treated as final legal advice.

## Alternatives Considered

1. **Label only content clearly covered by “matters of public interest”** —
   rejected because classification would be subjective and inconsistent.
2. **Rely only on account consent** — rejected because readers and downstream
   recipients are not necessarily the account holder.
3. **Use metadata without a visible label** — rejected because metadata is not
   readily perceivable and disappears in screenshots or print.
4. **Use a text label without stored provenance** — rejected because edited or
   manually created content could not be distinguished reliably.
5. **Implement C2PA immediately** — deferred; stronger but substantially more
   complex than the current article/PDF pipeline requires.

## References

- [EU AI Act Article 50](https://ai-act-service-desk.ec.europa.eu/en/ai-act/article-50)
- [EU icons for labeling AI-generated content](https://digital-strategy.ec.europa.eu/en/policies/eu-icons-labelling-ai-generated-content)
- [Code of Practice on marking and labelling AI-generated content](https://digital-strategy.ec.europa.eu/en/policies/code-practice-ai-generated-content)
- [European Commission AI Act Service Desk](https://ai-act-service-desk.ec.europa.eu/)
