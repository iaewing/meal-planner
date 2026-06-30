<?php

use App\Utilities\UnitConverter;

describe('converting ingredient units', function () {
    it('converts tsp to tbsp', closure: function () {
        $conversionFactor = UnitConverter::getVolumeConversion('tsp', 'tbsp');
        expect($conversionFactor)->toBe(0.333);
    });

    it('converts kg to lb', closure: function () {
        $conversionFactor = UnitConverter::getWeightConversion('kg', 'lb');
        expect($conversionFactor)->toBe(2.205);
    });

    it('handles a custom conversion', closure: function () {
        $conversionFactor = UnitConverter::getCustomConversion('tbsp', 'tsp', 'garlic_powder');
        expect($conversionFactor)->toBe(3.0);
    });

    it('handles a custom conversion with a non-standard unit', closure: function () {
        $conversionFactor = UnitConverter::getCustomConversion('slice', 'lb', 'bacon');
        expect($conversionFactor)->toBe(0.063);
    });
});

describe('determining conversion factor', function () {
    it('returns 1.0 if no conversion is found', closure: function () {
        $conversionFactor = UnitConverter::determineConversionFactor('tsp', 'children');
        expect($conversionFactor)->toBe(1.0);
    });

    it('returns volume to volume conversion', closure: function () {
        $conversionFactor = UnitConverter::determineConversionFactor('tsp', 'tbsp');
        expect($conversionFactor)->toBe(0.333);
    });

    it('returns weight to weight conversion', closure: function () {
        $conversionFactor = UnitConverter::determineConversionFactor('g', 'lb');
        expect($conversionFactor)->toBe(0.002);
    });

    it('returns the correct conversion factor for custom units', closure: function () {
        $conversionFactor = UnitConverter::determineConversionFactor('slice', 'lb', 'bacon');
        expect($conversionFactor)->toBe(0.063);
    });
});

describe('choosing display units', function () {
    it('prefers cups over tablespoons when the total is at least one cup', closure: function () {
        expect(UnitConverter::chooseDisplayUnit(19, 'tbsp'))->toBe([
            'quantity' => 1.19,
            'unit' => 'cup',
        ]);
    });

    it('keeps tablespoons when the total is under one cup', closure: function () {
        expect(UnitConverter::chooseDisplayUnit(3, 'tbsp'))->toBe([
            'quantity' => 3.0,
            'unit' => 'tbsp',
        ]);
    });

    it('prefers pounds over ounces for larger weight totals', closure: function () {
        expect(UnitConverter::chooseDisplayUnit(32, 'oz'))->toBe([
            'quantity' => 2.0,
            'unit' => 'lb',
        ]);
    });

    it('keeps fractional teaspoon amounts in teaspoons', closure: function () {
        expect(UnitConverter::chooseDisplayUnit(0.5, 'tsp'))->toBe([
            'quantity' => 0.5,
            'unit' => 'tsp',
        ]);
    });
});
