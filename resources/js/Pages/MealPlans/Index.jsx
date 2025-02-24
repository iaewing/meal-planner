import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { format } from 'date-fns';

export default function Index({ auth, mealPlans }) {
    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Meal Plans" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-2xl font-semibold">Meal Plans</h2>
                        <Link
                            href={route('meal-plans.create')}
                            className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                        >
                            Create Meal Plan
                        </Link>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {mealPlans.data.length > 0 ? (
                                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {mealPlans.data.map((mealPlan) => (
                                        <div
                                            key={mealPlan.id}
                                            className="border rounded-lg p-4 hover:shadow-lg transition-shadow"
                                        >
                                            <Link href={route('meal-plans.show', mealPlan.id)}>
                                                <h3 className="text-xl font-semibold mb-2">{mealPlan.name}</h3>
                                                <div className="text-gray-600">
                                                    <p>
                                                        {format(new Date(mealPlan.start_date), 'MMM d, yyyy')} -{' '}
                                                        {format(new Date(mealPlan.end_date), 'MMM d, yyyy')}
                                                    </p>
                                                    <p className="mt-2">
                                                        {mealPlan.recipes.length} {mealPlan.recipes.length === 1 ? 'recipe' : 'recipes'}
                                                    </p>
                                                </div>
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <p className="text-gray-500">No meal plans yet. Create your first one!</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}