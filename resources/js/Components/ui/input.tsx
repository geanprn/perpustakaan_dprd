import { cn } from '@/lib/utils';
import { forwardRef, InputHTMLAttributes } from 'react';

const Input = forwardRef<
    HTMLInputElement,
    InputHTMLAttributes<HTMLInputElement>
>(({ className, type = 'text', ...props }, ref) => (
    <input
        ref={ref}
        type={type}
        className={cn(
            'flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus-visible:border-indigo-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50',
            className,
        )}
        {...props}
    />
));
Input.displayName = 'Input';

export { Input };