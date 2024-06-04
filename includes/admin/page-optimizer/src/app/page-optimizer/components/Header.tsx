import {
    ArrowPathIcon,
    ArrowTopRightOnSquareIcon,
    ComputerDesktopIcon,
    DevicePhoneMobileIcon, XMarkIcon, EyeIcon, InformationCircleIcon
} from "@heroicons/react/24/outline";
import {CheckCircleIcon, XCircleIcon} from "@heroicons/react/24/solid";
import ThemeSwitcher from "components/ui/theme-switcher";
import React, {useEffect, useMemo, useState} from "react";
import {useAppContext} from "../../../context/app";
import TooltipText from "components/ui/tooltip-text";
import {ThunkDispatch} from "redux-thunk";
import {AppAction, AppState, RootState} from "../../../store/app/appTypes";
import {useDispatch, useSelector} from "react-redux";
import {changeReport, fetchData, getCSSStatus} from "../../../store/app/appActions";
import {optimizerData} from "../../../store/app/appSelector";
import {Button} from "components/ui/button";
import AppButton from "components/ui/app-button";
import {cn} from "lib/utils";
import {
    Loader,
    LogOut,
    Monitor,
    RefreshCw, ThumbsUpIcon
} from "lucide-react";
import { useTour } from '@reactour/tour'
import Steps, {AuditSteps, FinalSteps} from "components/tour/steps";
import useCommonDispatch from "hooks/useCommonDispatch";
import {m, AnimatePresence, motion} from "framer-motion";
import ScaleUp from "components/animation/ScaleUp";
import {setCommonRootState, setCommonState} from "../../../store/common/commonActions";
import equal from 'fast-deep-equal/es6/react'
import UnsavedChanges from "app/page-optimizer/components/footer/unsaved-changes";
import UrlPreview from "app/page-optimizer/components/footer/url-preview";
import SaveChanges from "app/page-optimizer/components/footer/save-changes";
import {Switch} from "components/ui/switch";
import {getTestModeStatus} from "../../../store/app/appActions";
import {useToast} from "components/ui/use-toast";

// const Header = ({ url }: { url: string}) => {
const Header = ({ url }: { url: string}) => {

    const tourPromptKey = 'titan-tour-prompt'

    const {
        setShowOptimizer ,
        options,
        version,
        mode,
        showInprogress,
        setShowInprogress
    } = useAppContext()

    const { activeReport,
        loading, error,
        settings,
        data,
        revisions
    } = useSelector(optimizerData);
    const {inProgress } = useCommonDispatch()
    const {
        activeTab,
        activeMetric,
        settingsMode,
        dispatch: commonDispatch
    } = useCommonDispatch()

    const {testMode} = useSelector((state: RootState) => state.app);

    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const [timeoutId, setTimeoutId] = useState<NodeJS.Timeout | null>(null);
    const [localSwitchState, setLocalSwitchState] = useState<boolean>(false);
    const [previewButton, setPreviewButton]= useState<boolean>(false);
    const [testModeLoading, setTestModeLoading]= useState<boolean>(false);

    useEffect(() => {
        dispatch(getTestModeStatus(options, url));
    }, [dispatch]);

    useEffect(() => {
        let toastInstance: ReturnType<typeof toast> | undefined;
        if(settingsMode==='turboMax' && !localSwitchState){
            toastInstance = toast({
                description: (
                    <>
                        <div className='flex w-full gap-2 text-center items-center'>
                            <InformationCircleIcon className='w-5 text-orange-600'/>
                            Do you want to turn on test mode?
                            <AppButton onClick={e => {
                                handleSwitchChange(true);
                            }} variant='outline'>
                                Yes
                            </AppButton>
                            <AppButton onClick={e => {
                                // Dismiss the toast immediately
                                if (toastInstance) {
                                    toastInstance.dismiss();
                                }
                            }} variant='outline'>
                                No
                            </AppButton>
                        </div>
                    </>
                ),
            }, 50000);

        }else if(settingsMode!='turboMax'){
            console.log(settingsMode)
            if (toastInstance) {
                console.log("test")
                toastInstance.dismiss();
            }
        } else if(!testMode){
            setLocalSwitchState(false);
        }
    }, [settingsMode]);

    useEffect(() => {
        if (testMode) {
            setLocalSwitchState(testMode.status || false);
            setPreviewButton(true);
        }
    }, [testMode]);

    const { toast } = useToast();

    const handleSwitchChange = async (isChecked: boolean) => {
      //  setLocalSwitchState(isChecked);
        setTestModeLoading(true);

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        const newTimeoutId = setTimeout(async () => {
            const result = await dispatch(getTestModeStatus(options, url, String(isChecked)));
            if (result.success) {
                setTestModeLoading(false);
                toast({
                    description: (
                        <>
                            <div className='flex w-full gap-2 text-center'>
                                Test Mode turned {localSwitchState ? 'off' : 'on'} successfully
                                <CheckCircleIcon className='w-5 text-green-600'/>
                            </div>
                            <div className='flex w-full gap-2 text-center'>
                                <InformationCircleIcon className='w-5 text-green-600'/>
                                Test Mode changes are on live
                            </div>
                        </>
                    ),
                });
            } else {
                toast({
                    description: (
                        <div className='flex w-full gap-2 text-center'>
                            Failed to turn on Test mode: {result.error}
                            <XCircleIcon className='w-5 text-red-600' />
                        </div>
                    ),
                });
                setLocalSwitchState(false);
                setTestModeLoading(false);
            }
        }, 200);
        setTimeoutId(newTimeoutId);

    };

    return (
        <>

        <header className='z-[110000] w-full px-6 py-3 flex gap-3 justify-between border-b backdrop-blur-sm dark:bg-brand-930/80 bg-brand-50/75 '>
            <div className='flex gap-12 items-center'>
                <div className='relative'>
                    <img className='w-36' src={ options?.page_optimizer_base ? (options?.page_optimizer_base + `/logo.svg`) : '/logo.svg'} alt='RapidLoad - #1 to unlock breakneck page speed'/>
                    {version && (
                        <span className='absolute text-xxs w-[200px] left-[72px] top-[1px] dark:text-brand-500 text-brand-400'>TITAN v{version}</span>
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
                    <div className='flex overflow-hidden border rounded-2xl shadow' data-tour="current-url">
                        <UrlPreview/>
                        <UnsavedChanges
                            title='Analyze without applying optimization?'
                            description="Your changes are not saved yet. If you analyze now, your recent edits won't be included."
                            action='Apply Optimization'
                            cancel='Discard & Analyze'
                            onCancel={() => {
                                dispatch(fetchData(options, url, true))
                                commonDispatch(setCommonState('openAudits', []))
                            }}
                            onClick={() =>  {

                                if(!inProgress || !loading){
                                    dispatch(fetchData(options, url, true))
                                }
                                commonDispatch(setCommonState('openAudits', []))

                            }} >
                            <TooltipText
                                text='Analyze the page'>
                                <AppButton asChild={true} data-tour='analyze'

                                           className={cn(
                                               'transition-none rounded-none h-12 px-3 pr-3.5 ' +
                                               'border-r-0 border-l border-t-0 border-b-0 bg-transparent ',
                                           )}
                                           variant='outline'>
                                    <div className={`flex flex-col gap-[1px] items-center`}>
                                        <RefreshCw className={cn(
                                            'w-4 -mt-0.5',
                                            loading && 'animate-spin'
                                        )}/>
                                        <span className='text-xxs font-normal text-brand-500'>Analyze </span>
                                    </div>
                                </AppButton>
                            </TooltipText>
                        </UnsavedChanges>
                    </div>
                </div>
            </div>



            <div className='flex relative gap-4 items-center'>
                {!loading && !showInprogress && (
                    <>
                    {!error && (
                        <>
                            <div className="flex overflow-hidden border rounded-2xl shadow">

                                <button
                                    onClick={() => {
                                        window.open(options.optimizer_url + '?rapidload_preview_optimization', '_blank');
                                    }}
//cursor-not-allowed opacity-50 pointer-events-none
                                    className={`flex gap-2 items-center text-sm h-10 rounded-[14px] bg-brand-200/80 dark:bg-primary dark:hover:bg-primary/90 px-4 py-2 m-1 pr-5 ${
                                     revisions.length > 0 
                                        ? '' : ''}`} data-tour="preview-button">

                                    <ArrowTopRightOnSquareIcon className='w-5 text-gray-500'/>
                                    Preview
                                </button>

                                <div
                                    className="text-sm flex gap-1 justify-end items-center ml-4 mr-4 text-left w-full dark:text-brand-300" data-tour="test-mode">
                                    <div>{testModeLoading ? ('Test Mode') : ('Test Mode')}</div>
                                    {testModeLoading ? (
                                        <Loader className='ml-2 mr-[7px] w-5 animate-spin'/>
                                    ) : (
                                        <Switch
                                            checked={localSwitchState}
                                            onCheckedChange={(checked) => handleSwitchChange(checked)}

                                        />
                                    )
                                    }


                                </div>
                            </div>


                            <SaveChanges/>
                        </>
                    )}
                    </>
                )}

                <UnsavedChanges
                    onCancel={() => {
                        setShowOptimizer(false)
                    }}
                    cancel='Discard & Leave'
                    onClick={() => {
                        setShowOptimizer(false);
                        setShowInprogress(false);
                    }}>
                    <TooltipText text='Close Optimizer'>
                        <LogOut className={cn(
                            'h-5 w-5 dark:text-brand-300 text-brand-600 transition-opacity',
                        )}/>
                    </TooltipText>
                </UnsavedChanges>
            </div>
        </header>
            {localSwitchState && !loading && !showInprogress && !testModeLoading &&
                <motion.div  initial={{ opacity: 0, y: -10 }}
                         animate={{ opacity: 1, y: 0 }}
                         exit={{ opacity: 0, y: -10 }}
                         transition={{
                             ease: 'easeInOut',
                             duration: 0.5,
                         }}
                         className="z-[100000] w-full text-[13px] bg-[#D9CAEB] items-center text-center py-0.5 top-[74px] absolute"><span className="font-semibold text-purple-900">Test Mode turned on,</span> optimizations are safely previewed without affecting your live website. Perfect for experimentation and fine-tuning.</motion.div>
            }
        </>
    )
}

export default Header