import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Import({ auth }) {
    const { data: urlData, setData: setUrlData, post: postUrl, processing: urlProcessing, errors: urlErrors } = useForm({
        url: '',
    });

    const { data: imageData, setData: setImageData, post: postImage, processing: imageProcessing, errors: imageErrors } = useForm({
        image: null,
    });

    const [dragActive, setDragActive] = useState(false);

    const handleUrlSubmit = (e) => {
        e.preventDefault();
        postUrl('import/url');
    };

    const handleImageSubmit = (e) => {
        e.preventDefault();
        postImage('import/image', {
            forceFormData: true,
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
        <AuthenticatedLayout user={auth.user}>
            <Head title="Import Recipe" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* URL Import */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h2 className="text-lg font-medium mb-4">Import from URL</h2>
                            <form onSubmit={handleUrlSubmit}>
                                <div>
                                    <InputLabel htmlFor="url" value="Recipe URL" />
                                    <TextInput
                                        id="url"
                                        type="url"
                                        className="mt-1 block w-full"
                                        value={urlData.url}
                                        onChange={e => setUrlData('url', e.target.value)}
                                        required
                                    />
                                    <InputError message={urlErrors.url} className="mt-2" />
                                </div>

                                <div className="mt-4">
                                    <PrimaryButton disabled={urlProcessing}>
                                        Import from URL
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>

                        {/* Image Import */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h2 className="text-lg font-medium mb-4">Import from Image</h2>
                            <form onSubmit={handleImageSubmit}>
                                <div
                                    className={`border-2 border-dashed rounded-lg p-6 text-center ${
                                        dragActive ? 'border-blue-500 bg-blue-50' : 'border-gray-300'
                                    }`}
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
                                    />
                                    <label
                                        htmlFor="image"
                                        className="cursor-pointer text-gray-600"
                                    >
                                        {imageData.image
                                            ? imageData.image.name
                                            : 'Drop an image here or click to select'}
                                    </label>
                                    <InputError message={imageErrors.image} className="mt-2" />
                                </div>

                                <div className="mt-4">
                                    <PrimaryButton
                                        disabled={imageProcessing || !imageData.image}
                                    >
                                        Import from Image
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