import React from 'react';

export default function RecipeRating({ rating, onRate, readOnly = false, size = 'md' }) {
    const starSize = size === 'sm' ? 'text-base' : 'text-2xl';

    return (
        <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
            {[1, 2, 3, 4, 5].map((value) => {
                const filled = rating != null && value <= rating;

                return (
                    <button
                        key={value}
                        type="button"
                        disabled={readOnly}
                        onClick={() => onRate?.(value)}
                        className={`${starSize} ${readOnly ? 'cursor-default' : 'cursor-pointer hover:scale-110'} transition-transform ${
                            filled ? 'text-yellow-400' : 'text-gray-300'
                        }`}
                        aria-label={`Rate ${value} out of 5`}
                    >
                        ★
                    </button>
                );
            })}
        </div>
    );
}
