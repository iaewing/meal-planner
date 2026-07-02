import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';

const ToastContext = createContext(null);

function ToastItem({ toast, onDismiss }) {
    useEffect(() => {
        const timer = setTimeout(() => onDismiss(toast.id), 4000);

        return () => clearTimeout(timer);
    }, [toast.id, onDismiss]);

    const styles = toast.type === 'error'
        ? 'bg-red-50 border-red-200 text-red-800'
        : 'bg-green-50 border-green-200 text-green-800';

    return (
        <div className={`pointer-events-auto w-full max-w-sm rounded-lg border px-4 py-3 shadow-lg ${styles}`}>
            <div className="flex items-start justify-between gap-3">
                <p className="text-sm font-medium">{toast.message}</p>
                <button
                    type="button"
                    onClick={() => onDismiss(toast.id)}
                    className="text-current opacity-70 hover:opacity-100"
                    aria-label="Dismiss notification"
                >
                    ×
                </button>
            </div>
        </div>
    );
}

export function ToastProvider({ children }) {
    const { flash } = usePage().props;
    const [toasts, setToasts] = useState([]);

    const dismissToast = useCallback((id) => {
        setToasts((current) => current.filter((toast) => toast.id !== id));
    }, []);

    const showToast = useCallback((message, type = 'success') => {
        if (!message) {
            return;
        }

        setToasts((current) => [
            ...current,
            {
                id: `${Date.now()}-${Math.random()}`,
                message,
                type,
            },
        ]);
    }, []);

    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, 'success');
        }

        if (flash?.error) {
            showToast(flash.error, 'error');
        }
    }, [flash?.success, flash?.error, showToast]);

    return (
        <ToastContext.Provider value={{ showToast }}>
            {children}
            <div className="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6">
                {toasts.map((toast) => (
                    <ToastItem key={toast.id} toast={toast} onDismiss={dismissToast} />
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    const context = useContext(ToastContext);

    if (!context) {
        throw new Error('useToast must be used within a ToastProvider');
    }

    return context;
}
