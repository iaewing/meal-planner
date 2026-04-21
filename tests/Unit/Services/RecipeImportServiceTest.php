<?php

namespace Tests\Unit\Services;

use App\Contracts\OcrService;
use App\Models\Ingredient;
use App\Models\User;
use App\Services\RecipeImportService;
use Tests\Stubs\ConcreteTesseractOcrException;
use thiagoalessio\TesseractOCR\TesseractOcrException;

beforeEach(function () {
    $this->user = User::factory()->create();
});


describe('Image imports', function () {
    beforeEach(function () {
        $this->mockOcr = $this->mock(OcrService::class);
    });

    it('handles a tesseract exception', function () {
        $imagePath = 'fake/image/path.jpg';
        $this->mockOcr->shouldReceive('run')
            ->once()
            ->with($imagePath)
            ->andThrow(new ConcreteTesseractOcrException('Failed to run Tesseract'));


        $service = app()->make(RecipeImportService::class);

        $service->importFromImage($imagePath, 1);
    })->throws(TesseractOcrException::class);

    it('imports recipe text when OCR succeeds', function () {
        $this->mockOcr->shouldReceive('run')->once()->with('good.jpg')->andReturn('Pasta Recipe');

        $this->app->instance(OcrService::class, $this->mockOcr);

        $service = app()->make(RecipeImportService::class);

        $recipe = $service->importFromImage('good.jpg', $this->user->id);

        expect($recipe)->not->toBeNull()
            ->and($recipe->name)->toBe('Pasta Recipe');
    });

    it('handles null OCR result gracefully', function () {
        $this->mockOcr->shouldReceive('run')->once()->with('bad.jpg')->andReturn(null);

        $this->app->instance(OcrService::class, $this->mockOcr);

        $service = app()->make(RecipeImportService::class);

        $recipe = $service->importFromImage('bad.jpg', $this->user->id);

        expect($recipe)->toBeNull();
    });

    it('creates and attaches ingredients', function () {
        $stepText = [
            'title' => 'Pasta Recipe',
            'ingredients' => [
                '1 cup bacon',
                '1 pound sausage',
                '454 grams macaroni',
                '1 block cheese'
            ],
            'instructions' => [
                'cook bacon and sausage',
                'cook macaroni',
                'shred cheese',
                'combine'
            ]
        ];

        $ocrText = 'Pasta Recipe 
        Ingredients 
        1 cup bacon 
        1 pound sausage 
        454 grams macaroni 
        1 block cheese 
        instructions 
        cook bacon and sausage 
        cook macaroni 
        shred cheese 
        combine';

        $this->mockOcr->shouldReceive('run')
            ->once()
            ->with('good.jpg')
            ->andReturn($ocrText);

        $this->app->instance(OcrService::class, $this->mockOcr);

        $service = app()->make(RecipeImportService::class);

        $recipe = $service->importFromImage('good.jpg', $this->user->id);

        expect($recipe)->not->toBeNull()
            ->and($recipe->name)->toBe($stepText['title'])
            ->and($recipe->ingredients)->toHaveCount(4)
            ->and($recipe->steps)->toHaveCount(4);
    });

    //test it creates recipe steps
});

describe('OCR parsing', function () {
    it('parses ingredients', function () {
        $ocrText = 'Pasta Recipe 
        Ingredients 
        1 cup bacon 
        1 pound sausage 
        454 grams macaroni 
        1 block cheese 
        instructions 
        cook bacon and sausage 
        cook macaroni 
        shred cheese 
        combine';

        $service = app()->make(RecipeImportService::class);

        $parsedText = $service->parseOcrText($ocrText);

        expect($parsedText)->toEqual([
            'title' => 'Pasta Recipe',
            'ingredients' => [
                '1 cup bacon',
                '1 pound sausage',
                '454 grams macaroni',
                '1 block cheese'
            ],
            'instructions' => [
                'cook bacon and sausage',
                'cook macaroni',
                'shred cheese',
                'combine'
            ]
        ]);
    });

    it('parses instructions', function () {
        $ocrText = 'Pasta Recipe
        Ingredients
        1 cup bacon
        instructions
        cook bacon and sausage
        cook macaroni
        shred cheese
        combine';

        $service = app()->make(RecipeImportService::class);

        $parsedText = $service->parseOcrText($ocrText);

        expect($parsedText['instructions'])->toEqual([
            'cook bacon and sausage',
            'cook macaroni',
            'shred cheese',
            'combine',
        ]);
    });
});