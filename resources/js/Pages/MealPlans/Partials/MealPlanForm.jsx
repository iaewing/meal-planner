import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Plus, Trash2 } from 'lucide-react';
import { format } from 'date-fns';
import { route } from 'ziggy-js';

export default function MealPlanForm({ recipes, mealPlan = null, className = '' }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: mealPlan?.name ?? '',
        start_date: mealPlan?.start_date ?? format(new Date(), 'yyyy-MM-dd'),
        end_date: mealPlan?.end_date ?? format(new Date(), 'yyyy-MM-dd'),
        meals: mealPlan?.recipes?.map(recipe => ({
            recipe_id: recipe.id,
            planned_date: recipe.pivot.planned_date,
            meal_type: recipe.pivot.meal_type,
        })) ?? [],
    });

    const addMeal = () => {
        setData('meals', [
            ...data.meals,
            {
                recipe_id: '',
                planned_date: data.start_date,
                meal_type: 'dinner',
            },
        ]);
    };

    const removeMeal = (index) => {
        setData('meals', data.meals.filter((_, i) => i !== index));
    };

    const updateMeal = (index, field, value) => {
        const updatedMeals = [...data.meals];
        updatedMeals[index] = {
            ...updatedMeals[index],
            [field]: value,
        };
        setData('meals', updatedMeals);
    };

    const submit = (e) => {
        e.preventDefault();
        if (mealPlan) {
            put(route('meal-plans.update', mealPlan.id));
        } else {
            post(route('meal-plans.store'));
        }
    };

    return (
        <form onSubmit={submit} className={className}>
            <div className="space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="Plan Name" />
                    <TextInput
                        id="name"
                        type="text"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={e => setData('name', e.target.value)}
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="start_date" value="Start Date" />
                        <TextInput
                            id="start_date"
                            type="date"
                            className="mt-1 block w-full"
                            value={data.start_date}
                            onChange={e => setData('start_date', e.target.value)}
                            required
                        />
                        <InputError message={errors.start_date} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="end_date" value="End Date" />
                        <TextInput
                            id="end_date"
                            type="date"
                            className="mt-1 block w-full"
                            value={data.end_date}
                            onChange={e => setData('end_date', e.target.value)}
                            required
                        />
                        <InputError message={errors.end_date} className="mt-2" />
                    </div>
                </div>

                <div>
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <h3 className="text-lg font-medium">Meals</h3>
                        <SecondaryButton type="button" onClick={addMeal} className="gap-2 whitespace-nowrap">
                            <Plus className="h-4 w-4" />
                            Add Meal
                        </SecondaryButton>
                    </div>

                    {data.meals.map((meal, index) => (
                        <div key={index} className="mb-4 rounded-lg bg-gray-50 p-4">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div>
                                    <InputLabel value="Recipe" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        value={meal.recipe_id}
                                        onChange={e => updateMeal(index, 'recipe_id', e.target.value)}
                                        required
                                    >
                                        <option value="">Select a recipe</option>
                                        {recipes.map(recipe => (
                                            <option key={recipe.id} value={recipe.id}>
                                                {recipe.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors[`meals.${index}.recipe_id`]} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel value="Date" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={meal.planned_date}
                                        onChange={e => updateMeal(index, 'planned_date', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors[`meals.${index}.planned_date`]} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel value="Meal Type" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        value={meal.meal_type}
                                        onChange={e => updateMeal(index, 'meal_type', e.target.value)}
                                        required
                                    >
                                        <option value="breakfast">Breakfast</option>
                                        <option value="lunch">Lunch</option>
                                        <option value="dinner">Dinner</option>
                                        <option value="snack">Snack</option>
                                    </select>
                                    <InputError message={errors[`meals.${index}.meal_type`]} className="mt-2" />
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => removeMeal(index)}
                                className="mt-3 inline-flex items-center gap-2 text-sm font-medium text-red-600 hover:text-red-800"
                            >
                                <Trash2 className="h-4 w-4" />
                                Remove
                            </button>
                        </div>
                    ))}
                    <InputError message={errors.meals} className="mt-2" />
                </div>

                <div className="sticky bottom-20 -mx-4 border-t border-gray-200 bg-white/95 px-4 py-4 sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:px-0 sm:py-0">
                    <PrimaryButton type="submit" disabled={processing} className="w-full justify-center sm:w-auto">
                        {mealPlan ? 'Update Meal Plan' : 'Create Meal Plan'}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    );
}
