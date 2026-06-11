import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import MealPlanForm from './Partials/MealPlanForm';

export default function Edit({ auth, mealPlan, recipes }) {
    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Edit Meal Plan" />

            <div className="py-6 sm:py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-4 sm:p-6">
                            <h2 className="mb-6 text-2xl font-semibold">Edit Meal Plan</h2>
                            <MealPlanForm
                                mealPlan={mealPlan}
                                recipes={recipes}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
