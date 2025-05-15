import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import axios from 'axios';

export default function Import({ auth, flash }) {
    const { data: urlData, setData: setUrlData, post: postUrl, processing: urlProcessing, errors: urlErrors, reset: resetUrl } = useForm({
        url: '',
    });

    const { data: imageData, setData: setImageData, post: postImage, processing: imageProcessing, errors: imageErrors, reset: resetImage } = useForm({
        image: null,
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
                setTimeout(() => {
                    window.location.href = data.recipe.url;
                }, 2000);
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

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            setImageData('image', e.dataTransfer.files[0]);
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Import Recipe</h2>}
        >
            <Head title="Import Recipe" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* URL Import */}
                        <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
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

                                <div className="flex items-center justify-end mt-4">
                                    <PrimaryButton className="ml-4" disabled={loading}>
                                        {loading ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Importing...
                                            </>
                                        ) : (
                                            'Import Recipe'
                                        )}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                        
                        {/* Image Import */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900">Import from Image</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Upload an image of a recipe to import.
                            </p>
                            <form onSubmit={handleImageSubmit} className="mt-4">
                                <div
                                    className={`border-2 border-dashed rounded-lg p-6 text-center ${
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
                                        onChange={e => setImageData('image', e.target.files[0])}
                                        accept="image/*"
                                        disabled={imageProcessing}
                                    />
                                    <label
                                        htmlFor="image"
                                        className={`cursor-pointer text-gray-600 ${imageProcessing ? 'pointer-events-none' : ''}`}
                                    >
                                        {imageData.image
                                            ? imageData.image.name
                                            : 'Drop an image here or click to select'}
                                    </label>
                                    <InputError message={imageErrors.image} className="mt-2" />
                                </div>

                                <div className="flex items-center justify-end mt-4">
                                    <PrimaryButton
                                        disabled={imageProcessing || !imageData.image}
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