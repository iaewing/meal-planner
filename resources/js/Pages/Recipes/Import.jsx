import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import { useToast } from '@/Components/ToastProvider';
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

function ImportContent() {
    const { showToast } = useToast();
    const { data: imageData, setData: setImageData, post: postImage, processing: imageProcessing, errors: imageErrors, reset: resetImage } = useForm({
        front_image: null,
        back_image: null,
    });

    const [url, setUrl] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            const response = await axios.post(route('recipes.import-url'), { url });
            const data = response.data;

            if (data.success) {
                showToast(data.message);
                setUrl('');
            } else {
                showToast(data.message || 'An error occurred during import', 'error');
            }
        } catch (err) {
            showToast(err.response?.data?.message || 'Failed to import recipe', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleImageSubmit = (e) => {
        e.preventDefault();
        postImage(route('recipes.import-image'), {
            forceFormData: true,
            onSuccess: () => {
                resetImage();
            },
            onError: () => {
                showToast('Failed to import recipe from image. Please try another image.', 'error');
            },
        });
    };

    const canImportCard = imageData.front_image && imageData.back_image;

    return (
        <div className="py-6 sm:py-12">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="overflow-hidden bg-white p-4 shadow-xl sm:rounded-lg sm:p-6">
                        <h3 className="text-lg font-medium text-gray-900">Import from URL</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Enter the URL of a recipe from a supported website. Instagram post and reel URLs are supported when the caption is publicly available.
                        </p>

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
    );
}

export default function Import({ auth }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Import Recipe</h2>}
        >
            <Head title="Import Recipe" />
            <ImportContent />
        </AuthenticatedLayout>
    );
}
