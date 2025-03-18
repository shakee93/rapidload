import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ArrowPathIcon, CheckCircleIcon, Cog8ToothIcon, XCircleIcon } from "@heroicons/react/24/solid";

import { Switch } from "components/ui/switch"
import { ThunkDispatch } from "redux-thunk";
import { AppAction, AppState, RootState } from "../../../../store/app/appTypes";
import { useDispatch } from "react-redux";
import { updateSettings } from "../../../../store/app/appActions";

import AppButton from "components/ui/app-button"

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

import { Circle, GanttChart, Loader, Lock, RefreshCcw, Settings, SettingsIcon } from "lucide-react";
import { Cog6ToothIcon } from "@heroicons/react/20/solid";
import { Textarea } from "components/ui/textarea";
import { JsonView } from "react-json-view-lite";
import Fields from "app/page-optimizer/components/audit/additional-inputs";
import TooltipText from "components/ui/tooltip-text";
import Mode from "app/page-optimizer/components/Mode";
import { useAppContext } from "../../../../context/app";
import Indicator from "components/indicator";
import { cn } from "lib/utils";
import InProgress from "components/in-progress";
import { Button } from "components/ui/button";
import ApiService from "../../../../services/api";
import { toast } from "components/ui/use-toast";
import SlideLeft from "components/animation/SlideLeft";
import { AnimatePresence } from "framer-motion";
import {
    CloudDelivery, CSSDelivery, FontDelivery,
    ImageDeliverySVG,
    JavascriptDelivery,
    PageCache
} from "app/page-optimizer/components/icons/category-icons";
import ProTooltip from "components/ProTooltip";

interface SettingItemProps {
    updateValue?: (setting: AuditSetting, value: any, key: any) => void
    settings?: AuditSetting;
    index: number;
    showIcons?: boolean
    hideActions?: boolean
}

export const Status = React.memo(({ status }: { status: AuditSetting['status'] }) => {

    if (!status) {
        return <></>
    }

    if (status.status === 'failed') {
        return (
            <Indicator className='fill-rose-600'>
                <div className='flex flex-col gap-0.5'>
                    <span className='flex gap-2 items-center'>
                        <Circle className='w-2 fill-rose-500 stroke-0' />
                        Error while optimizing {status.error?.code && `(Code: ${status.error?.code})`}
                    </span>
                    <span className='text-brand-500 ml-4'>{status.error?.message ? status.error?.message : 'Failed to Optimize'}</span>
                </div>
            </Indicator>
        );
    }

    if (status.status === 'queued') {
        return (
            <Indicator className='animate-pulse fill-amber-500'>
                <div className='flex gap-2 items-center'><GanttChart className='w-4 animate-pulse text-amber-500' />
                    Waiting in the queue
                </div>
            </Indicator>
        )
    }

    if (status.status === 'processing') {
        return <InProgress />
    }

    if (status.status === 'success') {
        return (
            <Indicator className='fill-green-600'>
                <div className='flex gap-2 items-center'>
                    <CheckCircleIcon className='w-5 text-green-600 dark:text-brand-800' />Successfully Optimized
                </div>
            </Indicator>
        )
    }

    return <></>;
})


const Setting = ({ updateValue, settings, index, hideActions, showIcons = true }: SettingItemProps) => {

    if (!settings) {
        return <></>
    }

    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const { mode, options } = useAppContext()
    const [open, setOpen] = React.useState(false);
    const [loading, setLoading] = useState(false)

    const [mainInput, ...additionalInputs] = useMemo(() => settings.inputs, [settings])

    const [updates, setUpdates] = useState<{
        key: string,
        value: any
    }[]>(additionalInputs.map(({ key, value }) => ({ key, value })))

    const update = useCallback((val: any, key: string) => {
        let changed = updates.find(i => i.key === key);

        if (changed) {
            setUpdates(
                updates.map(_i => {

                    if (_i.key === key) {
                        _i.value = val
                    }

                    return _i;
                })
            )
        } else {
            setUpdates([...updates, {
                key: key,
                value: val
            }])
        }
    }, [updates, settings, additionalInputs])

    let icons = useMemo(() => ({
        cache: <PageCache />,
        cdn: <CloudDelivery />,
        image: <ImageDeliverySVG />,
        javascript: <JavascriptDelivery />,
        js: <JavascriptDelivery />,
        font: <FontDelivery />,
        css: <CSSDelivery />,
    }), [])

    // temporarily show this popup on render blocking resources audit
    const showPopover = useMemo(() => additionalInputs.length > 0, [additionalInputs])

    const saveAdditionalSettings = useCallback(() => {

        updates.forEach(({ key, value }) => {
            updateValue(settings, value, key)
        })

        setOpen(false);
    }, [updates, open])


    // TODO: temp fix for scroll view leakage
    // useEffect(() => {
    //     if (open) {
    //         const content =  document.getElementById('rapidload-page-optimizer-content');
    //         content?.scrollTo(0, 0)
    //     }
    // }, [open])




    const buttonAction = async (input: AuditSettingInput) => {
        setLoading(true)

        try {

            let api = new ApiService(options, undefined, input.action || input.value || undefined)
            await api.post()

            toast({
                description: <div className='flex w-full gap-2 text-center'>Your action is successful <CheckCircleIcon className='w-5 text-green-600' /></div>,
            })

        } catch (error: any) {

            setLoading(false)
            toast({
                description: <div className='flex w-full gap-2 text-center'>{error.message} <XCircleIcon className='w-5 text-red-600' /></div>,
            })
        }

        setLoading(false);
    }

    return (
        <div
            key={index}
            className={cn(
                'relative flex cursor-pointer gap-2 font-medium text-sm hover:bg-brand-100 dark:bg-brand-900 bg-brand-50 border w-fit rounded-xl items-center pr-2 py-1',
                showIcons ? 'px-0.5' : 'px-2'
            )}
        >

            {showIcons && icons[settings.category as keyof typeof icons]}
            {settings.name}

            {!hideActions && (
                <>

                    {mainInput && (
                        <>
                            {mainInput.control_type === 'checkbox' && (
                                <Switch disabled={['onboard', 'preview'].includes(mode)}
                                    checked={mainInput.value}
                                    onCheckedChange={(c: boolean) => updateValue(settings, c, mainInput.key)} />

                            )}
                            {mainInput.control_type === 'button' && (
                                <Button loading={loading} disabled={loading} onClick={e => buttonAction(mainInput)}
                                    className='flex -mr-0.5 gap-1 py-1 px-2.5 h-auto rounded-[8px]'>
                                    <span className='text-xs py-1 px-0.5'>{mainInput.control_label}</span>
                                </Button>
                            )}
                        </>
                    )}

                    {settings.status && (
                        <div className='px-1'>
                            <Status status={settings.status} />
                        </div>
                    )}

                    <Mode>
                        {showPopover && (
                            <Dialog open={open} onOpenChange={setOpen}>
                                <DialogTrigger disabled asChild>
                                    <div >
                                        <TooltipText text={`${settings.name} Settings`}>
                                            <Cog6ToothIcon className='w-5 text-brand-400' />
                                        </TooltipText>
                                    </div>
                                </DialogTrigger>
                                <DialogContent asChild className="sm:max-w-[450px] cursor-auto">

                                    <DialogHeader className='border-b px-6 py-7'>
                                        <DialogTitle>{settings.name} Settings</DialogTitle>
                                        <DialogDescription>
                                            Make changes to your <span className='lowercase'>{settings.name}</span> settings here. Click save when you're done.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div className="grid gap-4 px-6 py-4">
                                        {additionalInputs.map((input, index) =>
                                            <div key={index} >
                                                <Fields input={input} updates={updates} update={update} />
                                            </div>
                                        )}
                                    </div>

                                    <DialogFooter className='px-6 py-3 border-t'>
                                        <AppButton onClick={e => saveAdditionalSettings()} className='text-sm'>Save changes</AppButton>
                                        <AppButton onClick={e => setOpen(false)} variant='outline' className='text-sm'>Close</AppButton>
                                    </DialogFooter>

                                </DialogContent>
                            </Dialog>


                        )}


                    </Mode>

                    <Mode mode='onboard'>
                    <ProTooltip
                                                       >
                                                        <Lock className='w-4 text-brand-400' />
                                                    </ProTooltip>
                    </Mode>
                </>
            )}
        </div>
    );
};

export default Setting;
