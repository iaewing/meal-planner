import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import Checkbox from '@/Components/Checkbox';
import { format } from 'date-fns';
import { route } from 'ziggy-js';

export default function RandomizerForm({ className = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        name: `Random Meal Plan - ${format(new Date(), 'MMM d, yyyy')}`,
        start_date: format(new Date(), 'yyyy-MM-dd'),
        end_date: format(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), 'yyyy-MM-dd'), // 1 week from now
        meal_types: ['breakfast', 'lunch', 'dinner'],
    });

    const toggleMealType = (mealType) => {
        if (data.meal_types.includes(mealType)) {
            setData('meal_types', data.meal_types.filter(type => type !== mealType));
        } else {
            setData('meal_types', [...data.meal_types, mealType]);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('meal-plans.randomize'));
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

                <div className="grid grid-cols-2 gap-4">
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
                    <InputLabel value="Meal Types" />
                    <div className="mt-2 flex flex-wrap gap-4">
                        <label className="flex items-center">
                            <Checkbox
                                checked={data.meal_types.includes('breakfast')}
                                onChange={() => toggleMealType('breakfast')}
                            />
                            <span className="ml-2">Breakfast</span>
                        </label>
                        <label className="flex items-center">
                            <Checkbox
                                checked={data.meal_types.includes('lunch')}
                                onChange={() => toggleMealType('lunch')}
                            />
                            <span className="ml-2">Lunch</span>
                        </label>
                        <label className="flex items-center">
                            <Checkbox
                                checked={data.meal_types.includes('dinner')}
                                onChange={() => toggleMealType('dinner')}
                            />
                            <span className="ml-2">Dinner</span>
                        </label>
                        <label className="flex items-center">
                            <Checkbox
                                checked={data.meal_types.includes('snack')}
                                onChange={() => toggleMealType('snack')}
                            />
                            <span className="ml-2">Snack</span>
                        </label>
                    </div>
                    <InputError message={errors.meal_types} className="mt-2" />
                </div>

                <div className="flex justify-end">
                    <PrimaryButton type="submit" disabled={processing}>
                        Generate Random Meal Plan
                    </PrimaryButton>
                </div>
            </div>
        </form>
    );
} 