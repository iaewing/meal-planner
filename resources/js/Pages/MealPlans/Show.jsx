import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { format } from 'date-fns';
import DangerButton from '@/Components/DangerButton';
import { router } from '@inertiajs/react';

export default function Show({ auth, mealPlan }) {
    const deleteMealPlan = () => {
        if (confirm('Are you sure you want to delete this meal plan?')) {
            router.delete(route('meal-plans.destroy', mealPlan.id));
        }
    };

    // Group meals by date
    const mealsByDate = mealPlan.recipes.reduce((acc, recipe) => {
        const date = recipe.pivot.planned_date;
        if (!acc[date]) {
            acc[date] = [];
        }
        acc[date].push({
            ...recipe,
            meal_type: recipe.pivot.meal_type,
        });
        return acc;
    }, {});

    // Sort dates
    const sortedDates = Object.keys(mealsByDate).sort();

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={mealPlan.name} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <div>
                                    <h2 className="text-2xl font-semibold">{mealPlan.name}</h2>
                                    <p className="text-gray-600">
                                        {format(new Date(mealPlan.start_date), 'MMM d, yyyy')} -{' '}
                                        {format(new Date(mealPlan.end_date), 'MMM d, yyyy')}
                                    </p>
                                </div>
                                <div className="flex gap-4">
                                    <Link
                                        href={route('meal-plans.grocery-list', mealPlan.id)}
                                        className="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                                    >
                                        View Grocery List
                                    </Link>
                                    <Link
                                        href={route('meal-plans.edit', mealPlan.id)}
                                        className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                    >
                                        Edit Plan
                                    </Link>
                                    <DangerButton onClick={deleteMealPlan}>
                                        Delete Plan
                                    </DangerButton>
                                </div>
                            </div>

                            <div className="space-y-8">
                                {sortedDates.map(date => (
                                    <div key={date} className="border rounded-lg p-6">
                                        <h3 className="text-xl font-semibold mb-4">
                                            {format(new Date(date), 'EEEE, MMMM d, yyyy')}
                                        </h3>
                                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                            {mealsByDate[date]
                                                .sort((a, b) => {
                                                    const mealOrder = {
                                                        breakfast: 1,
                                                        lunch: 2,
                                                        dinner: 3,
                                                        snack: 4,
                                                    };
                                                    return mealOrder[a.meal_type] - mealOrder[b.meal_type];
                                                })
                                                .map(recipe => (
                                                    <div
                                                        key={`${date}-${recipe.id}-${recipe.meal_type}`}
                                                        className="bg-gray-50 rounded-lg p-4"
                                                    >
                                                        <div className="flex items-center justify-between mb-2">
                                                            <span className="text-sm font-medium text-gray-500 capitalize">
                                                                {recipe.meal_type}
                                                            </span>
                                                        </div>
                                                        <Link
                                                            href={route('recipes.show', recipe.id)}
                                                            className="block hover:text-blue-600"
                                                        >
                                                            <h4 className="text-lg font-medium">{recipe.name}</h4>
                                                        </Link>
                                                        {recipe.image_path && (
                                                            <img
                                                                src={`/storage/${recipe.image_path}`}
                                                                alt={recipe.name}
                                                                className="w-full h-32 object-cover rounded-md mt-2"
                                                            />
                                                        )}
                                                        <div className="mt-2 text-sm text-gray-600">
                                                            <p>{recipe.description}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}