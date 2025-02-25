import React from 'react';
import {Head, usePage} from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Show({auth}) {
    const {recipe} = usePage().props

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Show Recipe"/>
            <div className="container mx-auto px-4 py-8">
                <div className="bg-white rounded-lg shadow-lg">
                    <div className="p-6 border-b">
                        <div className="text-2xl font-semibold">{recipe.name}</div>
                        {recipe.source_url && <div>Imported From: <a href={recipe.source_url}>{recipe.source_url}</a></div>}
                    </div>
                    <div className="p-6">
                        <div className="text-lg font-semibold">Ingredients</div>
                        <ul>
                            {recipe.ingredients.map((ingredient, index) => (
                                <li
                                    key={index}
                                    className="capitalize">{ingredient.quantity + ' ' + ingredient.unit + ' of ' + ingredient.name}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="p-6">
                        {recipe.steps.map((step, index) => (
                            <div key={index} className="border-b p-4">
                                <div className="text-lg font-semibold">Step: {step.order + 1}</div>
                                <div className="capitalize">{step.instruction}</div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}