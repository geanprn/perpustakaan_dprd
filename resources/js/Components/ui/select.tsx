import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';
import { forwardRef, SelectHTMLAttributes } from 'react';

/**
 * Select asli yang diberi gaya seperti Input — menjaga aksesibilitas dan
 * pengetikan TypeScript yang ketat tanpa dependensi tambahan.
 */
const Select = forwardRef<
    HTMLSelectElement,
    SelectHTMLAttributes<HTMLSelectElement>
>(({ className, children, ...props }, ref) => (
    <div className="relative">
        <select
            ref={ref}
            className={cn(
                'flex h-10 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pl-3 pr-9 text-sm shadow-sm focus-visible:border-indigo-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        >
            {children}
        </select>

        <ChevronDown
            className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
            aria-hidden="true"
        />
    </div>
));
Select.displayName = 'Select';

export { Select };
