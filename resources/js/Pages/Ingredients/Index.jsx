import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Trash2 } from 'lucide-react';

export default function Index({ auth }) {
    const { ingredients, volumeUnits, weightUnits } = usePage().props;
    const [newIngredient, setNewIngredient] = useState({
        name: '',
        units: [{ unit: '', is_default: true }]
    });
    
    // Combine volume and weight units for the dropdown
    const allUnits = [...volumeUnits, ...weightUnits];

    function addUnit() {
        setNewIngredient(prev => ({
            ...prev,
            units: [...prev.units, { unit: '', is_default: false }]
        }));
    }

    function removeUnit(index) {
        setNewIngredient(prev => ({
            ...prev,
            units: prev.units.filter((_, i) => i !== index)
        }));
    }

    function updateUnit(index, value) {
        setNewIngredient(prev => {
            const updatedUnits = [...prev.units];
            updatedUnits[index].unit = value;
            return { ...prev, units: updatedUnits };
        });
    }

    function setDefaultUnit(index) {
        setNewIngredient(prev => {
            const updatedUnits = prev.units.map((unit, i) => ({
                ...unit,
                is_default: i === index
            }));
            return { ...prev, units: updatedUnits };
        });
    }

    function addIngredient() {
        router.post(route('ingredients.store'), newIngredient);
        setNewIngredient({
            name: '',
            units: [{ unit: '', is_default: true }]
        });
    }

    // Function to add a unit to an existing ingredient
    function addUnitToIngredient(ingredientId) {
        router.post(route('ingredients.add-unit', ingredientId), {
            unit: '',
            is_default: false
        });
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="All Ingredients"/>
            <div className="py-12 bg-gray-50">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Add Ingredient Section */}
                    <div className="mb-8">
                        <h2 className="text-2xl font-bold text-gray-800 mb-4">Add a New Ingredient</h2>
                        <div className="bg-white p-6 rounded-lg shadow">
                            <div className="mb-4">
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                                    Ingredient Name
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    value={newIngredient.name}
                                    onChange={(e) => setNewIngredient({...newIngredient, name: e.target.value})}
                                    placeholder="e.g., Chicken, Flour, Olive Oil"
                                />
                            </div>
                            
                            <div className="mb-4">
                                <div className="flex justify-between items-center mb-2">
                                    <label className="block text-sm font-medium text-gray-700">
                                        Units
                                    </label>
                                    <button 
                                        type="button" 
                                        onClick={addUnit}
                                        className="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200"
                                    >
                                        <Plus size={14} className="mr-1" /> Add Unit
                                    </button>
                                </div>
                                
                                {newIngredient.units.map((unitObj, index) => (
                                    <div key={index} className="flex items-center gap-2 mb-2">
                                        <select
                                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value={unitObj.unit}
                                            onChange={(e) => updateUnit(index, e.target.value)}
                                        >
                                            <option value="">Select a unit</option>
                                            <optgroup label="Volume Units">
                                                {volumeUnits.map(unit => (
                                                    <option key={`volume-${unit}`} value={unit}>{unit}</option>
                                                ))}
                                            </optgroup>
                                            <optgroup label="Weight Units">
                                                {weightUnits.map(unit => (
                                                    <option key={`weight-${unit}`} value={unit}>{unit}</option>
                                                ))}
                                            </optgroup>
                                            <option value="custom">Custom Unit...</option>
                                        </select>
                                        
                                        {unitObj.unit === 'custom' && (
                                            <input
                                                type="text"
                                                className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Enter custom unit"
                                                onChange={(e) => updateUnit(index, e.target.value)}
                                            />
                                        )}
                                        
                                        <div className="flex items-center bg-gray-100 px-3 py-2 rounded-md">
                                            <input
                                                type="checkbox"
                                                id={`default-${index}`}
                                                checked={unitObj.is_default}
                                                onChange={() => setDefaultUnit(index)}
                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            />
                                            <label htmlFor={`default-${index}`} className="ml-2 text-sm text-gray-700 font-medium">
                                                Default Unit
                                            </label>
                                        </div>
                                        
                                        {index > 0 && (
                                            <button 
                                                type="button" 
                                                onClick={() => removeUnit(index)}
                                                className="p-1 text-red-600 hover:text-red-800"
                                            >
                                                <Trash2 size={16} />
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                            
                            <button
                                type="button"
                                onClick={addIngredient}
                                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                disabled={!newIngredient.name || newIngredient.units.some(u => !u.unit)}
                            >
                                Add Ingredient
                            </button>
                        </div>
                    </div>

                    {/* Ingredients List */}
                    <div>
                        <h2 className="text-2xl font-bold text-gray-800 mb-4">All Ingredients</h2>
                        <div className="bg-white rounded-lg shadow overflow-hidden">
                            {ingredients.length > 0 ? (
                                <ul className="divide-y divide-gray-200">
                                    {ingredients.map((ingredient) => (
                                        <li key={ingredient.id} className="p-4">
                                            <div className="flex flex-col">
                                                <div className="flex justify-between items-center">
                                                    <h3 className="text-lg font-medium text-gray-900 capitalize">
                                                        {ingredient.name}
                                                    </h3>
                                                    <button
                                                        onClick={() => addUnitToIngredient(ingredient.id)}
                                                        className="text-sm text-blue-600 hover:text-blue-800"
                                                    >
                                                        Add Unit
                                                    </button>
                                                </div>
                                                
                                                {ingredient.units && ingredient.units.length > 0 ? (
                                                    <div className="mt-1">
                                                        <span className="text-sm text-gray-500">Units: </span>
                                                        <div className="flex flex-wrap gap-2 mt-1">
                                                            {ingredient.units.map((unit, idx) => (
                                                                <span 
                                                                    key={idx} 
                                                                    className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                                        unit.is_default 
                                                                            ? 'bg-green-100 text-green-800' 
                                                                            : 'bg-gray-100 text-gray-800'
                                                                    }`}
                                                                >
                                                                    {unit.unit} {unit.is_default && '(default)'}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-gray-500">No units defined</span>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <div className="p-6 text-center text-gray-500">
                                    No ingredients added yet.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}