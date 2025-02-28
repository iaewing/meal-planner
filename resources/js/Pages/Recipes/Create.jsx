import {useEffect, useState} from 'react';
import { X } from 'lucide-react';
import { router, usePage } from "@inertiajs/react";
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const CreateRecipe = ({ auth }) => {
    const { ingredientsData } = usePage().props;
    const [ingredients, setIngredients] = useState([
        { name: '', quantity: '', unit: '', ingredient_id: '' }
    ]);
    const [data, setData] = useState({
        name: '',
        description: '',
        source_url: '',
        image: null,
        servings: '',
        prep_time: '',
        cook_time: '',
        total_time: '',
        ingredients: ingredients,
        steps: [''],
        nutrition: {
            calories: '',
            fat: '',
            saturated_fat: '',
            cholesterol: '',
            sodium: '',
            carbohydrates: '',
            fiber: '',
            sugar: '',
            protein: ''
        }
    });

    // Track available units for each selected ingredient
    const [availableUnits, setAvailableUnits] = useState([]);

    useEffect(() => {
        setData(prevData => ({
            ...prevData,
            ingredients: ingredients
        }));
    }, [ingredients]);

    const handleSubmit = (e) => {
        e.preventDefault();

        router.post(route('recipes.store'), data, {
            forceFormData: true,
            onError: (errors) => {
                // Handle errors
            },
            onSuccess: () => {
                // Success handling is automatic through the backend redirect
            },
        });
    };

    const addIngredient = () => {
        setIngredients([...ingredients, { name: '', quantity: '', unit: '', ingredient_id: ''}]);
        setAvailableUnits([...availableUnits, []]);
    };

    const removeIngredient = (index) => {
        setIngredients(prev => prev.filter((_, i) => i !== index));
        setAvailableUnits(prev => prev.filter((_, i) => i !== index));
    };

    const handleIngredientChange = (index, field, value) => {
        const updatedIngredients = [...ingredients];
        updatedIngredients[index][field] = value;
        
        // If changing the ingredient_id, update available units
        if (field === 'ingredient_id') {
            const selectedIngredient = ingredientsData.find(ing => ing.id === parseInt(value));
            
            if (selectedIngredient && selectedIngredient.units) {
                // Update available units for this ingredient
                const newAvailableUnits = [...availableUnits];
                newAvailableUnits[index] = selectedIngredient.units;
                setAvailableUnits(newAvailableUnits);
                
                // Set default unit if available
                const defaultUnit = selectedIngredient.units.find(u => u.is_default);
                if (defaultUnit) {
                    updatedIngredients[index].unit = defaultUnit.unit;
                } else if (selectedIngredient.units.length > 0) {
                    updatedIngredients[index].unit = selectedIngredient.units[0].unit;
                }
            } else {
                // Clear units if no ingredient selected
                const newAvailableUnits = [...availableUnits];
                newAvailableUnits[index] = [];
                setAvailableUnits(newAvailableUnits);
                updatedIngredients[index].unit = '';
            }
        }
        
        setIngredients(updatedIngredients);
    };

    const handleStepChange = (index, value) => {
        const updatedSteps = [...data.steps];
        updatedSteps[index] = value;
        setData({...data, steps: updatedSteps});
    };

    const addStep = () => {
        setData({...data, steps: [...data.steps, '']});
    };

    const removeStep = (index) => {
        setData({
            ...data,
            steps: data.steps.filter((_, i) => i !== index)
        });
    };

    const handleNutritionChange = (field, value) => {
        setData({
            ...data,
            nutrition: {
                ...data.nutrition,
                [field]: value
            }
        });
    };

    const handleImageChange = (e) => {
        setData({...data, image: e.target.files[0]});
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Create Recipe" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h1 className="text-2xl font-bold mb-6">Create New Recipe</h1>
                            
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Basic Info */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Recipe Name
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData({...data, name: e.target.value})}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            required
                                        />
                                    </div>
                                    
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Source URL (Optional)
                                        </label>
                                        <input
                                            type="url"
                                            value={data.source_url}
                                            onChange={(e) => setData({...data, source_url: e.target.value})}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        />
                                    </div>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        value={data.description}
                                        onChange={(e) => setData({...data, description: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        rows="3"
                                    ></textarea>
                                </div>
                                
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Servings
                                        </label>
                                        <input
                                            type="number"
                                            value={data.servings}
                                            onChange={(e) => setData({...data, servings: e.target.value})}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            min="1"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Prep Time (minutes)
                                        </label>
                                        <input
                                            type="number"
                                            value={data.prep_time}
                                            onChange={(e) => setData({...data, prep_time: e.target.value})}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            min="0"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Cook Time (minutes)
                                        </label>
                                        <input
                                            type="number"
                                            value={data.cook_time}
                                            onChange={(e) => setData({...data, cook_time: e.target.value})}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            min="0"
                                        />
                                    </div>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Recipe Image (Optional)
                                    </label>
                                    <input
                                        type="file"
                                        onChange={handleImageChange}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        accept="image/*"
                                    />
                                </div>
                                
                                {/* Ingredients */}
                                <div>
                                    <div className="flex justify-between items-center mb-2">
                                        <h2 className="text-lg font-medium">Ingredients</h2>
                                        <button
                                            type="button"
                                            onClick={addIngredient}
                                            className="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                                        >
                                            Add Ingredient
                                        </button>
                                    </div>
                                    
                                    {ingredients.map((ingredient, index) => (
                                        <div key={index} className="flex items-center gap-2 mb-2">
                                            <div className="grid grid-cols-12 gap-2 flex-1">
                                                <div className="col-span-2">
                                                    <input
                                                        type="number"
                                                        value={ingredient.quantity}
                                                        onChange={(e) => handleIngredientChange(index, 'quantity', e.target.value)}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                        placeholder="Qty"
                                                        step="0.01"
                                                        min="0"
                                                    />
                                                </div>
                                                
                                                <div className="col-span-2">
                                                    <select
                                                        value={ingredient.unit}
                                                        onChange={(e) => handleIngredientChange(index, 'unit', e.target.value)}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                        disabled={!availableUnits[index] || availableUnits[index].length === 0}
                                                    >
                                                        <option value="">Unit</option>
                                                        {availableUnits[index] && availableUnits[index].map((unit, i) => (
                                                            <option key={i} value={unit.unit}>
                                                                {unit.unit} {unit.is_default ? '(default)' : ''}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                
                                                <div className="col-span-8">
                                                    <select
                                                        value={ingredient.ingredient_id}
                                                        onChange={(e) => handleIngredientChange(index, 'ingredient_id', e.target.value)}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md capitalize"
                                                    >
                                                        <option value="">Select Ingredient</option>
                                                        {ingredientsData.map((ing) => (
                                                            <option key={ing.id} value={ing.id} className="capitalize">
                                                                {ing.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <button
                                                type="button"
                                                onClick={() => removeIngredient(index)}
                                                className="p-2 text-red-600 hover:text-red-800"
                                                disabled={ingredients.length === 1}
                                            >
                                                <X size={20} />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                                
                                {/* Steps */}
                                <div>
                                    <div className="flex justify-between items-center mb-2">
                                        <h2 className="text-lg font-medium">Steps</h2>
                                        <button
                                            type="button"
                                            onClick={addStep}
                                            className="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                                        >
                                            Add Step
                                        </button>
                                    </div>
                                    
                                    {data.steps.map((step, index) => (
                                        <div key={index} className="flex items-start gap-2 mb-2">
                                            <div className="flex-none pt-2">
                                                <span className="inline-flex items-center justify-center h-6 w-6 rounded-full bg-gray-200 text-gray-800 text-sm font-medium">
                                                    {index + 1}
                                                </span>
                                            </div>
                                            
                                            <textarea
                                                value={step}
                                                onChange={(e) => handleStepChange(index, e.target.value)}
                                                className="flex-1 px-3 py-2 border border-gray-300 rounded-md"
                                                rows="2"
                                                placeholder={`Step ${index + 1}`}
                                            ></textarea>
                                            
                                            <button
                                                type="button"
                                                onClick={() => removeStep(index)}
                                                className="p-2 text-red-600 hover:text-red-800"
                                                disabled={data.steps.length === 1}
                                            >
                                                <X size={20} />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                                
                                {/* Submit Button */}
                                <div className="flex justify-end">
                                    <button
                                        type="submit"
                                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                    >
                                        Create Recipe
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default CreateRecipe;