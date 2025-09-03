<?php

namespace Tests\Unit\Services;

use App\Contracts\OcrService;
use App\Services\RecipeImportService;
use Tests\Stubs\ConcreteTesseractOcrException;
use thiagoalessio\TesseractOCR\TesseractOcrException;


describe('Image imports', function () {
    it('handles a tesseract exception', function () {
        $imagePath = 'fake/image/path.jpg';
        $mockOcr = $this->mock(OcrService::class);
        $mockOcr->shouldReceive('run')
            ->once()
            ->with($imagePath)
            ->andThrow(new ConcreteTesseractOcrException('Failed to run Tesseract'));


        $service = app()->make(RecipeImportService::class);

        $service->importFromImage($imagePath, 1);
    })->throws(TesseractOcrException::class);

    it('imports recipe text when OCR succeeds', function () {
        $mockOcr = $this->mock(OcrService::class);
        $mockOcr->shouldReceive('run')->once()->with('good.jpg')->andReturn('Pasta Recipe');

        $this->app->instance(OcrService::class, $mockOcr);

        $service = app()->make(\App\Services\RecipeImportService::class);

        $recipe = $service->importFromImage('good.jpg', 1);

        expect($recipe)->not->toBeNull()
            ->and($recipe->title)->toBe('Pasta Recipe');
    });

    it('handles null OCR result gracefully', function () {
        $mockOcr = $this->mock(OcrService::class);
        $mockOcr->shouldReceive('run')->once()->with('bad.jpg')->andReturn(null);

        $this->app->instance(OcrService::class, $mockOcr);

        $service = app()->make(RecipeImportService::class);

        $recipe = $service->importFromImage('bad.jpg', 1);

        expect($recipe)->toBeNull();
    });
});