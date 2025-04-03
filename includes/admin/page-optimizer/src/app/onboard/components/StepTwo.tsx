import React, { useMemo, useCallback, useState, useEffect } from 'react';
import { cn, isDev } from "lib/utils";
import { useAppContext } from "../../../context/app";
import { CheckCircleIcon, InformationCircleIcon, ArrowLongRightIcon, ChevronLeftIcon } from "@heroicons/react/24/solid";
import { changeGear, fetchSettings, updateLicense } from "../../../store/app/appActions";
import useCommonDispatch from "hooks/useCommonDispatch";
import { useSelector } from "react-redux";
import { optimizerData } from "../../../store/app/appSelector";
import useSubmitSettings from "hooks/useSubmitSettings";
import { useTestModeUtils } from "hooks/testModeUtils";
import { Starter, Accelerate, TurboMax } from "app/page-optimizer/components/icons/gear-icons";
import { CustomCheckIcon } from "app/dashboard/components/icons/icon-svg";
import { AIButtonIcon } from "app/onboard/components/icons/icon-svg";
import { Input } from "components/ui/input";
import { AnimatePresence, motion } from 'framer-motion';
import { Loader } from "lucide-react";
import ComparisonDialog from "app/dashboard/components/ComparisonDialog";
import { CircularProgressbarWithChildren } from "react-circular-progressbar";
import LettersPullUp from "components/ui/letterUpAnimation";
import ApiService from '../../../services/api';
import { setCommonRootState } from '../../../store/common/commonActions';

// type PerformanceGear = 'starter' | 'accelerate' | 'turboMax';

const boosterLevels: PerformanceGear[] = ['starter', 'accelerate', 'turboMax'];

type InputChangeHandler = React.ChangeEventHandler<HTMLInputElement>;

const GEAR_FEATURES: Record<PerformanceGear, string[]> = {
    turboMax: [
        "Unused CSS generation",
        "Minifying CSS",
        "Minifying JavaScript",
        "Page Cache Generated",
        "Google Fonts self-hosted",
        "Files served through CDN",
        "Images served in Next-Gen",
        "Images Lazy-loaded",
        "JavaScript files Deferred",
        "Critical CSS files generated",
        "JavaScript files delayed",
    ],
    accelerate: [
        "Unused CSS generation",
        "Minifying CSS",
        "Minifying JavaScript",
        "Page Cache Generated",
        "Google Fonts self-hosted",
        "Files served through CDN",
        "Images served in Next-Gen format",
        "Images Lazy-loaded",
        "JavaScript files Deferred",
    ],
    starter: [
        "Unused CSS generation",
        "Minifying CSS",
        "Minifying JavaScript",
        "Page Cache Generated",
        "Google Fonts self-hosted",
    ],
    custom: [
        // "Unlock peak performance potential",
        // "Utilize Accelerator mode",
        // "Generate critical CSS for faster rendering",
        // "Implement advanced JavaScript handling methods",
        // "Delay execution for improved speed and efficiency",
    ],

};

interface StepTwoProps {
    reconnect?: boolean;
    onNext?: () => void;
}

const StepTwo: React.FC<StepTwoProps> = ({ reconnect, onNext }) => {
    const { options, uucssGlobal } = useAppContext();
    const { dispatch, headerUrl, settingsMode } = useCommonDispatch();
    const { activeGear, license, settings } = useSelector(optimizerData);
    const [activeLevel, setActiveLevel] = useState<PerformanceGear>('turboMax');
    const [inputLicense, setInputLicense] = useState("");
    const [showInput, setShowInput] = useState(false);
    const [licenseMessage, setLicenseMessage] = useState("");
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const LicenseInputChange: InputChangeHandler =
        (event) => {
            setInputLicense(event.target.value);
        };
    const connectRapidloadLicense = async () => {
        setLoading(true);
        const response = await dispatch(updateLicense(options, inputLicense));
        setLoading(false);
        if (response.success) {
            dispatch(updateLicense(options));
            dispatch(setCommonRootState('licenseConnected', true));
           
            if (onNext) {
                onNext();
            }

        } else {
            setLicenseMessage(response?.error ?? '');
        }
    };

    useEffect(() => {
        dispatch(fetchSettings(options, headerUrl ? headerUrl : options.optimizer_url, false));
    }, [dispatch]);

    useEffect(() => {
        setLicenseMessage("");
    }, [inputLicense]);

    useEffect(() => {
        dispatch(changeGear(
            activeLevel as BasePerformanceGear
        ))

    }, [activeLevel, activeGear]);


    const settingsModeOnChange = (mode: PerformanceGear) => {
        setActiveLevel(mode)
    };
    useEffect(() => {
        // console.log(activeGear)

    }, [activeGear]);



    const getIcon = useMemo(() => (level: PerformanceGear) => {
        const iconProps = {
            cls: `w-16 h-16 mt-[16px] ${activeLevel === level ? 'text-purple-600' : 'text-gray-400'}`
        };
        switch (level) {
            case 'starter': return <Starter {...iconProps} />;
            case 'accelerate': return <Accelerate {...iconProps} />;
            case 'turboMax': return <TurboMax {...iconProps} />;
        }
    }, [activeLevel]);

    const renderBoosterLevel = useCallback((level: PerformanceGear) => {
        const isActive = activeLevel === level;
        const isTurboMax = level === 'turboMax';
        return (
            <div
                key={level}
                className={cn(
                    'relative bg-brand-0 flex flex-col gap-3.5 font-normal w-[166px] h-[166px] cursor-pointer rounded-3xl items-center justify-center dark:bg-brand-800 ',
                    isActive ? 'text-brand-600 border-[3px] border-[#592d8d] dark:border-purple-700' : 'border border-transparent border-[3px] outline outline-1 outline-brand-200 dark:outline-brand-700',
                    isTurboMax && 'gap-1 pt-4',
                )}
                onClick={() => settingsModeOnChange(level)}
            >
                <div>
                    {getIcon(level)}
                    <div className={cn("absolute w-7 h-7 justify-center dark:bg-brand-800 dark:text-brand-300", level === "turboMax" ? "top-1 right-1.5 gap-1 text-[10px] items-center font-semibold rounded-3xl p-1 flex bg-brand-0 border border-brand-200 min-w-[150px]	" : "top-2.5 right-2.5", isTurboMax && 'dark:border-brand-600 dark:border')}>
                        {level === 'turboMax' && (<><AIButtonIcon />  AI Recommended </>)}
                        {isActive && <CheckCircleIcon className={`w-6 h-6 text-purple-800 `} />}
                    </div>
                </div>
                <div className="items-center flex flex-col">
                    <span className="capitalize font-semibold dark:text-brand-300">{level}</span>
                    {/* {level === 'turboMax' && (
                        <span className="font-normal text-[10px] leading-none">Test Mode Recommended</span>)} */}
                </div>
            </div>
        );
    }, [activeLevel, getIcon]);




    const GearFeatures: React.FC<{ gearName: PerformanceGear; features: string[] }> = ({ gearName, features }) => {
        // Split the features into groups of 4
        const chunkedFeatures = [];
        const length = gearName === 'turboMax' ? 4 : 3;
        for (let i = 0; i < features.length; i += length) {
            chunkedFeatures.push(features.slice(i, i + length));
        }

        return (
            <div className="relative">
                <h4 className="text-base font-semibold capitalize text-center">{gearName} Gear Performance Features</h4>
                <div className="flex space-x-4">
                    {chunkedFeatures.map((chunk, columnIndex) => (
                        <div key={columnIndex} className="flex-1">
                            <ul className="space-y-1 py-2 text-sm font-medium">
                                {chunk.map((feature, index) => (
                                    <li key={index} className="flex items-center gap-2 truncate">
                                        <CustomCheckIcon className="h-3 w-3 text-brand-600" />
                                        {/*<LettersPullUp text={feature} />*/}
                                        <span className="whitespace-nowrap">{feature}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            </div>
        );
    };


    const GearDisplay: React.FC<{ activeGear: PerformanceGear }> = ({ activeGear }) => {
        const features = GEAR_FEATURES[activeGear] || [];
        return <GearFeatures gearName={activeGear} features={features} />;
    };


    return (
        <div
            className="w-full flex flex-col gap-4 transition-all h-screen">
            <div className="flex flex-col gap-8 p-16 items-center rounded-3xl">
                <div className="px-2">
                    <img
                        className="w-22"
                        src={options?.page_optimizer_base ? `${options.page_optimizer_base}/logo.svg` : '/logo.svg'}
                        alt="RapidLoad - #1 to unlock breakneck page speed"
                    />
                </div>
                {!reconnect ? (
                    <>
                        <div className="flex flex-col gap-2 text-center">
                            <h1 className="text-4xl font-bold">Select a Performance Gear</h1>
                            <span className="font-medium text-base text-zinc-600 dark:text-brand-300">
                                Pick your Performance Mode: Starter, Accelerate or TurboMax to fine-tune your site's speed.
                            </span>
                        </div>

                        <div className="flex flex-col">
                            <div className="flex gap-3 w-full">
                                {boosterLevels.map(renderBoosterLevel)}
                            </div>
                        </div>

                        {/* <GearDisplay activeGear={activeLevel as PerformanceGear}/> */}
                        <button className="text-base font-semibold capitalize text-center underline text-brand-500"
                            onClick={() => setOpen(true)}
                        >Compare Performance Gears
                        </button>
                        <ComparisonDialog open={open} setOpen={setOpen} />
                        {/*<button onClick={onNext}>Next</button>*/}
                    </>
                ) : (
                    <div className="flex flex-col gap-2 text-center">
                        <h1 className="text-4xl font-bold">Connect Your Account 2</h1>
                        <span className="font-medium text-base text-zinc-600 dark:text-brand-300">
                            Pick your Performance Mode: Starter, Accelerate or TurboMax to fine-tune your site's speed.
                        </span>
                    </div>
                )}


                <div className="flex flex-col gap-6 items-center border-t-2 border-brand-200 pt-8 w-full max-w-[900px]">
                    <h6 className="text-base font-semibold capitalize text-center">Connect Your Account to
                        Optimize </h6>

                    <AnimatePresence mode="wait">
                        {showInput ? (
                            <motion.div
                                key="inputDiv"
                                initial={{ opacity: 0, y: -20 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, y: -20 }}
                                transition={{ duration: 0.2 }}
                                className="flex flex-col w-[500px] gap-4"
                            >
                                <div className='border-2 rounded-2xl px-2 py-1'>
                                    <Input
                                        id="licenseKey"
                                        type="text"
                                        placeholder={'Enter you license key'}
                                        className="text-sm flex-grow border-none focus:outline-none focus-visible:ring-0 dark:text-brand-300 focus-visible:ring-offset-0"
                                        value={inputLicense}
                                        onChange={LicenseInputChange}
                                    />
                                </div>


                                <div
                                    className="flex justify-between items-center pl-1">
                                    {licenseMessage.length > 0 ? (
                                        <h3 className="text-sm font-medium text-amber-700">{licenseMessage}</h3>
                                    ) : (
                                        <h3 className="text-sm font-medium"></h3>
                                    )}
                                    <div className='flex gap-2 '>
                                        <button
                                            className={cn('flex items-center bg-brand-200 text-brand-950 hover:shadow-[inset_0_0_0_2px_rgba(0,0,0,1)] font-medium py-2 px-4 rounded-lg hover:bg-transparent transition-all gap-1 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 dark:text-brand-300',
                                                loading && 'pointer-events-none cursor-default opacity-30')}
                                            onClick={() => setShowInput(false)}
                                        >
                                            <ChevronLeftIcon className="h-4 w-4 text-brand-60" /> Back
                                        </button>
                                        <button
                                            className={cn('flex items-center hover:bg-gradient-to-br hover:from-[rgba(94,92,92,0.55)]  hover:to-brand-900/90 bg-brand-900/90  text-white font-medium py-2 px-4 rounded-lg transition-all gap-1 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 dark:text-brand-300',
                                                loading && 'pointer-events-none cursor-default opacity-30')}
                                            onClick={connectRapidloadLicense}
                                        >
                                            {loading ? (
                                                <>
                                                    <Loader className='w-5 animate-spin' /> Connecting
                                                </>
                                            ) : (
                                                'Connect'
                                            )}
                                        </button>
                                    </div>

                                </div>

                            </motion.div>
                        ) : (
                            <motion.div
                                key="connectDiv"
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, y: 20 }}
                                transition={{ duration: 0.2 }}
                                className="flex flex-col gap-4 items-center"
                            >
                                <div className='flex items-center gap-4'>
                                    <button
                                        className="items-center bg-brand-200 text-brand-950 hover:shadow-[inset_0_0_0_2px_rgba(0,0,0,1)] font-medium py-2 px-4 rounded-lg hover:bg-transparent transition-all gap-2 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 dark:text-brand-300"
                                        onClick={() => {
                                            setShowInput(true)
                                            if (localStorage.getItem('rapidLoadGear')) {
                                                localStorage.removeItem('rapidLoadGear');
                                            }
                                        }}
                                    >
                                        Connect with License key
                                    </button>
                                    <span className="font-semibold">or</span>
                                    <button
                                        className="items-center hover:bg-gradient-to-br hover:from-[rgba(94,92,92,0.55)]  hover:to-brand-900/90 bg-brand-900/90  text-white font-medium py-2 px-4 rounded-lg transition-all gap-2 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 dark:text-brand-300"
                                        onClick={() => {
                                            localStorage.setItem('rapidLoadGear', activeLevel);

                                            if (uucssGlobal?.activation_url) {
                                                window.location.href = uucssGlobal?.activation_url;
                                            }
                                        }}
                                    >
                                        Connect Account
                                    </button>
                                </div>


                                <div onClick={async () => {
                                    try{
                                        const api = new ApiService(options);
                                        await api.post('rapidload_onboard_skipped');
                                       
                                    }catch(error){
                                        console.error(error)
                                    }
                                    onNext?.()
                                    }} className='text-sm text-brand-500 mt-2 cursor-pointer hover:underline'>
                                    Skip without connecting to RapidLoad AI
                                </div>

                            </motion.div>
                        )}




                    </AnimatePresence>



                </div>
            </div>
        </div>
    )
        ;
};

export default StepTwo;
