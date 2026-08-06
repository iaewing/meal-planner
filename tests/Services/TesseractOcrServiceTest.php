<?php

namespace Tests\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Tests\Fakes\TesseractOcrServiceFake;
use Tests\Stubs\ConcreteTesseractOcrException;
use thiagoalessio\TesseractOCR\TesseractOCR;

it('returns text when OCR succeeds', function () {
    $mockOCR = $this->mock(TesseractOCR::class);
    $mockOCR->shouldReceive('run')->once()->andReturn('hello world');

    $service = new TesseractOcrServiceFake($mockOCR);

    expect($service->run('fake.jpg'))->toBe('hello world');
});

it('returns null and logs when TesseractOcrException occurs', function () {
    Log::spy();

    $mockOCR = $this->mock(TesseractOCR::class);
    $mockOCR->shouldReceive('run')
        ->once()
        ->andThrow(new ConcreteTesseractOcrException('boom'));

    $service = new TesseractOcrServiceFake($mockOCR);

    $result = $service->run('fake.jpg');

    expect($result)->toBeNull();
    Log::shouldHaveReceived('error')->with('boom');
});

it('returns null and logs on generic exception', function () {
    Log::spy();

    $mockOCR = $this->mock(TesseractOCR::class);
    $mockOCR->shouldReceive('run')
        ->once()
        ->andThrow(new Exception('generic fail'));

    $service = new TesseractOcrServiceFake($mockOCR);

    $result = $service->run('fake.jpg');

    expect($result)->toBeNull();
    Log::shouldHaveReceived('error')->with('generic fail');
});
