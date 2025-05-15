import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RandomizerForm from './Partials/RandomizerForm';

export default function Randomize({ auth }) {
    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Generate Random Meal Plan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h2 className="text-2xl font-semibold mb-6">Generate Random Meal Plan</h2>
                            <p className="mb-6 text-gray-600">
                                Create a random meal plan by selecting your preferred date range and meal types.
                                The system will automatically assign random recipes to each meal.
                            </p>
                            <RandomizerForm />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 