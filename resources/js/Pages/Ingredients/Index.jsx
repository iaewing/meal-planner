import React, {useState} from 'react';
import {Head, usePage} from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({auth}) {
    const {ingredients} = usePage().props
    const [newIngredient, setNewIngredient] = useState({
        name: '',
        unit: '',
    });

    function addIngredient() {
        console.log(newIngredient);
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="All Ingredients"/>
            <div>
                <input
                    className="col-span-3 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Name"
                    required
                    onChange={(e) => setNewIngredient(prevState => ({
                        ...prevState,
                        name: e.target.value
                    }))}
                />
                <input
                    className="col-span-3 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Unit of Measurement (grams, pounds)"
                    onChange={(e) => setNewIngredient(prevState => ({
                        ...prevState,
                        unit: e.target.value
                    }))}
                />
                <button
                    type="button"
                    onClick={addIngredient}
                    className="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50"
                >
                    Add Ingredient
                </button>
            </div>
            <ul>
                {ingredients.map((ingredient, index) => (
                    <li
                        key={index}
                        className="capitalize">{ingredient.unit + ' of ' + ingredient.name}
                    </li>
                ))}
            </ul>
        </AuthenticatedLayout>
    );
}