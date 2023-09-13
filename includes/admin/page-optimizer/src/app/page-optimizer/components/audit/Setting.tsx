import React, {useRef, useState} from 'react';
import {ArrowPathIcon, Cog8ToothIcon} from "@heroicons/react/24/solid";
import {
    CSSDelivery,
    JavascriptDelivery,
    ImageDeliverySVG,
    FontDelivery,
    CloudDelivery,
    PageCache,
} from '../icons/icon-svg';

import { Switch } from "components/ui/switch"
import {ThunkDispatch} from "redux-thunk";
import {AppAction, AppState, RootState} from "../../../../store/app/appTypes";
import {useDispatch} from "react-redux";
import {updateSettings} from "../../../../store/app/appActions";

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

import {Circle, Lock, Settings, SettingsIcon} from "lucide-react";
import {Cog6ToothIcon} from "@heroicons/react/20/solid";
import {Textarea} from "components/ui/textarea";
import {JsonView} from "react-json-view-lite";
import AdditionalInputs from "app/page-optimizer/components/audit/additional-inputs";
import TooltipText from "components/ui/tooltip-text";
import Mode from "app/page-optimizer/components/Mode";
import {useAppContext} from "../../../../context/app";
import Indicator from "components/indicator";
import {cn} from "lib/utils";
import InProgress from "components/in-progress";

interface SettingItemProps {
    audit: Audit
    settings?: AuditSetting;
    index: number;
    hideActions?: boolean
}

const Setting = ({audit, settings, index, hideActions}: SettingItemProps) => {

    if (!settings) {
        return <></>
    }

    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const { mode } = useAppContext()
    const [open, setOpen] = React.useState(false);
    const [mainInput, ...additionalInputs] = settings.inputs
    const [updates, setUpdates] = useState<{
        key: string,
        value: any
    }[]>(additionalInputs.map(({ key, value }) => ({ key, value })))

    const update = (val: any, key: string) => {
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
    }

    let icons = {
        cache : <PageCache/>,
        image : <ImageDeliverySVG/>,
        javascript : <JavascriptDelivery/>,
        js : <JavascriptDelivery/>,
        font : <FontDelivery/>,
        css : <CSSDelivery/>,
    }


    const updateValue = (value: any, key: string) => {

        dispatch(updateSettings(
            audit,
            settings,
            key,
            value
        ));
    }

    // temporarily show this popup on render blocking resources audit
    const showPopover = additionalInputs.length > 0

    const saveAdditionalSettings = () => {

        updates.forEach(({key, value}) => {
            updateValue(value, key)
        })

        setOpen(false);
    }

    const Status = ({ status } : { status: AuditSetting['status']}) => {

        if (!status) {
            return  <></>
        }

        if (status.status === 'failed') {
            return (
                <Indicator className='fill-rose-600'>
                    <div className='flex flex-col gap-0.5'>
                        <span className='flex gap-2 items-center'>
                            <Circle className='w-2 fill-rose-500 stroke-0'/>
                            Error while optimizing {status.error?.code && `(Code: ${status.error?.code})`}
                        </span>
                        <span className='text-brand-500 ml-4'>{status.error?.message ? status.error?.message : 'Failed to Optimize'}</span>
                    </div>
                </Indicator>
            );
        }

        if(status.status === 'progress') {
            return <InProgress/>
        }

        return (
            <>
                {/*{status.status}*/}
            </>
        );
    }

    return (
        <div
            key={index}
            className="relative flex cursor-pointer gap-2 font-medium text-sm hover:bg-brand-100 dark:bg-brand-900 bg-brand-50 border w-fit rounded-xl items-center px-0.5 pr-2 py-1"
        >



            {icons[settings.category as keyof typeof icons]} {settings.name}


            {/*<JsonView data={settings.status}/>*/}


            {!hideActions && (
                <>

                    {mainInput && (
                        // @ts-ignore
                        <Switch disabled={['onboard', 'preview'].includes(mode)} checked={mainInput.value} onCheckedChange={(c: boolean) => updateValue(c, mainInput.key)}/>
                    )}

                    {settings.status && (
                        <div className='px-1'>
                            <Status status={settings.status}/>
                        </div>
                    )}

                    <Mode>
                        {showPopover && (
                            <Dialog open={open} onOpenChange={setOpen}>
                                <DialogTrigger disabled asChild>
                                    <div >
                                        <TooltipText text={`${settings.name} Settings`}>
                                            <Cog6ToothIcon className='w-5 text-brand-400'/>
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
                                        <AdditionalInputs updates={updates} update={update} data={additionalInputs}/>
                                    </div>
                                    <DialogFooter className='px-6 py-3 border-t'>
                                        <AppButton onClick={e => saveAdditionalSettings()} className='text-sm'>Save changes</AppButton>
                                        <AppButton onClick={e => setOpen(false)} dark={false} className='text-sm'>Close</AppButton>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        )}
                    </Mode>

                    <Mode mode='onboard'>
                        <TooltipText text={<><span className='text-purple-750 font-medium'>PRO</span> feature</>}>
                            <Lock className='w-4 text-brand-400'/>
                        </TooltipText>
                    </Mode>
                </>
            )}





            {/*<TooltipText text='Queued'>*/}
            {/*    <div className='bg-sky-400 w-2 h-2 shadow-lg rounded-full -right-1 -top-1'></div>*/}
            {/*</TooltipText>*/}

        </div>
    );
};

export default Setting;
