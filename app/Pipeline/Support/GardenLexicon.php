<?php

namespace App\Pipeline\Support;

use Illuminate\Support\Str;

/**
 * The garden lexicon — a curated vocabulary of plant, vegetable, herb, fruit,
 * and cultivar names common to New England gardens. This is the "skill" that
 * makes transcription garden-aware, and it does two jobs:
 *
 *  1. whisperPrompt() — a compact initial prompt fed to whisper.cpp so the
 *     transcriber is *primed* to hear and spell these names (e.g. "arugula",
 *     "Cherokee Purple", "McIntosh") rather than guessing phonetically.
 *
 *  2. normalize() — a safety net that rewrites known mishearings / variant
 *     spellings to their canonical form in a finished transcript
 *     (e.g. "rucola" → "arugula").
 *
 * ── How to curate ──
 *  • Add tricky names to HOTWORDS so whisper learns to spell them. Keep the
 *    list focused: whisper's prompt window is small (~224 tokens), so favour
 *    the names it actually gets wrong over an exhaustive catalogue.
 *  • Add confident, unambiguous fixes to CORRECTIONS. Keep these conservative —
 *    only mappings that are virtually always right in a garden context, so we
 *    never "correct" a word that was already fine. Anything that could be an
 *    ordinary word (e.g. "rocket" for arugula) is deliberately left out.
 */
class GardenLexicon
{
    /**
     * Names whispered into whisper's ear as context. Curate freely.
     *
     * @var list<string>
     */
    public const HOTWORDS = [
        // Leafy greens & brassicas
        'arugula', 'kale', 'Swiss chard', 'collards', 'bok choy', 'radicchio',
        'escarole', 'mesclun', 'kohlrabi', 'broccoli rabe', 'rapini',
        'Brussels sprouts', 'cauliflower', 'rutabaga',
        // Tomatoes — cultivars common in New England
        'Cherokee Purple', 'Brandywine', 'Sungold', 'San Marzano', 'Green Zebra',
        'Black Krim', 'Mortgage Lifter', 'Sweet 100', 'Early Girl', 'Mountain Magic', 'Defiant',
        // Squash & roots
        'delicata', 'butternut', 'Hubbard', 'kabocha', 'zucchini', 'pattypan',
        'celeriac', 'parsnip', 'leek', 'shallot', 'scallion', 'fennel',
        // Herbs
        'cilantro', 'Genovese basil', 'Thai basil', 'thyme', 'oregano', 'marjoram',
        'tarragon', 'chervil', 'lovage', 'sorrel', 'borage',
        // New England fruit — apples especially
        'McIntosh', 'Cortland', 'Honeycrisp', 'Macoun', 'Northern Spy', 'Baldwin',
        'Empire', 'highbush blueberry', 'Concord grape',
        // Regional & wild
        'fiddleheads', 'ramps', 'groundcherry', 'tomatillo', 'sunchoke',
        // Flowers
        'nasturtium', 'calendula', 'zinnia', 'cosmos', 'dahlia', 'peony', 'echinacea',
    ];

    /**
     * Confident variant / mishearing → canonical fixes. Matched whole-word and
     * case-insensitively; the canonical casing is used as-is, except a match
     * that began a sentence stays capitalized.
     *
     * @var array<string, string>
     */
    public const CORRECTIONS = [
        // The reported case: whisper renders arugula's Italian name with a C.
        'rucola' => 'arugula',
        'roquette' => 'arugula',
        // Cultivar names whisper tends to split or mangle.
        'sun gold' => 'Sungold',
        'brandy wine' => 'Brandywine',
        'cherokee purple' => 'Cherokee Purple',
        'san marzano' => 'San Marzano',
        'green zebra' => 'Green Zebra',
        'black krim' => 'Black Krim',
        'mortgage lifter' => 'Mortgage Lifter',
        // Greens & brassicas
        'radichio' => 'radicchio',
        'radiccio' => 'radicchio',
        'kohl rabi' => 'kohlrabi',
        'coal rabi' => 'kohlrabi',
        'pak choi' => 'bok choy',
        'bock choy' => 'bok choy',
        'boc choy' => 'bok choy',
        'broccoli rab' => 'broccoli rabe',
        'broccoli rob' => 'broccoli rabe',
        // Squash & roots
        'delacata' => 'delicata',
        'kabacha' => 'kabocha',
        // New England apples
        'macintosh' => 'McIntosh',
        'mac intosh' => 'McIntosh',
        'honey crisp' => 'Honeycrisp',
        // Wild & regional
        'fiddle heads' => 'fiddleheads',
        'ground cherry' => 'groundcherry',
    ];

    /**
     * A compact context string for whisper's --prompt. whisper biases decoding
     * toward words in its preceding "context", so this nudges it toward our
     * spellings.
     */
    public static function whisperPrompt(): string
    {
        return 'A New England gardener talking about their garden. Likely names: '
            .implode(', ', self::HOTWORDS).'.';
    }

    /**
     * Rewrite known mishearings / variant spellings to their canonical names.
     */
    public static function normalize(string $text): string
    {
        foreach (self::CORRECTIONS as $variant => $canonical) {
            // Whole-word, case-insensitive, unicode. Quote each word and join
            // with \s+ so a space in the variant matches any run of whitespace
            // ("sun  gold" still hits).
            $words = preg_split('/\s+/', $variant);
            $pattern = '/\b'.implode('\s+', array_map(fn ($w) => preg_quote($w, '/'), $words)).'\b/iu';

            $text = preg_replace_callback($pattern, function (array $m) use ($canonical) {
                // Preserve a sentence-start capital when the canonical is lowercase.
                if (self::startsUpper($m[0]) && ! self::startsUpper($canonical)) {
                    return Str::ucfirst($canonical);
                }

                return $canonical;
            }, $text);
        }

        return $text;
    }

    /** Does the string begin with an uppercase letter? */
    protected static function startsUpper(string $s): bool
    {
        if ($s === '') {
            return false;
        }

        $first = mb_substr($s, 0, 1);

        return mb_strtoupper($first) === $first && mb_strtolower($first) !== $first;
    }
}
