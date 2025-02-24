import React from 'react';
import { Link } from '@inertiajs/react';

const RecipeList = ({ recipes }) => {
    // Destructure the data from the pagination object
    const recipeData = recipes.data;

    return (
        <div className="container mx-auto px-4 py-8">
            <h1 className="text-3xl font-bold mb-6">My Recipes</h1>

            {recipeData.length === 0 ? (
                <p className="text-gray-600">No recipes found.</p>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {recipeData.map((recipe) => (
                        <div
                            key={recipe.id}
                            className="border rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow"
                        >
                            {/* Image placeholder */}
                            <div className="bg-gray-200 h-48 flex items-center justify-center">
                                {recipe.image_path ? (
                                    <img
                                        src={recipe.image_path}
                                        alt={recipe.name}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <span className="text-gray-500">No Image</span>
                                )}
                            </div>

                            <div className="p-4">
                                <h2 className="text-xl font-semibold mb-2">{recipe.name}</h2>

                                <div className="text-sm text-gray-600 mb-2">
                                    {recipe.description || 'No description available'}
                                </div>

                                <div className="flex justify-between items-center mt-4">
                                    <Link
                                        href={`/recipes/${recipe.id}`}
                                        className="text-blue-600 hover:underline"
                                    >
                                        View Details
                                    </Link>

                                    {recipe.source_url && (
                                        <a
                                            href={recipe.source_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-sm text-gray-500 hover:text-blue-600"
                                        >
                                            Original Recipe
                                        </a>
                                    )}
                                </div>

                                {/* Optional: Quick nutrition info */}
                                {recipe.nutrition && (
                                    <div className="mt-2 text-xs text-gray-500">
                                        {recipe.nutrition.calories && `${recipe.nutrition.calories} cal `}
                                        {recipe.nutrition.protein && `| ${recipe.nutrition.protein}g protein`}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Pagination */}
            {recipes.last_page > 1 && (
                <div className="flex justify-center mt-8 space-x-2">
                    {recipes.links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url || '#'}
                            className={`px-4 py-2 border rounded ${
                                link.active
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-100'
                            }`}
                            disabled={!link.url}
                        >
                            {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
};

export default RecipeList;