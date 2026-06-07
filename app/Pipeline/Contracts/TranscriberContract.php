<?php

namespace App\Pipeline\Contracts;

use App\Pipeline\Data\TranscriptionResult;

interface TranscriberContract
{
    /**
     * Transcribe an audio file (absolute path) to text.
     */
    public function transcribe(string $audioPath): TranscriptionResult;

    /**
     * Identifier stored on the transcript, e.g. "whisper_cpp:large-v3-turbo".
     */
    public function identifier(): string;
}
