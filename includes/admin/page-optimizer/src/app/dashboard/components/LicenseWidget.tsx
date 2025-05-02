import React, { useState, useEffect } from 'react';
import Card from "components/ui/card";
import { useSelector } from "react-redux";
import { CheckBadgeIcon, EyeIcon, EyeSlashIcon, XCircleIcon } from "@heroicons/react/24/solid";
import { optimizerData } from "../../../store/app/appSelector";
import { Input } from "components/ui/input";
import { updateLicense } from "../../../store/app/appActions";
import { useAppContext } from "../../../context/app";
import useCommonDispatch from "hooks/useCommonDispatch";
import { AnimatePresence, motion } from 'framer-motion';
import { Loader, PlugIcon } from "lucide-react";
import { Button } from 'components/ui/button';
import AppButton from 'components/ui/app-button';
import { setCommonRootState } from '../../../store/common/commonActions';
import ApiService from "../../../services/api";

type InputChangeHandler = React.ChangeEventHandler<HTMLInputElement>;

const LicenseWidget = () => {
    const [isVisible, setIsVisible] = useState(true);
    const [licenseMessage, setLicenseMessage] = useState("");
    const { license } = useSelector(optimizerData);
    const { options, uucssGlobal } = useAppContext();

    const [licenseInfo, setLicenseInfo] = useState<License | null>(() => options.rapidload_license_data || null);
    const [inputLicense, setInputLicense] = useState("");
    const [showInput, setShowInput] = useState(false);
    const [loading, setLoading] = useState(false);

    const { dispatch } = useCommonDispatch();


    useEffect(() => setLicenseMessage(""), [inputLicense]);

    const handleLicenseInputChange: InputChangeHandler = (event) => {
        setInputLicense(event.target.value);
    };

    const connectRapidloadLicense = async () => {
        setLoading(true);
        const response = await dispatch(updateLicense(options, inputLicense));
        setLoading(false);

        if (response.success) {
            dispatch(updateLicense(options));
            dispatch(setCommonRootState('licenseConnected', true));
            localStorage.setItem('rapidLoadLicense', JSON.stringify(response.data));
        } else {
            setLicenseMessage(response.error || '');
            localStorage.removeItem('rapidLoadLicense');
        }
    };

    useEffect(() => {
        if (license) {
            setLicenseInfo(license);
        }
    }, [license]);

  

    const toggleVisibility = () => setIsVisible(!isVisible);

    const renderLicenseStatus = () => {
        const isActivated = !!licenseInfo;
        const Icon = isActivated ? CheckBadgeIcon : XCircleIcon;
        const textColor = isActivated ? "text-purple-900/90  dark:text-brand-300" : "text-purple-600  dark:text-brand-300";
        const bgColor = isActivated ? "text-purple-900/80 dark:text-brand-300" : "text-purple-600  dark:text-brand-300";
        const statusText = isActivated ? "Rapidload Activated" : "You are using RapidLoad AI FREE";

        return (
            <div className="flex gap-1 items-center">
                <Icon className={`h-4 w-4 ${bgColor}`} />
                <span className={`text-xs ${textColor}`}>{statusText}</span>
            </div>
        );
    };

    const licenseFields = [
        { label: 'Email', value: licenseInfo?.email },
        {
            label: 'Next Billing',
            value: licenseInfo?.plan === 'cancelled or not found' ? 'Expired' : licenseInfo?.next_billing
                ? new Date(licenseInfo.next_billing * 1000).toLocaleDateString()
                : ''
        },
        { label: 'Plan', value: licenseInfo?.plan },
        { label: 'Active Domain', value: licenseInfo?.licensedDomain ? licenseInfo?.licensedDomain : options.optimizer_url }
    ];

    const renderLicenseDetails = () => (
        <div className="relative space-y-3">
            {licenseFields.map(({ label, value }, index) => (
                <div
                    key={label}
                    className="flex gap-2 items-center text-sm"
                >
                    <span className="font-medium text-brand-950 dark:text-brand-300/80">{label}:</span>
                    <span className="text-brand-850 dark:text-brand-300">
                        {isVisible
                            ? value
                            : '•'.repeat((value || '').toString().length)}
                    </span>
                </div>
            ))}
        </div>
    );

    const renderLicenseInput = () => (
        <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            transition={{ duration: 0.2 }}
            className="relative pt-2"
        >
            {licenseMessage && (
                <h3 className="text-sm font-medium text-amber-700 absolute -top-4 right-0 dark:text-brand-300">{licenseMessage}</h3>
            )}
            <Input
                id="licenseKey"
                type="text"
                placeholder="Enter your license key"
                className="text-sm h-8 flex-grow focus:outline-none focus-visible:ring-0 dark:text-brand-300 focus-visible:ring-offset-0"
                value={inputLicense}
                onChange={handleLicenseInputChange}
            />

        </motion.div>
    );

    const deactivateLicense = async () => {
        setLoading(true);
        const response = await dispatch(updateLicense(options, {disconnect: true}));
            if (response.success) {
                dispatch(updateLicense(options));
                setLicenseInfo(null);
                dispatch(setCommonRootState('licenseConnected', false));
            }
        setLoading(false);
    };

    return (
        <AnimatePresence mode="wait">
            <div className="w-full flex flex-col gap-4">
                <Card data-tour="license-widget" className="border flex flex-col gap-4">

                    <div className="flex flex-col p-6 pb-0 gap-2">
                        <div className="text-lg font-bold">
                            {licenseInfo ? (
                                <span className="text-brand-400/50 dark:text-brand-300/80">Welcome back, <span className="text-brand-950 dark:text-brand-300">{licenseInfo?.name}</span></span>
                            ) : (
                                <span className="text-base font-semibold dark:text-brand-300">Connect your license</span>
                            )}
                        </div>
                        <div className="bg-purple-800/10 px-2.5 py-1.5 rounded-xl w-fit dark:bg-brand-800 dark:border-brand-600 dark:border">{renderLicenseStatus()}</div>
                    </div>

                    <div className="grid gap-4 px-8 text-sm relative">
                        
                        {/* !licenseInfo */}
                        {!licenseInfo ? (
                            <>
                               
                                <span>You're currently using just 30% of RapidLoad AI’s potential. While the free version speeds up your site, the real power unlocks at 100%—with advanced optimizations like CriticalCSS injection, real-time image compression, and a high-speed global CDN.</span>
                                {showInput && renderLicenseInput()}

                            </>
                        ) : renderLicenseDetails()}
                    </div>

                    
                        <div
                            className="px-6 py-6 text-sm font-semibold relative before:absolute before:left-0 before:right-0 before:top-0 before:h-[2px] before:bg-gradient-to-r before:from-white before:via-brand-200 before:to-white dark:before:bg-gradient-to-r dark:before:from-brand-800 dark:before:via-brand-900 dark:before:to-brand-800">
                            {/* licenseInfo */}
                            {licenseInfo ? (
                                <div className='flex gap-2 justify-end '>
                                    <button className="cursor-pointer text-brand-500 py-1.5" onClick={() => window.open('https://app.rapidload.io/', 'blank')}>View My Account</button>
                                    <button className="cursor-pointer bg-brand-100/90 text-brand-950 py-1.5 px-4 rounded-lg dark:text-brand-300 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 transition-all" onClick={() => window.open('https://app.rapidload.io/subscription', 'blank')}>Upgrade</button>
                                    <button className="cursor-pointer flex gap-2 items-center bg-brand-100/90 text-brand-950 py-1.5 px-4 rounded-lg dark:text-brand-300 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 transition-all" onClick={() => deactivateLicense()}>
                                        {loading && <Loader className='w-4 animate-spin' />} Deactivate
                                    </button>

                                </div>
                            ) : (
                                <div className='flex gap-4 justify-end '>
                                    <button
                                        className="cursor-pointer text-brand-500 py-1.5"
                                        onClick={() => setShowInput(!showInput)}
                                    >
                                        <motion.span
                                            key={showInput.toString()}
                                            initial={{ opacity: 0, y: 10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            exit={{ opacity: 0, y: -10 }}
                                            transition={{ duration: 0.3 }}
                                            className="inline-block"
                                        >
                                            {showInput ? "Cancel" : "Connect with License key"}
                                        </motion.span>
                                    </button>

                                    <AppButton
                                        className={`bg-[#09090b] flex gap-2 items-center dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 dark:text-brand-300 cursor-pointer px-4 rounded-lg ${showInput && inputLicense.length <= 0 ? "cursor-not-allowed pointer-events-none opacity-50" : ""}`}
                                        onClick={() => (showInput ? connectRapidloadLicense() : uucssGlobal?.activation_url ? window.location.href = uucssGlobal?.activation_url
                                            : window.open('https://rapidload.ai/', '_blank'))}
                                    >
                                        {loading ? <Loader className='w-4 animate-spin' /> : <PlugIcon className="w-4 h-4" />} Connect to Boost
                                    </AppButton>
                                </div>
                            )}
                        </div>
                   
                </Card>
            </div>
        </AnimatePresence>
    );
};

export default LicenseWidget;
