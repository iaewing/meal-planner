import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import axios from 'axios';

function CardImageSlot({ id, label, file, onSelect, disabled }) {
    return (
        <div>
            <InputLabel htmlFor={id} value={label} />
            <div className="mt-1 rounded-lg border-2 border-dashed border-gray-300 p-4 text-center">
                <input
                    type="file"
                    id={id}
                    className="hidden"
                    onChange={(e) => onSelect(e.target.files?.[0] ?? null)}
                    accept="image/*"
                    disabled={disabled}
                />
                <label
                    htmlFor={id}
                    className={`cursor-pointer text-sm text-gray-600 ${disabled ? 'pointer-events-none opacity-50' : ''}`}
                >
                    {file ? file.name : 'Click to select image'}
                </label>
            </div>
        </div>
    );
}

export default function Import({ auth, flash }) {
    const { data: imageData, setData: setImageData, post: postImage, processing: imageProcessing, errors: imageErrors, reset: resetImage } = useForm({
        front_image: null,
        back_image: null,
    });

    const [feedback, setFeedback] = useState({
        message: '',
        type: ''
    });

    const [url, setUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    useEffect(() => {
        if (flash && flash.success) {
            setFeedback({
                message: flash.success,
                type: 'success'
            });
        } else if (flash && flash.error) {
            setFeedback({
                message: flash.error,
                type: 'error'
            });
        }
    }, [flash]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await axios.post(route('recipes.import-url'), { url });
            const data = response.data;

            if (data.success) {
                setSuccess(data.message);
                setUrl('');
            } else {
                setError(data.message || 'An error occurred during import');
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to import recipe');
        } finally {
            setLoading(false);
        }
    };

    const handleImageSubmit = (e) => {
        e.preventDefault();
        setFeedback({ message: '', type: '' });
        postImage(route('recipes.import-image'), {
            forceFormData: true,
            onSuccess: () => {
                setFeedback({
                    message: 'Recipe successfully imported from image! Redirecting...',
                    type: 'success'
                });
                resetImage();
            },
            onError: () => {
                setFeedback({
                    message: 'Failed to import recipe from image. Please try another image.',
                    type: 'error'
                });
            }
        });
    };

    const canImportCard = imageData.front_image && imageData.back_image;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Import Recipe</h2>}
        >
            <Head title="Import Recipe" />

            <div className="py-6 sm:py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="overflow-hidden bg-white p-4 shadow-xl sm:rounded-lg sm:p-6">
                            <h3 className="text-lg font-medium text-gray-900">Import from URL</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Enter the URL of a recipe from a supported website.
                            </p>

                            {error && (
                                <div className="mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                                    {error}
                                </div>
                            )}

                            {success && (
                                <div className="mt-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                                    {success}
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="mt-4">
                                <div>
                                    <InputLabel htmlFor="url" value="Recipe URL" />
                                    <TextInput
                                        id="url"
                                        type="url"
                                        className="mt-1 block w-full"
                                        value={url}
                                        onChange={(e) => setUrl(e.target.value)}
                                        required
                                    />
                                </div>

                                <div className="mt-4 flex items-center justify-end">
                                    <PrimaryButton className="w-full justify-center sm:w-auto" disabled={loading}>
                                        {loading ? 'Queueing...' : 'Queue Import'}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>

                        <div className="overflow-hidden bg-white p-4 shadow-sm sm:rounded-lg sm:p-6">
                            <h3 className="text-lg font-medium text-gray-900">Import from Recipe Card</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Upload photos of the front and back of a double-sided recipe card.
                            </p>

                            {feedback.message && (
                                <div className={`mt-4 px-4 py-3 rounded border ${
                                    feedback.type === 'success'
                                        ? 'bg-green-50 border-green-200 text-green-800'
                                        : 'bg-red-50 border-red-200 text-red-800'
                                }`}>
                                    {feedback.message}
                                </div>
                            )}

                            <form onSubmit={handleImageSubmit} className="mt-4 space-y-4">
                                <CardImageSlot
                                    id="front_image"
                                    label="Front (title and ingredients)"
                                    file={imageData.front_image}
                                    onSelect={(file) => setImageData('front_image', file)}
                                    disabled={imageProcessing}
                                />
                                <CardImageSlot
                                    id="back_image"
                                    label="Back (instructions)"
                                    file={imageData.back_image}
                                    onSelect={(file) => setImageData('back_image', file)}
                                    disabled={imageProcessing}
                                />

                                <InputError message={imageErrors.front_image} className="mt-2" />
                                <InputError message={imageErrors.back_image} className="mt-2" />
                                <InputError message={imageErrors.images} className="mt-2" />

                                <div className="flex items-center justify-end">
                                    <PrimaryButton
                                        className="w-full justify-center sm:w-auto"
                                        disabled={imageProcessing || !canImportCard}
                                    >
                                        {imageProcessing ? 'Importing...' : 'Import Recipe Card'}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
