<?php

namespace Tests\Fakes;

use App\Services\TesseractOcrService;

class TesseractOcrServiceFake extends TesseractOcrService
{
    private $ocr;

    public function __construct($mockOcr)
    {
        $this->ocr = $mockOcr;
    }

    public function run(string $imagePath): ?string
    {
        // delegate directly to the injected mock
        return $this->ocr->run();
    }
}
