<?php

namespace App\Services;

use App\Contracts\OcrService;
use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\TesseractOcrException;
use Illuminate\Support\Facades\Log;

class TesseractOcrService implements OcrService
{
    public function run(string $imagePath): ?string
    {
        try {
            return (new TesseractOCR($imagePath))->run();
        } catch (TesseractOcrException $e) {
            Log::error($e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }
}
