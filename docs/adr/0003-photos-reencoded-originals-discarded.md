# Photos are re-encoded on intake; originals are discarded

Every photo is passed through Imagick on intake — converted to JPEG, capped
at ~2000px on the long edge, EXIF stripped — and only that re-encode (plus a
thumbnail) is stored. The uploaded original is thrown away.

Two reasons, in order:

1. **Privacy.** iPhone photos carry EXIF GPS of exactly where they were
   taken — a gardener's garden is usually their home — and journal entries
   are viewable by anyone holding the `download_token` link. Stripping EXIF
   at the door means no stored object can leak a location, ever.
2. **Predictable cost.** One ~300–600 KB object per photo instead of 3–10 MB
   originals keeps the S3 bill flat and photo serving fast through the app
   proxy.

The re-encode pass also collapses the HEIC problem: iOS converts HEIC→JPEG
on web upload anyway, and anything that slips through raw (e.g. a macOS
Finder file-pick) is normalised by the same Imagick pass.

## Consequences

- Discarding originals is irreversible, and is acceptable because the
  gardener's phone keeps the true original — we store a display copy, not an
  archive.
- Imagick with HEIF support is a hosting requirement (present on the current
  Herd setup).
