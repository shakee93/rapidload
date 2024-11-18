import {
    DevicePhoneMobileIcon
} from "@heroicons/react/24/outline";
import React from "react";
import { useAppContext } from "../../../context/app";
import TooltipText from "components/ui/tooltip-text";
import { ThunkDispatch } from "redux-thunk";
import { AppAction, RootState } from "../../../store/app/appTypes";
import { useDispatch, useSelector } from "react-redux";
import { changeReport, fetchReport } from "../../../store/app/appActions";
import { optimizerData } from "../../../store/app/appSelector";
import AppButton from "components/ui/app-button";
import { cn } from "lib/utils";
import {
    Link2,
    LogOut,
    Monitor,
    RefreshCw,
    Rocket,
    TextCursor,
    UserCircle
} from "lucide-react";
import useCommonDispatch from "hooks/useCommonDispatch";
import { AnimatePresence, motion } from "framer-motion";
import { setCommonRootState, setCommonState } from "../../../store/common/commonActions";
import UnsavedChanges from "app/page-optimizer/components/footer/unsaved-changes";
import UrlPreview from "app/page-optimizer/components/footer/url-preview";
import SaveChanges from "app/page-optimizer/components/footer/save-changes";
import { useRootContext } from "../../../context/root";
import { MousePointerClick } from "lucide-react";

const Header = ({ url }: { url: string }) => {

    const {
        setShowOptimizer,
        options,
        version,
    } = useAppContext()

    const { activeReport,
        loading,
        testMode,
        reanalyze
    } = useSelector(optimizerData);
    const { inProgress } = useCommonDispatch()
    const {
        dispatch: commonDispatch
    } = useCommonDispatch()


    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const { isDark } = useRootContext();



    return (
        <>

            <header
                className='z-[110000] w-full px-6 py-3 flex gap-3 justify-between border-b backdrop-blur-sm dark:bg-brand-930/80 bg-brand-50/75 '>
                <div className='flex gap-6 items-center'>
                    <div className='relative'>
                        <img
                            className='w-8'
                            src={isDark
                                ? options?.page_optimizer_base
                                    ? `${options?.page_optimizer_base}/dark-logo.svg`
                                    : '/dark-logo.svg'
                                : options?.page_optimizer_base
                                    ? `${options?.page_optimizer_base}/logo-icon.svg`
                                    : '/logo-icon.svg'
                            }
                            alt='RapidLoad - #1 to unlock breakneck page speed'
                        />
                    </div>
                    <div className='flex flex-column items-center gap-3'>
                        {/* <div data-tour='switch-report-strategy'
                            className='select-none relative  flex dark:bg-brand-800 py-0.5 bg-brand-200/80 rounded-2xl cursor-pointer'>
                            <div className={cn(
                                'absolute shadow-md translate-x-0 left-0.5 w-[55px] rounded-[14px] -z-1 duration-300 h-11 text-sm flex flex-column gap-2 px-4 py-3 font-medium dark:bg-brand-950 bg-brand-0',
                                activeReport === 'desktop' && 'w-[55px] -translate-x-1 left-1/2'
                            )}>
                            </div>

                            <TooltipText text="Mobile">
                                <div onClick={() => dispatch(changeReport('mobile'))}
                                    className={`relative z-1 text-sm flex flex-column gap-2 px-5 py-3 font-medium rounded-2xl`}>
                                    <DevicePhoneMobileIcon className="h-5 w-5 font-medium dark:text-brand-500" />
                                </div>
                            </TooltipText>

                            <TooltipText text='Desktop'>
                                <div onClick={() => dispatch(changeReport('desktop'))}
                                    className={`relative z-1 text-sm flex flex-column gap-2 pl-2 px-5 py-3 font-medium rounded-2xl`}>
                                    <Monitor className="h-5 w-5 font-medium dark:text-brand-500 " />
                                </div>
                            </TooltipText>
                        </div> */}
                        <div className='flex overflow-hidden border rounded-2xl shadow' data-tour="current-url">
                            <UrlPreview />
                            <UnsavedChanges
                                title='Analyze without applying optimization?'
                                description="Your changes are not saved yet. If you analyze now, your recent edits won't be included."
                                action='Apply Optimization'
                                cancel='Discard & Analyze'
                                onCancel={() => {
                                    dispatch(fetchReport(options, url, true))
                                    commonDispatch(setCommonState('openAudits', []))
                                }}
                                onClick={() => {

                                    if (!inProgress || !loading) {
                                        dispatch(fetchReport(options, url, true))
                                    }
                                    commonDispatch(setCommonState('openAudits', []))

                                }}>


                                <div className="flex">
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
                                                )} />
                                                <span className='text-xxs font-normal text-brand-500'>Analyze </span>
                                            </div>
                                        </AppButton>
                                    </TooltipText>

                                    {/* <TooltipText text='Change URL'>
                                        <AppButton
                                            onClick={() => {
                                                commonDispatch(setCommonRootState('showDemo', false))
                                            }}
                                            asChild={true} data-tour='demo-url'

                                            className={cn(
                                                'transition-none rounded-none h-12 px-3 pr-3.5 ' +
                                                'border-r-0 border-l border-t-0 border-b-0 bg-transparent ',
                                            )}
                                            variant='outline'>
                                            <div className={`flex flex-col gap-[1px] items-center`}>
                                                <Link2 className={cn(
                                                    'w-4 -mt-0.5',
                                                )} />
                                                <span className='text-xxs font-normal text-brand-500'>Change</span>
                                            </div>
                                        </AppButton>
                                    </TooltipText> */}
                                </div>
                            </UnsavedChanges>
                        </div>
                    </div>
                </div>

                {/* 
                <div className='flex relative gap-4 items-center'>

                    <>
                        <AppButton className='text-sm flex overflow-hidden justify-between select-none relative text-sm gap-2 h-12 rounded-[14px]'>
                            <Rocket className='w-5 mr-0.5' />
                            Speed Up My Site Now
                        </AppButton>
                    </>

                </div> */}


                <div className='flex gap-4 items-center'>
                    <p className='text-sm text-brand-500 flex gap-4 items-center'>
                        <motion.div
                            initial={{ opacity: 0, scale: 0.8 }}
                            animate={{
                                opacity: 1,
                                scale: [0.8, 1.2, 1],
                                x: [0, -5, 5, 0]
                            }}
                            transition={{
                                duration: 1.5,
                                repeat: Infinity,
                                repeatType: "reverse",
                                ease: "easeInOut"
                            }}
                        >
                            <MousePointerClick className='w-5 h-5 text-[#6e23ad]' />
                        </motion.div>
                        <motion.span
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 0.3 }}
                        >
                            This is an <span className='text-[#6e23ad]'>interactive demo</span>, try it out!
                        </motion.span>
                    </p>
                </div>
            </header>
            <AnimatePresence>
                {testMode && (
                    <motion.div
                        initial={{ opacity: 0, y: -10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -10 }}
                        transition={{
                            ease: 'easeInOut',
                            duration: 0.5,
                        }}
                        className="z-[110000] w-full text-[13px] bg-[#f7b250] items-center text-center py-0.5 top-[69px] absolute dark:bg-brand-950"
                    >
                        <span className="font-semibold text-black dark:text-brand-300">Test Mode turned on,</span>
                        optimizations are safely previewed without affecting your live website. Perfect for experimentation and fine-tuning.
                    </motion.div>
                )}

            </AnimatePresence>
        </>
    )
}

export default Header