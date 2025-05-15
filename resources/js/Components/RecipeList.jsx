import React, { useState, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import debounce from 'lodash/debounce';

const RecipeList = ({ recipes, filters = {} }) => {
    // Destructure the data from the pagination object
    const recipeData = recipes.data;
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // Debounce search to avoid too many requests
    const debouncedSearch = debounce((value) => {
        router.get(route('recipes.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);

    // Handle search input change
    const handleSearchChange = (e) => {
        const value = e.target.value;
        setSearchTerm(value);
        debouncedSearch(value);
    };

    // Clear search when component unmounts
    useEffect(() => {
        return () => {
            debouncedSearch.cancel();
        };
    }, []);

    return (
        <div className="container mx-auto px-4 py-8">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold">My Recipes</h1>
                <div className="flex space-x-3">
                    <Link
                        href={route('recipes.import-form')}
                        className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded"
                    >
                        Import Recipe
                    </Link>
                    <Link
                        href={route('recipes.create')}
                        className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                    >
                        Create Recipe
                    </Link>
                </div>
            </div>

            {/* Search Input */}
            <div className="mb-6">
                <div className="relative">
                    <input
                        type="text"
                        placeholder="Search recipes by name..."
                        value={searchTerm}
                        onChange={handleSearchChange}
                        className="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                    {searchTerm && (
                        <button 
                            onClick={() => {
                                setSearchTerm('');
                                router.get(route('recipes.index'));
                            }}
                            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                        >
                            ✕
                        </button>
                    )}
                </div>
            </div>

            {recipeData.length === 0 ? (
                <div className="text-center py-8">
                    <p className="text-gray-600 mb-2">No recipes found.</p>
                    {searchTerm && (
                        <p className="text-gray-500">
                            Try adjusting your search or <button 
                                onClick={() => {
                                    setSearchTerm('');
                                    router.get(route('recipes.index'));
                                }}
                                className="text-blue-500 hover:underline"
                            >
                                clear the search
                            </button>
                        </p>
                    )}
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {recipeData.map((recipe) => (
                        <Link
                            key={recipe.id}
                            href={`/recipes/${recipe.id}`}
                            className="block border rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow bg-white"
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
                                    <span className="text-blue-600">
                                        View Details
                                    </span>

                                    {recipe.source_url && (
                                        <a
                                            href={recipe.source_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-sm text-gray-500 hover:text-blue-600"
                                            onClick={(e) => e.stopPropagation()}
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
                        </Link>
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