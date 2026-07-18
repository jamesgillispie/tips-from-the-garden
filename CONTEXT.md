# Tips from the Garden

Voice memos from the garden become polished articles in the sender's own
voice. Gardeners submit memos through several intake doors; a pipeline
transcribes, writes, and delivers the article, and each delivered memo
teaches the system more about the gardener's voice.

## Language

**Authenticated sender**:
A From address whose message passed DMARC-style alignment — DKIM valid for
the From domain itself, or SPF pass with the envelope sender's domain
matching the From domain. Alignment is required regardless of whether the
domain publishes a DMARC policy; a bare SPF or DKIM pass without alignment
does not qualify.
_Avoid_: verified sender, trusted sender

**Intake door**:
One of the entry points that creates a Submission — in-browser recording,
web upload, pasted transcript, or inbound email. All doors funnel into the
same pipeline.
_Avoid_: channel, source (as a concept name; `source` remains a field value)
