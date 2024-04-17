import {useSelector} from "react-redux";
import {optimizerData} from "../../../store/app/appSelector";
import React, {ReactNode, useCallback, useEffect, useMemo, useState, useRef} from "react";
import {CheckCircle2, Circle} from "lucide-react";
import Audit from "app/page-optimizer/components/audit/Audit";
import {
    CloudDelivery,
    CSSDelivery,
    FontDelivery,
    ImageDeliverySVG,
    JavascriptDelivery,
    PageCache,
    AuditsLine,
    SettingsLine,
    PageCacheDuotone,
    CloudDeliveryDuotone,
    ImageDeliverySVGDuotone,
    JavascriptDeliveryDuotone,
    FontDeliveryDuotone, CSSDeliveryDuotone,
    Starter, Accelerate, TurboMax

} from "app/page-optimizer/components/icons/icon-svg";
import BetaSpeedSetting from "app/page-optimizer/components/audit/BetaSpeedSetting";
import {cn} from "lib/utils";
import {setCommonState} from "../../../store/common/commonActions";
import useCommonDispatch from "hooks/useCommonDispatch";
import {BoltIcon, CheckCircleIcon, ChevronRightIcon, ChevronDownIcon,  ChevronUpIcon } from "@heroicons/react/24/solid";
import {updateSettings} from "../../../store/app/appActions";
import PerformanceIcons from "app/page-optimizer/components/performance-widgets/PerformanceIcons";
import { m, AnimatePresence  } from 'framer-motion';
import AuditSettingsItem from './AuditSettingsItem';
import {useAppContext} from "../../../context/app"; // Import the new component

const capitalizeCategory = (category: string) => {
    if (category === 'css' || category === 'cdn') {
        return category.toUpperCase();
    } else {
        return category.charAt(0).toUpperCase() + category.slice(1);
    }
};

type GroupedSettings = Record<string, AuditSetting[]>;

const SpeedSettings = ({}) => {

    const {settings, data, activeReport} = useSelector(optimizerData);
    const [activeCategory,  setActiveCategory]= useState<SettingsCategory>('css')
    const [groupedSettings, setGroupedSettings] = useState<GroupedSettings>({});
    const {dispatch, openCategory, activeTab} = useCommonDispatch()
    const categoryOrder: SettingsCategory[] = [ 'css', 'javascript', 'image', 'font', 'cdn', 'cache'];
    const [sortedStatus, setSortedStatus] = useState(true)
    const modes = ['starter', 'accelerate', 'turboMax'];

    let savedSettingsMode = localStorage.getItem('settingsMode') as settingsMode;
    if (!savedSettingsMode || !modes.includes(savedSettingsMode)) {
        savedSettingsMode = 'starter';
    }

    const [settingsMode, setSettingsMode] = React.useState(savedSettingsMode);

    useEffect(() => {
        localStorage.setItem('settingsMode', settingsMode);
    }, [settingsMode]);


    const icons :  {
        [key in SettingsCategory]: React.ReactElement;
    } = useMemo(() => ( {
        cache : <PageCache/>,
        cdn : <CloudDelivery/>,
        image : <ImageDeliverySVG/>,
        javascript : <JavascriptDelivery/>,
        js : <JavascriptDelivery/>,
        font : <FontDelivery/>,
        css : <CSSDelivery/>,
    }), [])

    const iconsDuotone:  {
        [key in SettingsCategory]: React.ReactElement;
    } = useMemo(() => ( {
        cache : <PageCacheDuotone/>,
        cdn : <CloudDeliveryDuotone/>,
        image : <ImageDeliverySVGDuotone/>,
        javascript : <JavascriptDeliveryDuotone/>,
        js : <JavascriptDeliveryDuotone/>,
        font : <FontDeliveryDuotone/>,
        css : <CSSDeliveryDuotone/>,
    }), [])

    const groupByCategory = (settings: AuditSetting[]) => {

        const grouped = {} as GroupedSettings;
        settings.forEach((setting) => {
            if (!grouped[setting.category]) {
                grouped[setting.category] = [];
            }
            grouped[setting.category].push({
                ...setting,
                // audits: data.audits.filter(audit => audit.settings.find(s => s.name === setting.name))
                audits: (data?.audits || []).filter(audit => audit.settings.find(s => s.name === setting.name))

            });
        });
        return grouped;
    };
    // useEffect(() => {
    //     console.log(settingsMode)
    // },[settingsMode]);

    useEffect(() => {
     //   console.log(settings)

        const grouped = groupByCategory(settings || []);
        const sortedCategories = Object.keys(grouped).sort((a, b) => {
            const indexA = categoryOrder.indexOf(a as SettingsCategory);
            const indexB = categoryOrder.indexOf(b as SettingsCategory);
            return indexA - indexB;
        });

        const sortedGroupedSettings: GroupedSettings = {};
        sortedCategories.forEach((category) => {
            sortedGroupedSettings[category] = grouped[category as keyof typeof grouped];
            //sortedGroupedSettings[category] = grouped[category];
        });

        setGroupedSettings(sortedGroupedSettings);
       // setGroupedSettings(prevState => ({ ...prevState, ...sortedGroupedSettings }));

        if (openCategory) {
            setActiveCategory(openCategory);
          // dispatch(setCommonState('openCategory', ''));
        }else{
            dispatch(setCommonState('openCategory', 'css'));
        }

    }, [data, settings]);


    useEffect(() => {
        setSortedStatus(true);
    }, [activeReport]);

    const updateValue = useCallback( (setting: AuditSetting, value: any, key: string) => {
        dispatch(updateSettings(
            setting.audits[0],
            setting,
            key,
            value
        ));
    }, [dispatch]);

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

    const [passedAudits, setPassedAudits] = useState<AuditSetting[]>([]);
    const [notPassedAudits, setNotPassedAudits] = useState<AuditSetting[]>([]);
    const isInitialRender = useRef(true);


    useEffect(() => {
     //   console.log("mode: ", settingsMode);
    }, [groupedSettings]);



    useEffect(() => {

        if (isInitialRender.current) {
            isInitialRender.current = false;
            return;
        }

        if (groupedSettings && sortedStatus ) {

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
       // console.log('Not Passed Audits', notPassedAudits)

    }, [ groupedSettings]);



    const settingsModeOnChange = (mode: string) => {
        if (!notPassedAudits) {
            return;
        }

        const starterLabels = ['Remove Unused CSS', 'Enable Critical CSS', 'Minify CSS', 'Minify Javascript', 'Page Cache', 'Self Host Google Fonts'];
        const accelerateLabels = [...starterLabels, 'RapidLoad CDN', 'Serve next-gen Images', 'Lazy Load Iframes', 'Lazy Load Images', 'Exclude LCP image from Lazy Load', 'Add Width and Height Attributes', 'Defer Javascript'];
        const turboMaxLabels = [...accelerateLabels, 'Delay Javascript'];

        notPassedAudits.forEach(settings => {
            const [mainInput] = settings.inputs

            let settingsToReturn;

            if (mode === 'starter' && starterLabels.includes(mainInput.control_label)) {
                settingsToReturn = settings;
            } else if (mode === 'accelerate' && accelerateLabels.includes(mainInput.control_label)) {
                settingsToReturn = settings;
            } else if (mode === 'turboMax' && turboMaxLabels.includes(mainInput.control_label)) {
                settingsToReturn = settings;

            }
            if (settingsToReturn) {
                updateValue(settingsToReturn, true, mainInput.key);
            }else{
                updateValue(settings, false, mainInput.key);
            }

         //   console.log(mode , ' : ', settingsToReturn)
          //  updateValue(settings, true, mainInput.key);

        });
      //  console.log(notPassedAudits)

    };

    useEffect(() => {

        const starterLabels = ['Remove Unused CSS', 'Enable Critical CSS', 'Minify CSS', 'Minify Javascript', 'Page Cache', 'Self Host Google Fonts'];
        const accelerateLabels = [...starterLabels, 'RapidLoad CDN', 'Serve next-gen Images', 'Lazy Load Iframes', 'Lazy Load Images', 'Exclude LCP image from Lazy Load', 'Add Width and Height Attributes', 'Defer Javascript'];
        const turboMaxLabels = [...accelerateLabels, 'Delay Javascript'];

        const trueControlLabels: any[] = [];

            notPassedAudits.forEach(settings => {
                const [mainInput] = settings.inputs

                if (mainInput.value) {
                    trueControlLabels.push(mainInput.control_label);
                }

            });

        if (trueControlLabels.every(label => starterLabels.includes(label))) {
            setSettingsMode('starter')
        } else if (trueControlLabels.every(label => accelerateLabels.includes(label))) {
            setSettingsMode('accelerate')
        } else if (trueControlLabels.every(label => turboMaxLabels.includes(label))) {
            setSettingsMode('turboMax')
        } else {
            setSettingsMode('custom')
        }
    })

    const actionRequired = (item: AuditSetting): boolean => {
        const hasPassedAudit = item.inputs[0].value && item.audits.some((a) => a.type === 'passed_audit');
        const hasFailedAudit = item.audits.some((a) => a.type !== 'passed_audit');
        return hasPassedAudit || hasFailedAudit ;
    };

    const [categoryStates, setCategoryStates] = useState<Record<string, boolean>>({});
    const [passedAuditsCollapsStatus, setPassedAuditsCollapsStatus] = useState(false);
    const {options} = useAppContext()

    useEffect(() => {

        if (passedAuditsCollapsStatus){
            const initialCategoryStates: Record<string, boolean> = {};
            Object.keys(groupedSettings).forEach((category) => {
                initialCategoryStates[category] = false;
            });
            setCategoryStates(initialCategoryStates);
            setPassedAuditsCollapsStatus(false);
        }



    }, [groupedSettings]);

    const setShowHideState = (category: string) => {
        setCategoryStates((prevStates) => ({
            ...prevStates,
            [category]: !prevStates[category],
        }));
    };
    const filteredAudits = passedAudits.filter(
        (item) => item.category === activeCategory
    );

    return <div className='dark:bg-brand-800/40 bg-brand-200 px-4 pt-4 pb-2 mt-2 rounded-3xl'>
        <SettingsLine width={getWidthForCategory(activeCategory)|| 220} category={activeCategory}  />
        <div className="py-4">
            <h3 className="font-medium">Performance Gears</h3>
            <span className="font-normal text-sm">Select your Performance Mode: Starter, Accelerate, TurboMax, or Customize, to fine-tune your site's speed.</span>
        </div>

        <div className="flex gap-4">
            {modes.map((mode, index) => (
                <div
                    key={index}
                    className={`flex px-4 py-4 min-w-[156px] min-h-[156px] items-center justify-center w-fit rounded-2xl dark:bg-brand-950 bg-brand-0 dark:hover:border-brand-700/70 hover:border-purple-700 border border-brand-200 border-[3px]  ${mode === settingsMode ? ' border-purple-700' : ''}`}
                    onClick={e => {
                        setSettingsMode(mode as settingsMode);
                        dispatch(setCommonState('settingsMode', mode));
                        settingsModeOnChange(mode);
                    }}
                >
                    <div className="flex flex-col gap-1 items-center text-center">
                        {mode === 'starter' && <Starter cls={'px-2 py-2'} />}
                        {mode === 'accelerate' && <Accelerate cls={'px-2 py-2'} />}
                        {mode === 'turboMax' && <TurboMax cls={'px-2 py-2'} />}
                        <div>
                            <p className="font-medium">{mode.charAt(0).toUpperCase() + mode.slice(1)}</p>
                            {mode === 'turboMax' && <p className="font-normal text-[10px] leading-none">Test Mode Recommended</p>}
                        </div>
                    </div>
                </div>
            ))}
        </div>

        <div className="py-4">
            <h3 className="font-medium">Recommended Settings</h3>
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
                        activeCategory === category ? 'dark:bg-brand-950 shadow-md transition-all' : '' && ''
                    )}>
                        <div>
                            {activeCategory === category ?  <>{icons[category]}</> : <>{iconsDuotone[category]}</>}
                        </div>
                        <span className='font-medium tracking-wide'>
                        {capitalizeCategory(category)}
                        </span>

                    </m.div>

                </li>
            ))}


        </ul>

        <div>
            <ul>

                {notPassedAudits.map((item: AuditSetting, itemIndex) => (
                    <li key={itemIndex}>{ item.category === activeCategory && (
                        <m.div initial={{ opacity: 0}}
                               animate={{ opacity: 1}}
                               transition={{ duration: 0.3 }}
                        >
                        <AuditSettingsItem key={`${activeCategory}-${itemIndex}`} item={item} itemIndex={itemIndex} updateValue={updateValue} actionRequired={true}/>
                        </m.div>
                        )}</li>
                ))}
            </ul>

            <ul>


                {filteredAudits.length > 0 && (
                    <m.button
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        key={activeCategory}
                        onClick={() => setShowHideState(activeCategory)}
                        className={cn(
                            `select-non w-full transition-all border-2 border-transparent rounded-[20px] cursor-pointer  
          flex items-center gap-2 px-5 py-1.5 pb-2 text-sm font-medium `,
                            notPassedAudits.some(item => item.category === activeCategory) ? "" : "ml-10"
                        )}
                    >
                        Show Additional Settings{" "} <ChevronDownIcon className={cn(
                            'w-4 rounded-[15px] transition-transform',
                        categoryStates[activeCategory] && '-rotate-180'
                    )} />

                    </m.button>
                )}

                { (categoryStates[activeCategory]) && (
                    <>
                    <div className={cn('font-normal text-sm ml-0.5 -mt-2 mb-3 px-5',
                        notPassedAudits.some(item => item.category === activeCategory) ? "" : "ml-[42px]"
                    )}>The audits associated with these settings are already optimized</div>

                    {passedAudits.map((item: AuditSetting, itemIndex) => (

                    <li key={itemIndex}>{ item.category === activeCategory && (
                        <m.div initial={{ opacity: 0}}
                               animate={{ opacity: 1}}
                               transition={{ duration: 0.3 }}
                        >

                        <AuditSettingsItem key={`${activeCategory}-${itemIndex}`} item={item} itemIndex={itemIndex} updateValue={updateValue} actionRequired={false} />
                        </m.div>
                        )}
                    </li>

                    ))}
                    </>
                )}

                {(filteredAudits.length > 0 && !notPassedAudits.some(item => item.category === activeCategory)) &&
                    <m.div
                        initial={{opacity: 0, y: 10}}
                        animate={{opacity: 1, y: 0}}
                        exit={{opacity: 0, y: -20}}
                        className='flex flex-col gap-2 items-center px-2 pt-2 w-full mb-6'>
                        <div>
                            <img alt='Good Job!' className='w-64' src={ options?.page_optimizer_base ? (options?.page_optimizer_base + `/success.svg`) : '/success.svg'}/>
                        </div>
                        <span className='flex gap-2'>Brilliantly done! It's clear you've mastered this.</span>
                    </m.div>
                }
            </ul>
        </div>
    </div>
}

export default SpeedSettings