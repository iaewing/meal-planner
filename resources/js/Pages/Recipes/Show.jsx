import React, { useState } from 'react';
import {Head, usePage, Link} from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Show({auth}) {
    const {recipe} = usePage().props
    
    const [selectedUnits, setSelectedUnits] = useState(
        recipe.ingredients.reduce((acc, ingredient) => {
            acc[ingredient.id] = ingredient.unit;
            return acc;
        }, {})
    );
    
    const handleUnitChange = (ingredientId, newUnit) => {
        setSelectedUnits(prev => ({
            ...prev,
            [ingredientId]: newUnit
        }));
    };
    
    const getConvertedQuantity = (ingredient, selectedUnit) => {
        if (ingredient.unit === selectedUnit) {
            return ingredient.quantity;
        }
        
        const originalUnit = ingredient.available_units.find(u => u.unit === ingredient.unit);
        const targetUnit = ingredient.available_units.find(u => u.unit === selectedUnit);
        
        if (!originalUnit || !targetUnit) {
            return ingredient.quantity;
        }
        
        const defaultUnit = ingredient.available_units.find(u => u.is_default);
        
        if (!defaultUnit) {
            return ingredient.quantity;
        }
        
        const valueInDefaultUnit = ingredient.quantity * (originalUnit.conversion_factor / defaultUnit.conversion_factor);
        const convertedValue = valueInDefaultUnit * (defaultUnit.conversion_factor / targetUnit.conversion_factor);
        
        return parseFloat(convertedValue).toFixed(2);
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={recipe.name}/>
            <div className="container mx-auto px-4 py-8">
                <div className="bg-white rounded-lg shadow-lg">
                    <div className="p-6 border-b">
                        <div className="flex justify-between items-center">
                            <div className="text-2xl font-semibold">{recipe.name}</div>
                            <Link
                                href={route('recipes.edit', recipe.id)}
                                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            >
                                Edit Recipe
                            </Link>
                        </div>
                        {recipe.description && (
                            <p className="mt-2 text-gray-600">{recipe.description}</p>
                        )}
                        {recipe.source_url && (
                            <div className="mt-2">
                                <span className="text-gray-600">Source: </span>
                                <a href={recipe.source_url} className="text-blue-600 hover:underline" target="_blank" rel="noopener noreferrer">
                                    {recipe.source_url}
                                </a>
                            </div>
                        )}
                        {recipe.servings && (
                            <div className="mt-2 text-gray-600">
                                Servings: {recipe.servings}
                            </div>
                        )}
                    </div>
                    
                    {recipe.image_url && (
                        <div className="p-6 border-b">
                            <img 
                                src={recipe.image_url}
                                alt={recipe.name}
                                className="w-full max-h-96 object-cover rounded-lg"
                            />
                        </div>
                    )}
                    
                    <div className="p-6 border-b">
                        <h2 className="text-xl font-semibold mb-4">Ingredients</h2>
                        <ul className="space-y-3">
                            {recipe.ingredients.map((ingredient) => (
                                <li key={ingredient.id} className="flex items-center">
                                    <span className="capitalize mr-2">
                                        {getConvertedQuantity(ingredient, selectedUnits[ingredient.id])} {selectedUnits[ingredient.id]} {ingredient.name}
                                    </span>
                                    
                                    {ingredient.available_units && ingredient.available_units.length > 1 && (
                                        <select
                                            value={selectedUnits[ingredient.id]}
                                            onChange={(e) => handleUnitChange(ingredient.id, e.target.value)}
                                            className="ml-2 text-sm border border-gray-300 rounded px-2 py-1"
                                        >
                                            {ingredient.available_units.map((unit) => (
                                                <option key={unit.unit} value={unit.unit}>
                                                    {unit.unit} {unit.is_default ? '(default)' : ''}
                                                </option>
                                            ))}
                                        </select>
                                    )}
                                    
                                    {ingredient.notes && (
                                        <span className="ml-2 text-gray-500 italic">({ingredient.notes})</span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                    
                    <div className="p-6">
                        <h2 className="text-xl font-semibold mb-4">Instructions</h2>
                        <ol className="list-decimal list-inside space-y-4">
                            {recipe.steps.map((step) => (
                                <li key={step.id} className="pl-2">
                                    <span className="ml-2">{step.instruction}</span>
                                </li>
                            ))}
                        </ol>
                    </div>
                    
                    {recipe.nutrition && Object.values(recipe.nutrition).some(val => val) && (
                        <div className="p-6 border-t">
                            <h2 className="text-xl font-semibold mb-4">Nutrition Information</h2>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                {recipe.nutrition.calories && (
                                    <div>
                                        <span className="font-medium">Calories:</span> {recipe.nutrition.calories}
                                    </div>
                                )}
                                {recipe.nutrition.protein && (
                                    <div>
                                        <span className="font-medium">Protein:</span> {recipe.nutrition.protein}g
                                    </div>
                                )}
                                {recipe.nutrition.carbohydrates && (
                                    <div>
                                        <span className="font-medium">Carbs:</span> {recipe.nutrition.carbohydrates}g
                                    </div>
                                )}
                                {recipe.nutrition.fat && (
                                    <div>
                                        <span className="font-medium">Fat:</span> {recipe.nutrition.fat}g
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}