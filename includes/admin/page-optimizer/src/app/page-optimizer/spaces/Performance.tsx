import Card from "components/ui/card";
import { AnimatePresence, m } from "framer-motion";
import Audit from "app/page-optimizer/components/audit/Audit";
import React, { useEffect, useRef, useState, useMemo } from "react";
import { useSelector } from "react-redux";
import { optimizerData } from "../../../store/app/appSelector";
import { useAppContext } from "../../../context/app";
import { cn } from "lib/utils";
import TogglePerformance from "components/toggle-performance";
import useCommonDispatch from "hooks/useCommonDispatch";
import { setCommonState } from "../../../store/common/commonActions";
import { CopyMinus, FoldVertical, GaugeCircle, Layers, Loader, SplitSquareVertical } from "lucide-react";
import TooltipText from "components/ui/tooltip-text";
import ScaleUp from "components/animation/ScaleUp";
import { BoltIcon, MinusCircleIcon, PlusCircleIcon } from "@heroicons/react/24/solid";
import AuditList from "app/page-optimizer/components/AuditList";
import SpeedSettings from "app/page-optimizer/spaces/SpeedSettings";
import { AIStarIcon, OptimizationsIcon } from "../components/icons/icon-svg";
import Optimizations from "./Optimizations";
import PageSpeedInsights from "./PageSpeedInsights";
import { SettingsLine } from "../components/icons/line-icons";
import { useRootContext } from "../../../context/root";


const welcomePopupKey = 'new-titan-prompt'
const Performance = () => {
    const { data, loading, reanalyze, settings, error } = useSelector(optimizerData);
    const { isDark } = useRootContext()
    const { dispatch, activeTab, openAudits, storePassedAudits, settingsMode, diagnosticLoading } = useCommonDispatch()
    const [isSticky, setIsSticky] = useState(false);
    const navbarRef = useRef(null);
    const [open, setOpen] = React.useState(false);
    const [showNewTitanModelPopup, setShowNewTitanModelPopup] = useState(!!localStorage.getItem(welcomePopupKey));
    const {
        options,
        setOpenAudits,
        mode,
        manipulatingStyles,
        savingData,
        togglePerformance,
        setTogglePerformance,
        version
    } = useAppContext()

    const loadingTab: Tab = {
        key: "passed_audits",
        name: "Passed Audits",
        color: 'border-zinc-600',
        activeColor: 'bg-green-600'
    }

    const tabs: Tab[] = [
        {
            key: "opportunities",
            name: "Opportunities",
            color: 'border-orange-400',
            activeColor: 'bg-orange-400',
        },
        {
            key: "diagnostics",
            name: "Diagnostics",
            color: 'border-yellow-400 ',
            activeColor: 'bg-yellow-400 '
        },
        {
            key: "passed_audits",
            name: "Passed Audits",
            color: 'border-green-600',
            activeColor: 'bg-green-600'
        },

    ];

    const isOnboardMode = !['onboard', 'preview'].includes(mode);

    useEffect(() => {

        setTimeout(() => {
            if (isOnboardMode && !showNewTitanModelPopup) {
                setShowNewTitanModelPopup(true);
                setOpen(true);
            }
        }, 1000)

    }, []);

    const [isCheckedPopup, setIsCheckedPopup] = useState(false);
    const saveNewTitanModelPopup = (open: boolean) => {

        if (isCheckedPopup) {
            localStorage.setItem(welcomePopupKey, 'true');
        }
        if (!open) {
            setOpen(false);
        }

    };

    const getWidthForActiveTab = (activeTab: string) => {
        switch (activeTab) {
            case 'configurations':
                return 100;
            case 'optimizations':
                return 480;
            case 'opportunities':
                return 285;
            case 'diagnostics':
                return 285;
            case 'passed_audits':
                return 285;
            default:
                return 285;
        }
    };


    return (

        <div data-tour='nav-bar'>
            {/* <h2 className="text-lg ml-5 mb-4 flex gap-2 font-normal items-center">
                {!togglePerformance && <TogglePerformance />}
                Fix Performance Issues</h2> */}
            <div ref={navbarRef} style={{ height: '1px' }}></div>
            <div className={cn(
                'tabs flex sticky -top-1 dark:bg-brand-800 bg-brand-200 px-2 py-2 rounded-3xl gap-2 w-fit',
            )}>
                <div

                    onClick={() => dispatch(setCommonState('activeTab', 'configurations'))}
                    className={cn(

                        `whitespace-nowrap border-r rounded-[20px] cursor-pointer w-[170px]  flex items-center gap-2 px-5 py-3 text-sm font-medium`,
                        activeTab === 'configurations' ? "font-medium bg-brand-0 dark:bg-brand-950" : "dark:hover:text-brand-300",
                        diagnosticLoading && 'cursor-not-allowed opacity-90 pointer-events-none'
                    )}
                    data-tour="speed-settings"> <BoltIcon className='w-4 rounded-[15px]' />  Speed Settings</div>

                      <div

                    onClick={() => dispatch(setCommonState('activeTab', 'insights'))}
                    className={cn(

                        `whitespace-nowrap border-r rounded-[20px] cursor-pointer w-[200px]  flex items-center gap-2 px-5 py-3 text-sm font-medium`,
                        diagnosticLoading && 'cursor-not-allowed opacity-90 pointer-events-none',
                        activeTab === 'insights' || activeTab === 'opportunities'  || activeTab === 'diagnostics' || activeTab === 'passed_audits' ? "font-medium bg-brand-0 dark:bg-brand-950" : "dark:hover:text-brand-300"
                    )}
                    data-tour="page-speed-insights"> <GaugeCircle className='w-4' />  Page Speed Insights </div>

                    <div

                    onClick={() => dispatch(setCommonState('activeTab', 'optimizations'))}
                    className={cn(

                        `whitespace-nowrap border-r rounded-[20px] cursor-pointer w-[160px]  flex items-center gap-2 px-5 py-3 text-sm font-medium`,
                        diagnosticLoading && 'cursor-not-allowed opacity-90 pointer-events-none',
                        activeTab === 'optimizations' ? "font-medium bg-brand-0 dark:bg-brand-950" : "dark:hover:text-brand-300"
                    )}
                    data-tour="optimizations"> <AIStarIcon cls='w-4 dark:text-brand-300' isDark={isDark} />  AI Diagnostic </div>


            </div>
            <div className={cn("flex")}>
  <div
    className={cn(
      "py-3 relative scale-y-[-1] ml-6"
    )}
  >      <SettingsLine width={getWidthForActiveTab(activeTab) || 220} flip={true}/>
                {/* <SettingsLine width={300} /> */}
            </div>
            </div>
           
            <div className="audits flex mb-24 dark:bg-brand-800 bg-brand-200 rounded-3xl">
                <div className='w-full flex flex-col'>

                    <AnimatePresence initial={false}>
                        <div key='performance' className='grid grid-cols-12 gap-6 w-full relative '>
                            <div className='col-span-12 flex flex-col gap-2'>
                                {activeTab === 'configurations' ?
                                    <>
                                        {/*<SetupChecklist/>*/}
                                        <SpeedSettings />
                                    </>
                                    :
                                    activeTab === 'optimizations' ?
                                    <Optimizations />
                                    :  <>
                                    <PageSpeedInsights />
                                    <div className=" px-4 py-4 pt-0"><AuditList activeTab={activeTab} /></div>
                                    </>

                                }
                            </div>
                        </div>
                        <div key='audit-blank'>
                            {(activeTab !== 'configurations' && activeTab!== 'optimizations' && activeTab !== 'insights' && (!data?.grouped[activeTab] || data?.grouped[activeTab].length <= 0)) && (
                                <m.div
                                    initial={{ opacity: 0, y: 10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, y: -20 }}
                                    className='flex flex-col gap-8 items-center px-8 pt-40 pb-20 w-full'>

                                    <div>
                                        <img alt='Good Job!' className='w-64'
                                            src={options?.page_optimizer_base ? (options?.page_optimizer_base + `/success.svg`) : '/success.svg'} />
                                    </div>

                                    <span className='flex gap-2'>
                                        Brilliantly done! It's clear you've mastered this.
                                    </span>
                                </m.div>

                            )}
                        </div>

                        {/*{reanalyze &&*/}
                        {/*    <m.div*/}
                        {/*        key='loading-notification'*/}
                        {/*        initial={{opacity: 0, y: 10}}*/}
                        {/*        animate={{opacity: 1, y: 0}}*/}
                        {/*        exit={{opacity: 0, y: -10}}*/}
                        {/*        className='dark:bg-brand-800/40 bg-brand-200 px-8 py-5 rounded-3xl'>*/}

                        {/*        <div className='flex items-center gap-3'>*/}
                        {/*            <Loader className='w-5 animate-spin text-brand-700'/>*/}
                        {/*            <div className='text-sm text-brand-700'>*/}
                        {/*                Analyzing your page with Google Page Speed Insights...*/}
                        {/*            </div>*/}
                        {/*        </div>*/}

                        {/*    </m.div>*/}
                        {/*}*/}
                        <div>

                        </div>
                    </AnimatePresence>
                </div>
            </div>

        </div>

    )

}

export default Performance