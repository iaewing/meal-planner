import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { RotateCcw } from 'lucide-react';
import { formatQuantity } from '@/utils/formatQuantity';

export default function GroceryList({ auth, mealPlan, ingredients }) {
    const [checkedItems, setCheckedItems] = useState({});

    const toggleItem = (id) => {
        setCheckedItems(prev => ({
            ...prev,
            [id]: !prev[id]
        }));
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Grocery List - ${mealPlan.name}`} />

            <div className="py-6 sm:py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-4 sm:p-6">
                            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 className="text-2xl font-semibold">Grocery List</h2>
                                    <p className="text-gray-600">{mealPlan.name}</p>
                                </div>
                                <Link
                                    href={route('meal-plans.show', mealPlan.id)}
                                    className="text-blue-500 hover:text-blue-700"
                                >
                                    Back to Meal Plan
                                </Link>
                            </div>

                            <div className="space-y-4">
                                {ingredients.length > 0 ? (
                                    <div className="divide-y">
                                        {ingredients.map((ingredient, index) => {
                                            const itemKey = `${ingredient.id}-${ingredient.unit ?? index}`;
                                            return (
                                                <div
                                                    key={itemKey}
                                                    className={`flex items-center gap-4 py-4 ${
                                                        checkedItems[itemKey] ? 'opacity-50' : ''
                                                    }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={checkedItems[itemKey] || false}
                                                        onChange={() => toggleItem(itemKey)}
                                                        className="h-6 w-6 rounded text-blue-600"
                                                    />
                                                    <span className={`text-base ${checkedItems[itemKey] ? 'line-through' : ''}`}>
                                                        {formatQuantity(ingredient.total_quantity)} {ingredient.unit} {ingredient.name}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <p className="text-gray-500 text-center py-8">
                                        No ingredients found in this meal plan.
                                    </p>
                                )}
                            </div>

                            <div className="mt-8">
                                <button
                                    onClick={() => setCheckedItems({})}
                                    className="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-800"
                                >
                                    <RotateCcw className="h-4 w-4" />
                                    Clear All Checkmarks
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
