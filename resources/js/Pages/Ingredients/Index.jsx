import React, {useState} from 'react';
import {Head, usePage} from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({auth}) {
    const {ingredients} = usePage().props
    const [newIngredient, setNewIngredient] = useState({
        name: '',
        unit: '',
    });

    function addIngredient() {
        console.log(newIngredient);
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="All Ingredients"/>
            <div className="py-12 bg-gray-50">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Add Ingredient Section */}
                    <div className="mb-8">
                        <h2 className="text-2xl font-bold text-gray-800 mb-4">Add a New Ingredient</h2>
                        <div className="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <div className="flex flex-col sm:flex-row items-center gap-4">
                                <input
                                    className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                                    placeholder="Ingredient name"
                                    required
                                    onChange={(e) => setNewIngredient(prevState => ({
                                        ...prevState,
                                        name: e.target.value
                                    }))}
                                />
                                <input
                                    className="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                                    placeholder="Unit (grams, pounds, etc.)"
                                    onChange={(e) => setNewIngredient(prevState => ({
                                        ...prevState,
                                        unit: e.target.value
                                    }))}
                                />
                                <button
                                    type="button"
                                    onClick={addIngredient}
                                    className="w-full sm:w-auto px-6 py-3 bg-indigo-400 text-white font-medium rounded-lg hover:bg-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Add Ingredient
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Available Ingredients Section */}
                    <div className="mb-6">
                        <h2 className="text-2xl font-bold text-gray-800 mb-4">Available Ingredients</h2>
                        <div className="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                            {ingredients.length === 0 ? (
                                <div className="p-8 text-center text-gray-500">
                                    No ingredients added yet. Add your first ingredient above.
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                                    {ingredients.map((ingredient, index) => (
                                        <div
                                            key={index}
                                            className="p-4 rounded-lg border border-gray-100 hover:border-indigo-200 hover:shadow-md transition-all"
                                        >
                                            <h3 className="font-semibold text-lg text-gray-800 capitalize mb-1">
                                                {ingredient.name}
                                            </h3>
                                            <div className="text-sm text-gray-500 mb-1">Unit of Measurement</div>
                                            <div className="font-medium text-indigo-600 capitalize">
                                                {ingredient.unit || "Not specified"}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    )
        ;
}