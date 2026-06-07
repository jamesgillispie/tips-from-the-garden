<?php

namespace App\Pipeline\Transcribers;

use App\Pipeline\Contracts\TranscriberContract;
use App\Pipeline\Data\TranscriptionResult;

/**
 * Plumbing-test driver: returns a canned transcript without touching
 * any audio tooling. Set TRANSCRIBER_DRIVER=fake.
 */
class FakeTranscriber implements TranscriberContract
{
    public function transcribe(string $audioPath): TranscriptionResult
    {
        return new TranscriptionResult(
            text: 'So I am out by the raised beds this morning and the tomatoes have '
                .'finally set fruit, the Cherokee Purples, which honestly I did not '
                .'expect after that cold snap we had. Um, the trick I think was the '
                .'row cover, I left it on two weeks longer than usual. Also note to '
                .'self, the basil next to them is getting leggy, needs pinching back. '
                .'Oh and the squash bugs are back on the zucchini, I am going to try '
                .'the duct tape trick this year instead of spraying.',
            durationSeconds: 42.0,
        );
    }

    public function identifier(): string
    {
        return 'fake:transcriber';
    }
}
