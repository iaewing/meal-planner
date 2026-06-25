import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import axios from 'axios';

export default function Import({ auth, flash }) {
    const { data: imageData, setData: setImageData, post: postImage, processing: imageProcessing, errors: imageErrors, reset: resetImage } = useForm({
        images: [],
    });

    const [dragActive, setDragActive] = useState(false);
    const [feedback, setFeedback] = useState({
        message: '',
        type: '' // 'success' or 'error'
    });

    const [url, setUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    useEffect(() => {
        // Check for flash messages from the server
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

    const handleDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === 'dragenter' || e.type === 'dragover') {
            setDragActive(true);
        } else if (e.type === 'dragleave') {
            setDragActive(false);
        }
    };

    const handleImageSelection = (files) => {
        setImageData('images', Array.from(files));
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleImageSelection(e.dataTransfer.files);
        }
    };

    const selectedImageLabel = imageData.images.length > 0
        ? imageData.images.map((image) => image.name).join(', ')
        : 'Drop images here or click to select';

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Import Recipe</h2>}
        >
            <Head title="Import Recipe" />

            <div className="py-6 sm:py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-6 md:grid-cols-2">
                        {/* URL Import */}
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
                                        {loading ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Queueing...
                                            </>
                                        ) : (
                                            'Queue Import'
                                        )}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                        
                        {/* Image Import */}
                        <div className="overflow-hidden bg-white p-4 shadow-sm sm:rounded-lg sm:p-6">
                            <h3 className="text-lg font-medium text-gray-900">Import from Image</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Upload an image of a recipe to import.
                            </p>
                            <form onSubmit={handleImageSubmit} className="mt-4">
                                <div
                                    className={`rounded-lg border-2 border-dashed p-6 text-center ${
                                        dragActive ? 'border-blue-500 bg-blue-50' : 'border-gray-300'
                                    } ${imageProcessing ? 'opacity-50' : ''}`}
                                    onDragEnter={handleDrag}
                                    onDragLeave={handleDrag}
                                    onDragOver={handleDrag}
                                    onDrop={handleDrop}
                                >
                                    <input
                                        type="file"
                                        id="image"
                                        className="hidden"
                                        onChange={e => handleImageSelection(e.target.files)}
                                        accept="image/*"
                                        multiple
                                        disabled={imageProcessing}
                                    />
                                    <label
                                        htmlFor="image"
                                        className={`cursor-pointer text-gray-600 ${imageProcessing ? 'pointer-events-none' : ''}`}
                                    >
                                        {selectedImageLabel}
                                    </label>
                                    {imageData.images.length > 0 && (
                                        <p className="mt-2 text-sm text-gray-500">
                                            {imageData.images.length} {imageData.images.length === 1 ? 'image' : 'images'} selected
                                        </p>
                                    )}
                                    <InputError message={imageErrors.image} className="mt-2" />
                                    <InputError message={imageErrors.images} className="mt-2" />
                                </div>

                                <div className="mt-4 flex items-center justify-end">
                                    <PrimaryButton
                                        className="w-full justify-center sm:w-auto"
                                        disabled={imageProcessing || imageData.images.length === 0}
                                    >
                                        {imageProcessing ? 'Importing...' : 'Import from Image'}
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
