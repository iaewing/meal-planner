<?php

namespace Tests\Fakes;

use App\Services\TesseractOcrService;
use Exception;
use Illuminate\Support\Facades\Log;

class TesseractOcrServiceFake extends TesseractOcrService
{
    private $ocr;

    public function __construct($mockOcr)
    {
        $this->ocr = $mockOcr;
    }

    public function run(string $imagePath): ?string
    {
        try {
            return $this->ocr->run();
        } catch(Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }
}
