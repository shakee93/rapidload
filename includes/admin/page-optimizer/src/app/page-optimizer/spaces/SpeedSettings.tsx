import { useSelector } from "react-redux";
import { optimizerData } from "../../../store/app/appSelector";
import React, { ReactNode, useCallback, useEffect, useMemo, useState, useRef, MouseEventHandler } from "react";
import {
    Starter, Accelerate, TurboMax, GearLoading
} from "app/page-optimizer/components/icons/gear-icons";

import {
    SettingsLine,
    SettingsStraightLine,
} from "app/page-optimizer/components/icons/line-icons";

import {
    PageCacheDuotone,
    CloudDeliveryDuotone,
    ImageDeliverySVGDuotone,
    JavascriptDeliveryDuotone,
    FontDeliveryDuotone, CSSDeliveryDuotone,
} from "app/page-optimizer/components/icons/duno-icons";

import {
    CloudDelivery,
    CSSDelivery,
    FontDelivery,
    ImageDeliverySVG,
    JavascriptDelivery,
    PageCache,
} from "app/page-optimizer/components/icons/category-icons";
import { cn, isDev } from "lib/utils";
import { setCommonState } from "../../../store/common/commonActions";
import useCommonDispatch from "hooks/useCommonDispatch";
import { BoltIcon, CheckCircleIcon, ChevronRightIcon, ChevronDownIcon, ChevronUpIcon, CheckIcon, XMarkIcon } from "@heroicons/react/24/solid";
import { changeGear, getCSSStatus, updateSettings } from "../../../store/app/appActions";
import { m, AnimatePresence } from 'framer-motion';
import AuditSettingsItem from './AuditSettingsItem';
import { useAppContext } from "../../../context/app";
import AppButton from "components/ui/app-button";
import UnsavedChanges from "app/page-optimizer/components/footer/unsaved-changes";
import { InformationCircleIcon } from "@heroicons/react/24/outline";
import { useToast } from "components/ui/use-toast";
import { RootState } from "../../../store/app/appTypes"; // Import the new component
import { useTestModeUtils } from 'hooks/testModeUtils';
import SaveChanges from "app/page-optimizer/components/footer/save-changes";
import useSubmitSettings from "hooks/useSubmitSettings";
import { Loader } from "lucide-react";
import { buildStyles, CircularProgressbarWithChildren } from "react-circular-progressbar";
import TooltipText from "components/ui/tooltip-text";
import { useRootContext } from "../../../context/root";
const capitalizeCategory = (category: string) => {
    if (category === 'css' || category === 'cdn') {
        return category.toUpperCase();
    } else {
        return category.charAt(0).toUpperCase() + category.slice(1);
    }
};

const getWidthForCategory = (category: SettingsCategory) => {
    switch (category) {
        case 'cdn':
            return 625;
        case 'font':
            return 515;
        case 'css':
            return 130;
        case 'javascript':
            return 255;
        case 'image':
            return 395;
        case 'cache':
            return 740;
        default:
            return 395;
    }
};

type GroupedSettings = Record<string, AuditSetting[]>;

const SpeedSettings = ({ }) => {

    const { settings, data,
        activeReport,
        settingsLoading,
        activeGear, revisions, loading } = useSelector(optimizerData);
    const [activeCategory, setActiveCategory] = useState<SettingsCategory>('css')
    const [groupedSettings, setGroupedSettings] = useState<GroupedSettings>({});
    const { dispatch, openCategory,
        activeTab, auditsReturn, testModeStatus, settingsMode } = useCommonDispatch()
    const categoryOrder: SettingsCategory[] = ['css', 'javascript', 'image', 'font', 'cdn', 'cache'];
    const [sortedStatus, setSortedStatus] = useState(true)
    const modes: PerformanceGear[] = ['starter', 'accelerate', 'turboMax'];

    const customUnsavedChanges = useRef<HTMLDivElement>(null);
    const { options, savingData, invalidatingCache } = useAppContext()
    const [tempMode, setTempMode] = useState<PerformanceGear>((options.rapidload_titan_gear as PerformanceGear) || 'custom');
    const [customMode, setCustomMode] = useState(false);
    const [baseMode, setBaseMode] = useState<PerformanceGear>((options.rapidload_titan_gear as PerformanceGear) || 'custom');

    const [mouseOnSettingsGear, setMouseOnSettingsGear] = useState('');
    const { toast } = useToast();
    const { testMode } = useSelector(optimizerData);
    const { handleTestModeSwitchChange } = useTestModeUtils();
    const { isDark } = useRootContext()

    const icons: {
        [key in SettingsCategory]: React.ReactElement;
    } = useMemo(() => ({
        cache: <PageCache />,
        cdn: <CloudDelivery />,
        image: <ImageDeliverySVG />,
        javascript: <JavascriptDelivery />,
        js: <JavascriptDelivery />,
        font: <FontDelivery />,
        css: <CSSDelivery />,
    }), [])

    const iconsDuotone: {
        [key in SettingsCategory]: React.ReactElement;
    } = useMemo(() => ({
        cache: <PageCacheDuotone />,
        cdn: <CloudDeliveryDuotone />,
        image: <ImageDeliverySVGDuotone />,
        javascript: <JavascriptDeliveryDuotone />,
        js: <JavascriptDeliveryDuotone />,
        font: <FontDeliveryDuotone />,
        css: <CSSDeliveryDuotone />,
    }), [])

    const groupByCategory = useCallback((settings: AuditSetting[]) => {

        const grouped = {} as GroupedSettings;

        const audits = [
            ...data?.grouped?.passed_audits || [],
            ...data?.grouped?.diagnostics || [],
            ...data?.grouped?.opportunities || [],
        ]

        settings.forEach((setting) => {
            if (!grouped[setting.category]) {
                grouped[setting.category] = [];
            }

            grouped[setting.category].push({
                ...setting,
                audits: (audits || []).filter(audit => audit.settings.find(s => s.name === setting.name))

            });
        });
        return grouped;
    }, [settings, data, activeReport]);

    useEffect(() => {

        const grouped = groupByCategory(settings || []);

        const sortedCategories = Object.keys(grouped).sort((a, b) => {
            const indexA = categoryOrder.indexOf(a as SettingsCategory);
            const indexB = categoryOrder.indexOf(b as SettingsCategory);
            return indexA - indexB;
        });

        const sortedGroupedSettings: GroupedSettings = {};
        sortedCategories.forEach((category) => {
            sortedGroupedSettings[category] = grouped[category as keyof typeof grouped];
        });


        setGroupedSettings(sortedGroupedSettings);

        if (openCategory) {
            setActiveCategory(openCategory);
        } else {
            dispatch(setCommonState('openCategory', 'css'));
        }

    }, [settings, data, activeReport]);

    const updateValue = useCallback((setting: AuditSetting, value: any, key: string) => {
        dispatch(updateSettings(
            setting.audits[0],
            setting,
            key,
            value
        ));
    }, []);


    const [passedAudits, setPassedAudits] = useState<AuditSetting[]>([]);
    const [notPassedAudits, setNotPassedAudits] = useState<AuditSetting[]>([]);
    const isInitialRender = useRef(true);
    const { submitSettings } = useSubmitSettings()

    useEffect(() => {

        if (isInitialRender.current) {
            isInitialRender.current = false;
            return;
        }

        if (groupedSettings && sortedStatus) {


            const allPassedAudits: any[] = [];
            const allNotPassedAudits: any[] = [];

            categoryOrder.forEach((category) => {
                if (groupedSettings[category]) {

                    const sorted = groupedSettings[category].slice().sort((a, b) => {
                        const aHasFailedAudit = a.audits.some((audit) => audit.type !== 'passed_audit');
                        const bHasFailedAudit = b.audits.some((audit) => audit.type !== 'passed_audit');

                        if (aHasFailedAudit && !bHasFailedAudit) return -1;
                        if (!aHasFailedAudit && bHasFailedAudit) return 1;

                        // If both have failed audits or both don't have failed audits, prioritize by input values.
                        const aValue = a.inputs[0].value;
                        const bValue = b.inputs[0].value;

                        if (aValue && !bValue) return -1;
                        if (!aValue && bValue) return 1;

                        return 0;
                    });

                    const passed = sorted.filter((item) => !actionRequired(item));
                    const notPassed = sorted.filter((item) => actionRequired(item));

                    allPassedAudits.push(...passed);
                    allNotPassedAudits.push(...notPassed);
                }
            });

            setPassedAudits(allPassedAudits);
            setNotPassedAudits(allNotPassedAudits);
            setSortedStatus(false);

        }

    }, [groupedSettings]);

    const settingsModeOnChange = (mode: PerformanceGear, activate?: boolean) => {
        handleTestModeSettingsChange(mode);
        setTempMode(mode);

        if (activeGear === 'custom' && !activate) {
            customUnsavedChanges.current?.click();
        } else {

            dispatch(changeGear(
                mode as BasePerformanceGear
            ))

            //  submitSettings(true);

            if (!notPassedAudits) {
                return;
            }
        }
    };

    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        console.log('activeGear: ',activeGear)
        if(activeGear !== 'custom' && activeGear !== baseMode ){
            setBaseMode(tempMode);
            submitSettings(true);
        }
        // submitSettings(true);
    }, [activeGear]);

    const handleTestModeSettingsChange = (gearSettingsMode: string,) => {
        let toastInstance: ReturnType<typeof toast> | undefined;

        if (gearSettingsMode === "turboMax" && !testMode) {
            toastInstance = toast({
                description: (
                    <>
                        <div className='flex font-semibold w-full gap-2 text-center items-center'>
                            <InformationCircleIcon className='w-5 text-orange-600' />
                            Do you want to turn on test mode?

                            <AppButton className="px-2" onClick={async e => {
                                if (toastInstance) {
                                    toastInstance.dismiss();
                                }
                                await handleTestModeSwitchChange(true)
                            }} variant='outline'>
                                <CheckIcon className="h-5 w-5 text-gray-500" />
                                Yes
                            </AppButton>
                            <AppButton className="px-2" onClick={e => {
                                // Dismiss the toast immediately
                                if (toastInstance) {
                                    toastInstance.dismiss();
                                }
                            }} variant='outline'>
                                <XMarkIcon className="h-5 w-5 text-gray-500" />
                                No
                            </AppButton>

                        </div>
                    </>
                ),
            }, 99999999);

        }
    }

    useEffect(() => {
        setSortedStatus(true);
    }, [activeReport]);

    useEffect(() => {

    }, [settings]);

    useEffect(() => {
        // if (revisions?.length == 0 && !activeGear) {
        //     dispatch(changeGear('accelerate'));
        // }
    }, [settings])


    const settingsDescriptions: { [key in PerformanceGear]: string } = {
        starter: "Optimizes foundational aspects for faster load speeds by removing unused CSS, minifying CSS and JavaScript, caching pages, and self-hosting Google Fonts.",
        accelerate: "Starter mode + RapidLoad CDN, serving next-gen images, and enhancing image handling with lazy loading, while also deferring JavaScript and adding crucial image attributes.",
        turboMax: "Unlock peak performance potential including Accelerator mode + generating critical CSS, advanced JavaScript handling methods, such as delaying execution for improved speed and efficiency.",
        custom: "Tailor your optimization strategy to your needs, combining features like Accelerator mode and advanced JavaScript handling for personalized performance."
    };

    const currentMode: PerformanceGear = (mouseOnSettingsGear || activeGear) as PerformanceGear;


    const actionRequired = (item: AuditSetting): boolean => {
        const hasPassedAudit = item.inputs[0].value && item.audits.some((a) => a.type === 'passed_audit');
        const hasFailedAudit = item.audits.some((a) => a.type !== 'passed_audit');
        return hasPassedAudit || hasFailedAudit;
    };

    const [categoryStates, setCategoryStates] = useState<Record<string, boolean>>({});
    const [passedAuditsCollapsStatus, setPassedAuditsCollapsStatus] = useState(false);


    useEffect(() => {

        if (passedAuditsCollapsStatus) {
            const initialCategoryStates: Record<string, boolean> = {};
            Object.keys(groupedSettings).forEach((category) => {
                initialCategoryStates[category] = false;
            });
            setCategoryStates(initialCategoryStates);
            setPassedAuditsCollapsStatus(false);
        }



    }, [groupedSettings]);

    useEffect(() => {
        if (auditsReturn) {
            setCustomMode(true);
            dispatch(setCommonState('auditsReturn', false));
        }
    }, [auditsReturn]);

    const setShowHideState = (category: string) => {
        setCategoryStates((prevStates) => ({
            ...prevStates,
            [category]: !prevStates[category],
        }));
    };
    const filteredAudits = passedAudits.filter(
        (item) => item.category === activeCategory
    );

    const [enableFlags, setEnableFlags] = useState({ cpcss: false, uucss: false, cpcssuucss: false });

    const updateEnableFlags = useCallback(() => {
        if (!settings) return;
        const cpcssEnabled = settings.some(item => item.inputs.find(input => input.key === 'uucss_enable_cpcss')?.value);
        const uucssEnabled = settings.some(item => item.inputs.find(input => input.key === 'uucss_enable_uucss')?.value);
        const cpcssuucssEnabled = settings.some(item => item.inputs.find(input => input.key === 'enable_uucss_on_cpcss')?.value);

        setEnableFlags({ cpcss: cpcssEnabled, uucss: uucssEnabled, cpcssuucss: !cpcssuucssEnabled });
    }, [settings]);

    useEffect(() => {
        updateEnableFlags();
    }, [settings, updateEnableFlags]);

    useEffect(() => {
        dispatch(setCommonState('uucssError', enableFlags.cpcss && enableFlags.uucss && enableFlags.cpcssuucss));
    }, [enableFlags, dispatch]);

    return <AnimatePresence>
        <m.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.2, delay: 0.05 }}
            className=''
        >
            <div className='border-b border-brand-300 px-11 py-4 dark:border-brand-600/40'>
                <div className="pb-4">
                    <h3 className="font-semibold text-lg">Performance Gears</h3>
                    <span className="font-normal text-sm text-zinc-600 dark:text-brand-300">Select your Performance Mode: Starter, Accelerate, TurboMax, or Customize, to fine-tune your site's speed.</span>
                </div>



                <div className={`flex gap-4 `} data-tour="settings-gear">
                    {modes.map((mode, index) => (
                        <TooltipText key={`tooltip-${mode}`} text={loading ? "Analyzing your site performance" : savingData || invalidatingCache ? "Please wait while applying optimizations" : null}>
                            <div
                                key={index}
                                className={`${(savingData || invalidatingCache || loading) && 'cursor-not-allowed opacity-90 pointer-events-none'} cursor-pointer transition-all flex px-4 py-4 min-w-[166px] min-h-[166px] items-center justify-center w-fit rounded-3xl dark:bg-brand-950 bg-brand-0 dark:hover:border-purple-700 dark:border-brand-700/70 hover:border-purple-700 border border-brand-200 border-[3px]  ${mode === (activeGear || tempMode) ? ' border-purple-700 dark:border-purple-700' : ''}`}
                                onClick={e => {
                                    settingsModeOnChange(mode);
                                }}
                                onMouseEnter={() => setMouseOnSettingsGear(mode)}
                                onMouseLeave={() => setMouseOnSettingsGear('')}
                            >

                                <div
                                    className={`flex flex-col gap-1 items-center text-center ${mode === 'turboMax' ? ' pt-1.5' : ''}`}>

                                    {['starter', 'accelerate', 'turboMax'].includes(mode) && activeGear === mode && (
                                        <div className="absolute ml-28 -mt-4">
                                            {(savingData || invalidatingCache) ? (
                                                // <Loader className='w-24 animate-spin'/>
                                                <></>
                                            ) : (
                                                <CheckCircleIcon className="w-6 h-6 text-purple-800" />
                                            )}

                                        </div>
                                    )}

                                    {activeGear === mode && (savingData || invalidatingCache) ? (
                                        <div className="w-16 h-16 border-4 border-t-transparent border-[#7E22CE] rounded-full animate-spin"></div>
                                    ) : (
                                        <>
                                            {mode === 'starter' && <Starter cls={'px-2 py-2'} isDark={isDark} />}
                                            {mode === 'accelerate' && <Accelerate cls={'px-2 py-2'} isDark={isDark} />}
                                            {mode === 'turboMax' && <TurboMax cls={'px-2 py-2'} isDark={isDark} />}
                                        </>
                                    )}


                                    <div>
                                        {activeGear === mode && (savingData || invalidatingCache) ? (
                                            <p className="font-semibold capitalize z-[110000] mt-2"

                                            >Applying...</p>
                                        ) : (
                                            <>
                                                <p className="font-semibold capitalize"
                                                >{mode}</p>
                                                {mode === 'turboMax' && (
                                                    <p className="font-normal text-[10px] leading-none">Test Mode Recommended</p>
                                                )}
                                            </>
                                        )}

                                    </div>

                                </div>
                            </div>
                        </TooltipText>
                    ))}
                </div>


                <UnsavedChanges
                    title='Modified Customize Settings changes'
                    description="Switching to Performance Modes will result in the loss of any customized settings."
                    action='Activate'
                    performanceGear={true}
                    cancel='Cancel'
                    onClick={() => {
                        settingsModeOnChange(tempMode, true);
                        setCustomMode(false);
                    }}>
                    <div ref={customUnsavedChanges}></div>
                </UnsavedChanges>


                <div className="py-4 ">
                    {mouseOnSettingsGear ? (
                        <h3 className="font-semibold dark:text-brand-300 capitalize">{mouseOnSettingsGear} {activeGear === mouseOnSettingsGear && 'Activated'}</h3>
                    ) : (
                        <h3 className="font-semibold dark:text-brand-300 capitalize">{activeGear ? activeGear : baseMode} Activated</h3>
                    )}
                    <span
                        className="font-normal text-sm text-zinc-600 dark:text-brand-300">{settingsDescriptions[currentMode ? currentMode : baseMode]}</span>
                </div>
            </div>


            <div className='px-11 py-8'>
                <div>
                    {settingsLoading ?
                        <div className='w-48 animate-pulse h-10 select-none transition-all rounded-2xl cursor-pointer
          flex items-center gap-2 px-4 py-2 -ml-1 text-sm font-medium dark:hover:border-purple-700 dark:border-brand-700/70 hover:border-purple-700 border border-brand-200 border-[3px] dark:hover:bg-brand-950 bg-brand-0 dark:bg-brand-950'>
                        </div> :
                        <div
                            onClick={() => {
                                setTempMode('custom');
                                setCustomMode(prevMode => !prevMode);
                            }}
                            onMouseEnter={() => setMouseOnSettingsGear('custom')}
                            onMouseLeave={() => setMouseOnSettingsGear('')}
                            className={cn(
                                `select-none w-fit transition-all rounded-2xl cursor-pointer  
          flex items-center gap-2 px-4 py-2 -ml-1 text-sm font-medium dark:hover:border-purple-700 dark:border-brand-700/70 hover:border-purple-700 border border-brand-200 border-[3px] dark:hover:bg-brand-950 bg-brand-0 dark:bg-brand-950 `,
                                activeGear ? activeGear === 'custom' : baseMode === 'custom' && 'border-purple-700',
                                (savingData || invalidatingCache || loading) && 'cursor-not-allowed opacity-90 pointer-events-none'
                            )}
                            data-tour="customize-settings"
                        >
                            {activeGear? activeGear === 'custom' : baseMode === 'custom' &&
                                <div className="">
                                    <CheckCircleIcon className="w-6 h-6 text-purple-800" />
                                </div>
                            }

                            Customize Settings <ChevronDownIcon className={cn(
                                'w-4 rounded-[15px] transition-transform',
                                customMode && '-rotate-180'
                            )} />

                        </div>
                    }


                </div>

                {customMode &&
                    <>
                        <div className="py-3 relative">
                            <SettingsLine width={getWidthForCategory(activeCategory) || 220} category={activeCategory} />

                        </div>

                        <ul className='flex gap-3 ml-12'>
                            {categoryOrder.map((category: SettingsCategory, index) => (
                                <li key={index} onClick={e => {
                                    // setSortedStatus(true);
                                    setActiveCategory(category);
                                    dispatch(setCommonState('openCategory', category));
                                }}>
                                    <m.div
                                        id={category}
                                        transition={{ duration: 0.5 }} className={cn(
                                            'cursor-pointer select-none flex gap-2 transition-all items-center border border-transparent py-[6px] pr-3 pl-[7px] rounded-2xl w-fit mb-4 hover:bg-brand-50' +
                                            ' dark:bg-brand-950/60 dark:hover:bg-brand-950 bg-brand-0 hover:shadow-md',
                                            activeCategory === category && 'dark:bg-brand-950 shadow-md transition-all'
                                        )}>
                                        <div>
                                            {activeCategory === category ? <>{icons[category]}</> : <>{iconsDuotone[category]}</>}
                                        </div>
                                        <span className='font-medium tracking-wide'>
                                            {capitalizeCategory(category)}
                                        </span>

                                    </m.div>
                                </li>
                            ))}
                        </ul>

                        <div className={cn(
                            data ? '' : ''
                        )}>
                            <ul>

                                {groupedSettings[activeCategory]?.map((item: AuditSetting, itemIndex) => (
                                    <li key={`${item.category}-${itemIndex}`}>
                                        <m.div initial={{ opacity: 0, y: -10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            transition={{ duration: 0.3, delay: itemIndex ? 0.05 * itemIndex : 0 }}
                                        >
                                            <AuditSettingsItem key={`${item.category}-${itemIndex}`} item={item}
                                                itemIndex={itemIndex} updateValue={updateValue}
                                                actionRequired={true} />
                                        </m.div>
                                    </li>
                                ))}

                                {/*{(groupedSettings[activeCategory]?.length <= 2) && <m.div*/}
                                {/*    initial={{opacity: 0, y: 10}}*/}
                                {/*    animate={{opacity: 1, y: 0}}*/}
                                {/*    exit={{opacity: 0, y: -20}}*/}
                                {/*    className='flex flex-col gap-2 items-center px-2 mt-12 w-full mb-6'>*/}
                                {/*    <div>*/}
                                {/*        <img alt='Good Job!' className='w-60 -ml-6'*/}
                                {/*             src={options?.page_optimizer_base ? (options?.page_optimizer_base + `/success.svg`) : '/success.svg'}/>*/}
                                {/*    </div>*/}
                                {/*    <span className='flex text-sm mt-4 gap-2'>You're so close to perfection! One more fix and it's flawless!"</span>*/}
                                {/*</m.div>*/}
                                {/*}*/}
                            </ul>
                        </div>
                        <div className="flex justify-end mt-6">
                            <SaveChanges />
                        </div>

                    </>
                }
            </div>
        </m.div>
    </AnimatePresence>
}

export default SpeedSettings