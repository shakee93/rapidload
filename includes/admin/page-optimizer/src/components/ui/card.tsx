import {FC, forwardRef, ReactNode} from "react";
import {cn} from "lib/utils";
interface CardProps {
    children: ReactNode;
    className?: string;
    padding?: string;
    spreader?: boolean
}

const Card = forwardRef<HTMLDivElement, CardProps>(({ children, className, padding, spreader = false }, ref) => {

    const pad = padding? padding : 'py-4 px-6'

    return (
        <div ref={ref} className={cn(
            `w-full dark:bg-brand-950 bg-brand-50 border rounded-3xl`,
            pad,
            className,
            spreader ? 'mb-0' : ''
        )}>
            {children}

            {spreader && (
                <div className={cn(
                    'absolute w-full -bottom-1 z-[-20] transition-opacity duration-500 delay-500',

                )}>
                    <div className='rounded-3xl h-10 border dark:border-brand-700 mx-1.5 bg-brand-50 dark:bg-brand-900 opacity-80'></div>
                    <div className='absolute w-full -bottom-1 z-[-1]'>
                        <div className=' rounded-3xl h-5 border dark:border-brand-700 mx-4 bg-brand-50 dark:bg-brand-900 opacity-40'></div>
                    </div>
                </div>
            )}


        </div>
    )
})

export default Card