import React, { ReactNode, useEffect, useMemo, useState } from 'react';
import { Archive, CheckCircleIcon, Code, FileCode2, FileJson, FileJson2, FileMinus2, FileType, Loader, LucideIcon, RefreshCw, RemoveFormatting, Type, XCircleIcon } from "lucide-react";
import { useSelector } from "react-redux";
import { optimizerData } from "../store/app/appSelector";
import Card from "components/ui/card";
import AppButton from "components/ui/app-button";
import TooltipText from './ui/tooltip-text';
import { toast } from './ui/use-toast';
import { useAppContext } from '../context/app';
import ApiService from "../services/api";

interface ActionItem {
    id: string;
    label: string;
    icon: LucideIcon;
}

let icons: { [key: string]: ReactNode } = {
    clear_cache: <Archive className='w-3.5' />,
    clear_page_cache: <FileMinus2 className='w-5' />,
    clear_all_optimizations: <FileCode2 className='w-3.5' />,
    clear_css_optimizations: <FileType className='w-3.5' />,
    clear_js_optimizations: <Code className='w-3.5' />,
    clear_font_optimizations: <RemoveFormatting className='w-3.5' />,
    default: <RefreshCw className='w-3.5' />
}

type GlobalAction = AuditSettingInput & {
    category: string
    loading?: boolean
}

const RapidLoadActions: React.FC = () => {
    const { actions, settings } = useSelector(optimizerData);
    const {options} = useAppContext()

    
    let [_actions, setActions] = useState<GlobalAction[]>(actions.map((a: any) => ({
        ...a,
        loading: false
    })))

    useEffect(() => {
        setActions(actions.map((a: any) => ({
            ...a,
            loading: false
        })))
    }, [actions])

    const triggerAction = async (action: GlobalAction) => {

        try {
            setActions(prev => prev.map(a =>
                a.control_icon === action.control_icon ? {
                    ...a,
                    loading: true
                } : a
            ))

            if (action.control_label === 'Flush Cache') {
                const api = new ApiService(options);
                const url = options.optimizer_url;
                await api.post('clear_page_cache', {
                    url
                });
            } else {
                let result = await fetch(action.action.replace(/&amp;/g, '&'));
            }

            toast({
                duration: 10,
                description: <div className='flex w-full gap-2 text-center items-center'>Successfully completed <CheckCircleIcon className='w-5 text-green-600' /> </div>
            })

        } catch (e: any) {
            toast({
                duration: 10,
                description: <div className='flex w-full gap-2 text-center items-center'>An error occurred - {e?.message} <XCircleIcon className='w-5 text-red-600' /> </div>
            });
            console.error(e);
        }

        setActions(prev => prev.map(a =>
            a.control_icon === action.control_icon ? {
                ...a,
                loading: false
            } : a
        ))
    }


    if(!actions) {
        return <></>
    }

    return (
        <div className='items-center justify-center flex py-1'>
            {_actions.filter((action: GlobalAction) => action.category === 'general').map((action) => (
                <AppButton
                    key={action.control_icon}
                    disabled={action.loading}
                    onClick={e => triggerAction(action)}
                    className='rounded-[15px] h-12 px-3 dark:bg-brand-800 dark:hover:bg-brand-950' variant='ghost'>
                    <div className='flex flex-col gap-1 items-center'>
                        {action.loading ?
                            <span>
                                <Loader className='motion-safe:animate-spin w-4' />
                            </span> :
                            icons[action.control_icon] || icons.default}
                        <span className='text-xxs font-normal text-brand-500'>{action.control_label}</span>
                    </div>
                </AppButton>
            ))}
        </div>
    );
};

export default RapidLoadActions;