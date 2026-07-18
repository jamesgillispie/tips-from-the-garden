# Photos are served through the app from a private S3 bucket

Photos captured with a recording are stored in a private S3 bucket (audio
stays on local disk — whisper.cpp needs a local file) and served only
through an app route gated by the journal entry's `download_token`, the same
no-login trust model as the entry itself. Photo URLs get baked into
delivered `ArticleReady` emails, which live in inboxes forever, so the URL
scheme is effectively permanent — that's what makes this a real decision
rather than a swappable detail.

## Considered Options

- **Temporary signed S3 URLs** — offloads bandwidth to S3, but signatures
  cap at 7 days, so photos embedded in delivered emails break after a week.
  Rejected: email is the primary delivery surface.
- **Public-read objects with unguessable keys** — permanent URLs, zero app
  bandwidth, but leaked URLs stay live until deletion and the bucket name is
  exposed in every entry. Rejected: proxying keeps revocation and the trust
  model in one place at negligible bandwidth cost for this scale.

## Consequences

- Every photo view flows through the app (Mac + tunnel). Cache headers make
  this trivial at current scale; a CDN or public bucket would be the escape
  hatch if scale ever demands it — but old emails would keep the app-proxied
  URLs, so the proxy route must live as long as delivered entries do.
- Deleting a recording/entry (or an account purge) deletes the S3 objects,
  which is also the revocation mechanism.
