import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Show({auth}) {
    const { recipe } = usePage().props

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Show Recipe"/>

            <div>{recipe.name}</div>
            <div>From: <a href={recipe.source_url}>{recipe.source_url}</a></div>
        </AuthenticatedLayout>
    );
}