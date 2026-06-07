# Corpus drop zone

Drop scraped blog posts here (`.md`, `.txt`, `.html`, or `.json`), then run:

```bash
php artisan db:seed --class=Database\\Seeders\\CorpusSeeder
```

Each file becomes a writing sample on the practice account
(`practice@tipsfromthegarden.test`), used to tune voice-matching against a
known voice before real users exist.

JSON files may contain a single `{"title": "...", "body": "..."}` object or
an array of them.

These files are gitignored — the corpus never leaves your machine.
