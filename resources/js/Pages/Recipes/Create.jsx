import {useState} from 'react';
import { X } from 'lucide-react';
import {router, usePage} from "@inertiajs/react";
import dropdown from "@/Components/Dropdown.jsx";

const CreateRecipe = () => {
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

    // const handleSubmit = (e) => {
    //     e.preventDefault();
    //     const formData = new FormData();
    //
    //     Object.keys(data).forEach(key => {
    //         if (key === 'ingredients' || key === 'steps' || key === 'nutrition') {
    //             formData.append(key, JSON.stringify(data[key]));
    //         } else if (key === 'image' && data.image) {
    //             formData.append(key, data.image);
    //         } else if (data[key] !== null && data[key] !== '') {
    //             formData.append(key, data[key]);
    //         }
    //     });
    //
    //     // Handle form submission here
    //     console.log('Submitting form data:', formData);
    // };
    const handleSubmit = (e) => {
        e.preventDefault();

        router.post('/recipes', data, {
            forceFormData: true,
            onError: (errors) => {
                // setErrors(errors);
            },
            onSuccess: () => {
                // Success handling is automatic through the backend redirect
            },
        });
    };

    const addIngredient = () => {
        setIngredients([...ingredients, { name: '', quantity: '', unit: '', ingredient_id: ''}]);
    };

    const removeIngredient = (index) => {
        const newIngredients = [...ingredients];
        newIngredients.splice(index, 1);
        setIngredients(newIngredients);
    };

    const updateIngredient = (index, field, value) => {
        const newIngredients = [...ingredients];
        newIngredients[index][field] = value;

        // If the name field is being updated, find the matching ingredient and set its unit
        if (field === 'name') {
            const selectedIngredient = ingredientsData.find(ing => ing.name === value);
            if (selectedIngredient) {
                newIngredients[index].unit = selectedIngredient.unit;
            }
        }

        setIngredients(newIngredients);
    };

    const addStep = () => {
        setData(prev => ({
            ...prev,
            steps: [...prev.steps, '']
        }));
    };

    const removeStep = (index) => {
        setData(prev => ({
            ...prev,
            steps: prev.steps.filter((_, i) => i !== index)
        }));
    };

    const updateStep = (index, value) => {
        setData(prev => ({
            ...prev,
            steps: prev.steps.map((step, i) =>
                i === index ? value : step
            )
        }));
    };

    return (
        <div className="container mx-auto px-4 py-8">
            <div className="bg-white rounded-lg shadow-lg">
                <div className="p-6 border-b">
                    <h2 className="text-2xl font-semibold">Create New Recipe</h2>
                </div>

                <div className="p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid md:grid-cols-2 gap-4">
                            <div>
                                <label className="block mb-2 text-sm font-medium">Recipe Name *</label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData(prev => ({ ...prev, name: e.target.value }))}
                                    required
                                    placeholder="Enter recipe name"
                                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block mb-2 text-sm font-medium">Source URL</label>
                                <input
                                    type="url"
                                    value={data.source_url}
                                    onChange={(e) => setData(prev => ({ ...prev, source_url: e.target.value }))}
                                    placeholder="Recipe source URL"
                                    className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block mb-2 text-sm font-medium">Description</label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData(prev => ({ ...prev, description: e.target.value }))}
                                placeholder="Enter recipe description"
                                rows={3}
                                className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label className="block mb-2 text-sm font-medium">Recipe Image</label>
                            <input
                                type="file"
                                accept="image/*"
                                onChange={(e) => setData(prev => ({ ...prev, image: e.target.files[0] }))}
                                className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <div className="grid md:grid-cols-3 gap-4">
                            {['servings', 'prep_time', 'cook_time'].map((field) => (
                                <div key={field}>
                                    <label className="block mb-2 text-sm font-medium capitalize">
                                        {field.replace('_', ' ')}
                                    </label>
                                    <input
                                        type="number"
                                        value={data[field]}
                                        onChange={(e) => setData(prev => ({ ...prev, [field]: e.target.value }))}
                                        placeholder={`Enter ${field.replace('_', ' ')}`}
                                        className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-semibold">Ingredients</h3>
                                <button
                                    type="button"
                                    onClick={addIngredient}
                                    className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                                >
                                    Add Ingredient
                                </button>
                            </div>
                            {ingredients.map((ingredient, index) => (
                                <div key={index} className="grid grid-cols-12 gap-2">
                                    <select
                                        className="col-span-5 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        value={ingredient.name}
                                        onChange={(e) => updateIngredient(index, 'name', e.target.value, ingredient.ingredient_id)}
                                    >
                                        <option value="">Select ingredient</option>
                                        {ingredientsData.map((ing) => (
                                            <option key={ing.name} value={ing.name}>
                                                {ing.name}
                                            </option>
                                        ))}
                                    </select>
                                    <input
                                        className="col-span-3 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Quantity"
                                        value={ingredient.quantity}
                                        onChange={(e) => updateIngredient(index, 'quantity', e.target.value, ingredient.ingredient_id)}
                                    />
                                    <input
                                        className="col-span-3 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Unit"
                                        value={ingredient.unit}
                                        onChange={(e) => updateIngredient(index, 'unit', e.target.value, ingredient.ingredient_id)}
                                        readOnly={ingredient.name !== ''}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => removeIngredient(index)}
                                        className="col-span-1 flex items-center justify-center hover:bg-gray-100 rounded-lg"
                                    >
                                        <X className="h-4 w-4"/>
                                    </button>
                                </div>
                            ))}
                        </div>

                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-semibold">Steps</h3>
                                <button
                                    type="button"
                                    onClick={addStep}
                                    className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                                >
                                    Add Step
                                </button>
                            </div>
                            {data.steps.map((step, index) => (
                                <div key={index} className="flex gap-2">
                                    <textarea
                                        placeholder={`Step ${index + 1}`}
                                        value={step}
                                        onChange={(e) => updateStep(index, e.target.value)}
                                        className="flex-grow px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => removeStep(index)}
                                        className="flex items-center justify-center p-2 hover:bg-gray-100 rounded-lg"
                                    >
                                        <X className="h-4 w-4"/>
                                    </button>
                                </div>
                            ))}
                        </div>

                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">Nutrition Information</h3>
                            <div className="grid md:grid-cols-3 gap-4">
                                {Object.keys(data.nutrition).map((nutrient) => (
                                    <div key={nutrient}>
                                        <label className="block mb-2 text-sm font-medium capitalize">
                                            {nutrient.replace('_', ' ')}
                                        </label>
                                        <input
                                            type="number"
                                            placeholder={`Enter ${nutrient.replace('_', ' ')}`}
                                            value={data.nutrition[nutrient]}
                                            onChange={(e) => setData(prev => ({
                                                ...prev,
                                                nutrition: {
                                                    ...prev.nutrition,
                                                    [nutrient]: e.target.value
                                                }
                                            }))}
                                            className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="flex justify-end gap-4">
                            <button
                                type="button"
                                onClick={() => window.history.back()}
                                className="px-4 py-2 border rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                            >
                                Create Recipe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default CreateRecipe;