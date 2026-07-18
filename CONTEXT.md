# Tips from the Garden

Voice memos from the garden become polished journal entries in the gardener's
own voice. This glossary is the canonical language for the product; code and
copy should use these terms.

## Language

**Gardener**:
The person who records memos and receives journal entries. The only user of
the product; email address is their identity.
_Avoid_: User, customer, member

**Recording**:
A memo a gardener sends in — audio (recorded in the browser, uploaded, or
emailed) or a pasted transcript. Backed by `Submission`.
_Avoid_: Submission (in user-facing copy), upload

**Journal entry**:
The polished written piece produced from a recording, delivered by email and
readable on the desk. Backed by `Article`.
_Avoid_: Article (in user-facing copy), post, blog post

**Photo**:
An image a gardener captures alongside a recording, at capture time. It
belongs to the recording and is displayed with the resulting journal entry.
Photos only — never video.
_Avoid_: Image, attachment, media

**Desk**:
The signed-in dashboard with the Journal, Recordings, and My Voice tabs.
_Avoid_: Dashboard, account page

**Intake door**:
One of the entry points that creates a recording — in-browser recording,
web upload, pasted transcript, or inbound email. All doors funnel into the
same pipeline.
_Avoid_: channel, source (as a concept name; `source` remains a field value)

**Authenticated sender**:
A From address whose message passed DMARC-style alignment — DKIM valid for
the From domain itself, or SPF pass with the envelope sender's domain
matching the From domain. Alignment is required regardless of whether the
domain publishes a DMARC policy; a bare SPF or DKIM pass without alignment
does not qualify.
_Avoid_: verified sender, trusted sender

**Voice profile**:
A generated description of how a gardener writes, distilled from their
writing samples and injected into the writer's prompt.

**Writing sample**:
A banked piece of the gardener's own prose (delivered transcripts or
self-submitted text) that feeds voice profile regeneration.
