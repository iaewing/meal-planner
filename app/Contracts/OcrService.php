<?php

namespace App\Contracts;

interface OcrService
{
    public function run(string $imagePath): ?string;
}