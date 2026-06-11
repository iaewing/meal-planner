import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Camera,
    CheckCircle2,
    ClipboardList,
    Database,
    LogIn,
    Plus,
    ShoppingBasket,
} from 'lucide-react';

const flowItems = (groceryMealPlan) => [
    {
        label: 'Authenticated Start',
        detail: 'Login redirects back to this screen.',
        href: '/mobile',
        icon: LogIn,
        complete: true,
    },
    {
        label: 'Create Recipe',
        detail: 'Server-side recipe data stays in Laravel Cloud.',
        href: '/recipes/create',
        icon: Plus,
        complete: true,
    },
    {
        label: 'Image Import',
        detail: 'Upload a recipe photo to the hosted import flow.',
        href: '/recipes/import',
        icon: Camera,
        complete: true,
    },
    {
        label: 'Meal Plans',
        detail: 'Open, create, and edit hosted meal plans.',
        href: '/meal-plans',
        icon: ClipboardList,
        complete: true,
    },
    {
        label: 'Grocery List',
        detail: groceryMealPlan
            ? `Open ${groceryMealPlan.name}.`
            : 'Create a meal plan first.',
        href: groceryMealPlan ? `/grocery-list/${groceryMealPlan.id}` : '/meal-plans/create',
        icon: ShoppingBasket,
        complete: Boolean(groceryMealPlan),
    },
];

function FlowLink({ item }) {
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            className="flex items-center gap-4 border-b border-gray-100 px-4 py-4 last:border-b-0 hover:bg-gray-50 sm:px-5"
        >
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded bg-gray-900 text-white">
                <Icon className="h-5 w-5" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block font-medium text-gray-950">{item.label}</span>
                <span className="mt-0.5 block text-sm leading-5 text-gray-600">{item.detail}</span>
            </span>
            {item.complete && <CheckCircle2 className="h-5 w-5 shrink-0 text-green-600" />}
        </Link>
    );
}

export default function Poc({ auth, stats, groceryMealPlan }) {
    const items = flowItems(groceryMealPlan);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Mobile POC</h2>}
        >
            <Head title="Mobile POC" />

            <div className="py-6 sm:py-10">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="flex items-center gap-4 p-5">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded bg-blue-600 text-white">
                                <Database className="h-5 w-5" />
                            </span>
                            <div className="min-w-0">
                                <h1 className="text-lg font-semibold text-gray-950">
                                    Hosted Backend POC
                                </h1>
                                <p className="mt-1 text-sm text-gray-600">
                                    {stats.recipes} recipes, {stats.mealPlans} meal plans
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {items.map((item) => (
                            <FlowLink key={item.label} item={item} />
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
