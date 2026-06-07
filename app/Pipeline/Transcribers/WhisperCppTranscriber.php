<?php

namespace App\Pipeline\Transcribers;

use App\Pipeline\Contracts\TranscriberContract;
use App\Pipeline\Data\TranscriptionResult;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class WhisperCppTranscriber implements TranscriberContract
{
    public function __construct(
        protected string $binary,
        protected string $model,
        protected string $ffmpeg,
        protected int $threads = 8,
    ) {}

    public function transcribe(string $audioPath): TranscriptionResult
    {
        if (! is_file($audioPath)) {
            throw new RuntimeException("Audio file not found: {$audioPath}");
        }

        // whisper.cpp wants 16kHz mono WAV — voice memos arrive as m4a etc.
        $wavPath = sys_get_temp_dir().'/'.uniqid('tftg_', true).'.wav';

        try {
            $convert = Process::timeout(300)->run([
                $this->ffmpeg, '-y', '-i', $audioPath,
                '-ar', '16000', '-ac', '1', '-c:a', 'pcm_s16le',
                $wavPath,
            ]);

            if (! $convert->successful()) {
                throw new RuntimeException('ffmpeg failed: '.$convert->errorOutput());
            }

            $duration = $this->probeDuration($audioPath);

            $run = Process::timeout(3600)->run([
                $this->binary,
                '-m', $this->model,
                '-f', $wavPath,
                '-t', (string) $this->threads,
                '--no-timestamps',
                '--language', 'auto',
            ]);

            if (! $run->successful()) {
                throw new RuntimeException('whisper.cpp failed: '.$run->errorOutput());
            }

            $text = trim($run->output());

            if ($text === '') {
                throw new RuntimeException('whisper.cpp produced an empty transcript.');
            }

            return new TranscriptionResult(text: $text, durationSeconds: $duration);
        } finally {
            @unlink($wavPath);
        }
    }

    public function identifier(): string
    {
        return 'whisper_cpp:'.basename($this->model);
    }

    protected function probeDuration(string $audioPath): ?float
    {
        $ffprobe = str_replace('ffmpeg', 'ffprobe', $this->ffmpeg);

        if (! is_file($ffprobe)) {
            return null;
        }

        $probe = Process::timeout(60)->run([
            $ffprobe, '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $audioPath,
        ]);

        return $probe->successful() ? (float) trim($probe->output()) : null;
    }
}
