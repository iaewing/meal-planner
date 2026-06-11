import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { format, parseISO } from 'date-fns';

export default function Home({ auth, recentRecipes, activeMealPlan }) {
    const recipeImageSrc = (recipe) => recipe.image_url || (recipe.image_path ? `/storage/${recipe.image_path}` : null);

    const formatDate = (date) => {
        try {
            return format(parseISO(date), 'EEEE, MMMM d, yyyy');
        } catch (e) {
            return format(new Date(), 'EEEE, MMMM d, yyyy');
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Dashboard" />
            <div className="py-6 sm:py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Welcome Section */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h1 className="text-2xl font-bold text-gray-900 sm:text-3xl">
                                Welcome to Your Meal Planner
                            </h1>
                            <p className="mt-2 text-gray-600">
                                Plan your meals, organize recipes, and generate shopping lists all in one place.
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        {/* Quick Actions */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h2 className="text-xl font-semibold mb-4">Quick Actions</h2>
                                <div className="grid grid-cols-2 gap-3 sm:gap-4">
                                    <Link
                                        href="/recipes/create"
                                        className="block rounded-lg border p-3 text-center hover:bg-gray-50 sm:p-4"
                                    >
                                        <svg className="w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Recipe
                                    </Link>
                                    <Link
                                        href="/meal-plans/create"
                                        className="block rounded-lg border p-3 text-center hover:bg-gray-50 sm:p-4"
                                    >
                                        <svg className="w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Create Meal Plan
                                    </Link>
                                    <Link
                                        href="/recipes/import"
                                        className="block rounded-lg border p-3 text-center hover:bg-gray-50 sm:p-4"
                                    >
                                        <svg className="w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Import Recipe
                                    </Link>
                                    <Link
                                        href="/recipes"
                                        className="block rounded-lg border p-3 text-center hover:bg-gray-50 sm:p-4"
                                    >
                                        <svg className="w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        Browse Recipes
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Today's Meals */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h2 className="text-xl font-semibold mb-4">Today's Meals</h2>
                                {activeMealPlan ? (
                                    <>
                                        <div className="mb-4">
                                            <h3 className="text-lg font-medium">
                                                {activeMealPlan.name}
                                            </h3>
                                            <p className="text-sm text-gray-500">
                                                {formatDate(new Date().toISOString())}
                                            </p>
                                        </div>
                                        {activeMealPlan.recipes.length > 0 ? (
                                            <div className="space-y-4">
                                                {activeMealPlan.recipes.map(recipe => (
                                                    <Link
                                                        key={`${recipe.id}-${recipe.pivot.meal_type}`}
                                                        href={`/recipes/${recipe.id}`}
                                                        className="block p-4 border rounded-lg hover:bg-gray-50"
                                                    >
                                                        <div className="flex items-center justify-between gap-3">
                                                            <div>
                                                                <p className="text-sm font-medium text-gray-500 capitalize">
                                                                    {recipe.pivot.meal_type}
                                                                </p>
                                                                <p className="font-medium">{recipe.name}</p>
                                                            </div>
                                                            {recipeImageSrc(recipe) && (
                                                                <img
                                                                    src={recipeImageSrc(recipe)}
                                                                    alt={recipe.name}
                                                                    className="w-16 h-16 object-cover rounded"
                                                                />
                                                            )}
                                                        </div>
                                                    </Link>
                                                ))}
                                            </div>
                                        ) : (
                                            <p className="text-gray-500">No meals planned for today</p>
                                        )}
                                    </>
                                ) : (
                                    <div className="text-center py-8">
                                        <p className="text-gray-500 mb-4">No active meal plan</p>
                                        <Link
                                            href="/meal-plans/create"
                                            className="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                        >
                                            Create Meal Plan
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Recent Recipes */}
                    <div className="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-4 flex items-center justify-between gap-3">
                                <h2 className="text-xl font-semibold">Recent Recipes</h2>
                                <Link
                                    href="/recipes"
                                    className="text-blue-600 hover:text-blue-800"
                                >
                                    View all →
                                </Link>
                            </div>
                            {recentRecipes.length > 0 ? (
                                <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                                    {recentRecipes.map(recipe => (
                                        <Link
                                            key={recipe.id}
                                            href={`/recipes/${recipe.id}`}
                                            className="block group"
                                        >
                                            <div className="aspect-w-16 aspect-h-9 mb-2">
                                                {recipeImageSrc(recipe) ? (
                                                    <img
                                                        src={recipeImageSrc(recipe)}
                                                        alt={recipe.name}
                                                        className="w-full h-full object-cover rounded-lg"
                                                    />
                                                ) : (
                                                    <div className="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center">
                                                        <svg className="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                )}
                                            </div>
                                            <h3 className="font-medium group-hover:text-blue-600">
                                                {recipe.name}
                                            </h3>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-center py-8">
                                    No recipes yet. Start by adding your favorite recipes!
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 
