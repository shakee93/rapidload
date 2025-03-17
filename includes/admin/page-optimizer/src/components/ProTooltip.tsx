import React from 'react';
import { Lock, ExternalLink, PlugIcon, ArrowUpCircle, ArrowUp, ArrowUpRight } from 'lucide-react';
import * as Tooltip from '@radix-ui/react-tooltip';
import TooltipText from './ui/tooltip-text';
import AppButton from './ui/app-button';
import { useAppContext } from '../context/app';

interface ProTooltipProps {
    children: React.ReactNode;
    text?: React.ReactNode;
}

interface TooltipTextProps {
    children: React.ReactNode;
    text: React.ReactNode;
}

const ProTooltipContent = () => {
    const { options, uucssGlobal } = useAppContext();

    return (
        <div className="space-y-2 py-2">
            <h3 className="text-gray-900 font-semibold"><><span className='text-purple-750 font-medium'>PRO</span> feature</></h3>
            <div className="text-gray-600 font-normal text-sm">
                Get the most out of your website with exclusive Pro features: <br />
                <div className='py-2'>
                    - AI-powered <span className='font-medium'>✦</span> <br />
                    - Faster speeds <br />
                    - Advanced features <br />
                    - Priority support
                </div>
            </div>
            <AppButton
                className={`bg-[#09090b] flex gap-2 items-center dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 dark:text-brand-300 cursor-pointer px-4 rounded-lg`}
                onClick={() => (uucssGlobal?.activation_url ? window.location.href = uucssGlobal?.activation_url
                    : window.open('https://rapidload.ai/', '_blank'))}
            >
                <ArrowUpRight className="w-4 h-4" />
                Upgrade Now
            </AppButton>
        </div>
    )
}

export const ProTooltip: React.FC<TooltipTextProps> = ({ children }) => {
    return (
        <TooltipText
            className='cursor-pointer max-w-[300px]'
            text={<ProTooltipContent />}
        >
            {children}
        </TooltipText>
    );
};

export default ProTooltip;
