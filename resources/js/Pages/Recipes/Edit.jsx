import {useEffect, useState} from 'react';
import { X } from 'lucide-react';
import { router, usePage } from "@inertiajs/react";
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const EditRecipe = ({ auth }) => {
    const { recipe, ingredientsData = [] } = usePage().props;
    
    // State to track if we're in image-only edit mode
    const [imageOnlyMode, setImageOnlyMode] = useState(false);
    
    // Initialize ingredients from the recipe data
    const [ingredients, setIngredients] = useState(
        recipe.ingredients && recipe.ingredients.length > 0 
            ? recipe.ingredients.map(ing => ({
                ingredient_id: ing.id,
                name: ing.name,
                quantity: ing.quantity,
                unit: ing.unit,
                notes: ing.notes || ''
            }))
            : [] // Empty array if no ingredients
    );
    
    // Initialize steps from the recipe data
    const initialSteps = Array.isArray(recipe.steps) 
        ? recipe.steps.map(step => step.instruction || step)
        : [];
    
    const [data, setData] = useState({
        name: recipe.name,
        description: recipe.description || '',
        source_url: recipe.source_url || '',
        image: null,
        servings: recipe.servings || '',
        prep_time: recipe.prep_time || '',
        cook_time: recipe.cook_time || '',
        total_time: recipe.total_time || '',
        ingredients: ingredients,
        steps: initialSteps,
        nutrition: recipe.nutrition || {
            calories: '',
            fat: '',
            saturated_fat: '',
            cholesterol: '',
            sodium: '',
            carbohydrates: '',
            fiber: '',
            sugar: '',
            protein: ''
        },
        _method: 'PUT' // For Laravel method spoofing
    });

    // Track available units for each selected ingredient
    const [availableUnits, setAvailableUnits] = useState(
        recipe.ingredients && recipe.ingredients.length > 0
            ? recipe.ingredients.map(ing => ing.available_units || [])
            : [] // Empty array if no ingredients
    );
    
    // Preview image
    const [imagePreview, setImagePreview] = useState(
        recipe.image_path ? `/storage/${recipe.image_path}` : null
    );

    useEffect(() => {
        setData(prevData => ({
            ...prevData,
            ingredients: ingredients
        }));
    }, [ingredients]);

    const handleSubmit = (e) => {
        e.preventDefault();

        // Create a base form data object with required fields
        const formData = {
            name: data.name,
            description: data.description,
            source_url: data.source_url,
            image: data.image,
            _method: 'PUT'
        };

        // Only include ingredients if we have any and we're not in image-only mode
        if (!imageOnlyMode && ingredients.length > 0) {
            formData.ingredients = ingredients;
        }

        // Only include steps if we have any and we're not in image-only mode
        if (!imageOnlyMode && data.steps && data.steps.length > 0) {
            // Format steps properly for the backend
            formData.steps = data.steps.map((instruction, index) => ({
                instruction,
                order: index + 1
            }));
        }

        router.post(route('recipes.update', recipe.id), formData, {
            forceFormData: true,
            onError: (errors) => {
                // Handle errors
                console.error(errors);
            },
            onSuccess: () => {
                // Success handling is automatic through the backend redirect
            },
        });
    };

    const addIngredient = () => {
        setIngredients([...ingredients, { name: '', quantity: '', unit: '', ingredient_id: '', notes: '' }]);
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
            const selectedIngredient = ingredientsData && ingredientsData.length > 0 
                ? ingredientsData.find(ing => ing.id === parseInt(value))
                : null;
            
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
        const file = e.target.files[0];
        setData({...data, image: file});
        
        // Create preview URL
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Edit Recipe" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-2xl font-bold">Edit Recipe</h1>
                                <div className="flex items-center">
                                    <label className="inline-flex items-center mr-4">
                                        <input
                                            type="checkbox"
                                            className="form-checkbox h-5 w-5 text-blue-600"
                                            checked={imageOnlyMode}
                                            onChange={() => setImageOnlyMode(!imageOnlyMode)}
                                        />
                                        <span className="ml-2 text-gray-700">Image Update Only</span>
                                    </label>
                                </div>
                            </div>
                            
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
                                
                                {!imageOnlyMode && (
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
                                    </div>
                                )}
                                
                                {/* Recipe Image */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Recipe Image
                                    </label>
                                    
                                    {imagePreview && (
                                        <div className="mb-4">
                                            <img 
                                                src={imagePreview} 
                                                alt="Recipe preview" 
                                                className="w-64 h-64 object-cover rounded-md"
                                            />
                                        </div>
                                    )}
                                    
                                    <input
                                        type="file"
                                        onChange={handleImageChange}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        accept="image/*"
                                    />
                                    <p className="text-sm text-gray-500 mt-1">
                                        Upload a new image to replace the existing one or add an image to this recipe.
                                    </p>
                                </div>
                                
                                {/* Ingredients - Only show if not in image-only mode */}
                                {!imageOnlyMode && (
                                    <div>
                                        <h2 className="text-xl font-semibold mb-3">Ingredients</h2>
                                        
                                        {ingredients.length > 0 ? (
                                            ingredients.map((ingredient, index) => (
                                                <div key={index} className="flex items-center mb-3">
                                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-3 flex-grow">
                                                        <div>
                                                            <select
                                                                value={ingredient.ingredient_id}
                                                                onChange={(e) => handleIngredientChange(index, 'ingredient_id', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                                required
                                                            >
                                                                <option value="">Select Ingredient</option>
                                                                {ingredientsData && ingredientsData.map((ing) => (
                                                                    <option key={ing.id} value={ing.id}>
                                                                        {ing.name}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>
                                                        
                                                        <div>
                                                            <input
                                                                type="number"
                                                                placeholder="Quantity"
                                                                value={ingredient.quantity}
                                                                onChange={(e) => handleIngredientChange(index, 'quantity', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                                step="0.01"
                                                                min="0"
                                                                required
                                                            />
                                                        </div>
                                                        
                                                        <div>
                                                            <select
                                                                value={ingredient.unit}
                                                                onChange={(e) => handleIngredientChange(index, 'unit', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                            >
                                                                <option value="">No Unit</option>
                                                                {availableUnits[index] && availableUnits[index].map((unit) => (
                                                                    <option key={unit.unit} value={unit.unit}>
                                                                        {unit.unit} {unit.is_default ? '(default)' : ''}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>
                                                        
                                                        <div>
                                                            <input
                                                                type="text"
                                                                placeholder="Notes (optional)"
                                                                value={ingredient.notes}
                                                                onChange={(e) => handleIngredientChange(index, 'notes', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                            />
                                                        </div>
                                                    </div>
                                                    
                                                    <button
                                                        type="button"
                                                        onClick={() => removeIngredient(index)}
                                                        className="ml-3 text-red-500 hover:text-red-700"
                                                    >
                                                        <X size={20} />
                                                    </button>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-gray-500 italic mb-3">No ingredients added yet.</p>
                                        )}
                                        
                                        <button
                                            type="button"
                                            onClick={addIngredient}
                                            className="mt-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200"
                                        >
                                            Add Ingredient
                                        </button>
                                    </div>
                                )}
                                
                                {/* Steps - Only show if not in image-only mode */}
                                {!imageOnlyMode && (
                                    <div>
                                        <h2 className="text-xl font-semibold mb-3">Instructions</h2>
                                        
                                        {data.steps.length > 0 ? (
                                            data.steps.map((step, index) => (
                                                <div key={index} className="flex items-start mb-3">
                                                    <div className="flex-grow">
                                                        <div className="flex items-center">
                                                            <span className="mr-3 font-medium">{index + 1}.</span>
                                                            <textarea
                                                                value={step}
                                                                onChange={(e) => handleStepChange(index, e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                                rows="2"
                                                                required
                                                            ></textarea>
                                                        </div>
                                                    </div>
                                                    
                                                    <button
                                                        type="button"
                                                        onClick={() => removeStep(index)}
                                                        className="ml-3 text-red-500 hover:text-red-700"
                                                    >
                                                        <X size={20} />
                                                    </button>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-gray-500 italic mb-3">No instructions added yet.</p>
                                        )}
                                        
                                        <button
                                            type="button"
                                            onClick={addStep}
                                            className="mt-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200"
                                        >
                                            Add Step
                                        </button>
                                    </div>
                                )}
                                
                                {/* Submit Button */}
                                <div className="flex justify-end">
                                    <button
                                        type="submit"
                                        className="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                    >
                                        {imageOnlyMode ? 'Update Image' : 'Update Recipe'}
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

export default EditRecipe; 