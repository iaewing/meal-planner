import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { format } from 'date-fns';
import { Plus, Shuffle } from 'lucide-react';

export default function Index({ auth, mealPlans }) {
    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Meal Plans" />

            <div className="py-6 sm:py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 className="text-2xl font-semibold">Meal Plans</h2>
                        <div className="grid grid-cols-2 gap-3 sm:flex">
                            <Link
                                href={route('meal-plans.randomize-form')}
                                className="inline-flex items-center justify-center gap-2 rounded bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600"
                            >
                                <Shuffle className="h-4 w-4" />
                                Random
                            </Link>
                            <Link
                                href={route('meal-plans.create')}
                                className="inline-flex items-center justify-center gap-2 rounded bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600"
                            >
                                <Plus className="h-4 w-4" />
                                Create
                            </Link>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-4 sm:p-6">
                            {mealPlans.data.length > 0 ? (
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {mealPlans.data.map((mealPlan) => (
                                        <div
                                            key={mealPlan.id}
                                            className="rounded-lg border p-4 transition-shadow hover:shadow-lg"
                                        >
                                            <Link href={route('meal-plans.show', mealPlan.id)}>
                                                <h3 className="mb-2 text-lg font-semibold sm:text-xl">{mealPlan.name}</h3>
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
