import {
    ArrowPathIcon,
    ArrowTopRightOnSquareIcon,
    ComputerDesktopIcon,
    DevicePhoneMobileIcon, XMarkIcon
} from "@heroicons/react/24/outline";
import ThemeSwitcher from "components/ui/theme-switcher";
import React, {useEffect, useMemo, useState} from "react";
import {useAppContext} from "../../../context/app";
import TooltipText from "components/ui/tooltip-text";
import {ThunkDispatch} from "redux-thunk";
import {AppAction, AppState, RootState} from "../../../store/app/appTypes";
import {useDispatch, useSelector} from "react-redux";
import {changeReport, fetchData} from "../../../store/app/appActions";
import {optimizerData} from "../../../store/app/appSelector";
import {Button} from "components/ui/button";
import AppButton from "components/ui/app-button";
import {cn} from "lib/utils";
import {
    ArrowDownUp,
    GraduationCap,
    GraduationCapIcon,
    LogOut,
    Monitor,
    MoreHorizontal,
    MoreVertical,
    RefreshCcw
} from "lucide-react";
import { useTour } from '@reactour/tour'
import Steps, {AuditSteps, FinalSteps} from "components/tour/steps";
import useCommonDispatch from "hooks/useCommonDispatch";
import {AnimatePresence} from "framer-motion";
import ScaleUp from "components/animation/ScaleUp";
import {setCommonRootState, setCommonState} from "../../../store/common/commonActions";
import equal from 'fast-deep-equal/es6/react'
import UnsavedChanges from "app/page-optimizer/components/footer/unsaved-changes";
import UrlPreview from "app/page-optimizer/components/footer/url-preview";
import SaveChanges from "app/page-optimizer/components/footer/save-changes";

const Header = ({ url }: { url: string}) => {

    const tourPromptKey = 'titan-tour-prompt'

    const {
        setShowOptimizer ,
        options,
        version,
        mode
    } = useAppContext()

    const { activeReport,
        loading
    } = useSelector(optimizerData);

    const {
        activeTab,
        activeMetric,
        dispatch: commonDispatch
    } = useCommonDispatch()



    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();



    return (

        <header className='z-[110000] w-full px-6 py-3 mt-[3px] flex gap-3 justify-between border-b backdrop-blur-sm dark:bg-brand-930/80 bg-brand-50/75 '>
            <div className='flex gap-12 items-center'>
                <div className='relative'>
                    <img className='w-36' src={ options?.page_optimizer_base ? (options?.page_optimizer_base + `/logo.svg`) : '/logo.svg'} alt='RapidLoad - #1 to unlock breakneck page speed'/>
                    {version && (
                        <span className='absolute text-xxs left-[72px] top-[1px] dark:text-brand-500 text-brand-400'>TITAN v{version}</span>
                    )}
                </div>
                <div className='flex flex-column items-center gap-3'>
                    <div data-tour='switch-report-strategy' className='select-none relative  flex dark:bg-brand-800 py-0.5 bg-brand-200/80 rounded-2xl cursor-pointer'>
                        <div className={cn(
                            'absolute shadow-md translate-x-0 left-0.5 w-[55px] rounded-[14px] -z-1 duration-300 h-11 text-sm flex flex-column gap-2 px-4 py-3 font-medium dark:bg-brand-950 bg-brand-0',
                            activeReport === 'desktop' && 'w-[55px] -translate-x-1 left-1/2'
                        )}>
                        </div>

                        <TooltipText text="Mobile">
                            <div onClick={() => dispatch(changeReport('mobile'))}
                                 className={`relative z-1 text-sm flex flex-column gap-2 px-5 py-3 font-medium rounded-2xl`}>
                                <DevicePhoneMobileIcon  className="h-5 w-5 font-medium dark:text-brand-500" />
                            </div>
                        </TooltipText>

                        <TooltipText text='Desktop'>
                            <div onClick={() => dispatch(changeReport('desktop'))}
                                 className={`relative z-1 text-sm flex flex-column gap-2 pl-2 px-5 py-3 font-medium rounded-2xl`}>
                                <Monitor className="h-5 w-5 font-medium dark:text-brand-500 " />
                            </div>
                        </TooltipText>
                    </div>
                    <div className='flex overflow-hidden border rounded-2xl shadow'>
                        <UrlPreview/>
                        <UnsavedChanges
                            title='Analyze without Saving?'
                            description="Your changes are not saved yet. If you analyze now, your recent edits won't be included."
                            action='Save & Analyze'
                            cancel='Discard & Analyze'
                            onCancel={() => {
                                dispatch(fetchData(options, url, true))
                                commonDispatch(setCommonState('openAudits', []))
                            }}
                            onClick={() =>  {
                                dispatch(fetchData(options, url, true))
                                commonDispatch(setCommonState('openAudits', []))
                            }} >
                            <TooltipText
                                text='Analyze the page'>
                                <AppButton data-tour='analyze'

                                           className={cn(
                                               'transition-none rounded-none h-12 px-3 pr-3.5 ' +
                                               'border-r-0 border-l border-t-0 border-b-0 bg-transparent ',
                                           )}
                                           variant='outline'>
                                    <div className='flex flex-col gap-[1px] items-center'>
                                        <RefreshCcw className={cn(
                                            'w-4 -mt-0.5',
                                            loading && 'animate-spin'
                                        )}/>
                                        <span className='text-xxs font-normal text-brand-500'>Analyze </span>
                                    </div>
                                </AppButton>
                            </TooltipText>
                        </UnsavedChanges>
                        <TooltipText
                            text='Switch URL to optimize'>
                            <AppButton data-tour='analyze'

                                       className={cn(
                                           'transition-none rounded-none h-12 pl-3 pr-3.5' +
                                           ' border-l border-r-0 border-t-0 border-b-0 bg-transparent hover:opacity-100',
                                       )}
                                       variant='outline'>
                                <div className='flex flex-col gap-[1px] items-center'>
                                    <ArrowDownUp className={cn(
                                        'w-4 -mt-0.5'
                                    )}/>
                                    <span className='text-xxs font-normal text-brand-500'>Switch</span>
                                </div>
                            </AppButton>
                        </TooltipText>
                    </div>
                </div>
            </div>



            <div className='flex relative gap-4 items-center'>
                <SaveChanges/>

                <UnsavedChanges
                    onCancel={() => { setShowOptimizer(false) }}
                    cancel='Discard & Leave'
                    onClick={() => { setShowOptimizer(false) }} >
                    <TooltipText text='Close Optimizer'>
                        <LogOut className={cn(
                            'h-5 w-5 dark:text-brand-300 text-brand-600 transition-opacity',
                        )} />
                    </TooltipText>
                </UnsavedChanges>
            </div>
        </header>

    )
}

export default Header