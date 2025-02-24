import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RecipeList from "@/Components/RecipeList.jsx";

export default function Index({auth}) {
    const { recipes } = usePage().props
    console.log(recipes)
    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="All Recipes"/>

            <RecipeList recipes={recipes}/>
        </AuthenticatedLayout>
    );
}