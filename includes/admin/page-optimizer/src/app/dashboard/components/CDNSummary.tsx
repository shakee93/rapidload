import React, {Suspense, useEffect, useState} from 'react';
import Card from "components/ui/card";
import {cn} from "lib/utils";
import PerformanceIcons from "app/page-optimizer/components/performance-widgets/PerformanceIcons";
import { CheckBadgeIcon, EyeIcon, EyeSlashIcon,InformationCircleIcon  } from "@heroicons/react/24/outline";
import {Switch} from "components/ui/switch";
import {Label} from "components/ui/label";
import {Skeleton} from "components/ui/skeleton"
import PerformanceProgressBar from "components/performance-progress-bar";
import {getSummary, saveGeneralSettings} from "../../../store/app/appActions";
import useCommonDispatch from "../../../hooks/useCommonDispatch";
import {useAppContext} from "../../../context/app";
import {useSelector} from "react-redux";
import {RootState} from "../../../store/app/appTypes";
import {optimizerData} from "../../../store/app/appSelector";
import { Tooltip } from 'components/ui/tooltip';
import TooltipText from 'components/ui/tooltip-text';

interface SectionHeaderProps {
    title: string;
    tooltip?: string;
}

interface UsageBarProps {
    label: string;
    usage?: number;
    allowedUsage?: number;
    note?: string;
    used_gb_formatted?: string;
    additional_usage_gb?: number;
}

const CDNSummary = ({className}: {className: string}) => {

    const {dispatch} = useCommonDispatch();
    const {options} = useAppContext();
    const {cdnUsage, imageUsage, cacheUsage, license} = useSelector(optimizerData);
    const [licenseInfo, setLicenseInfo] = useState<License | null>(null);
    const [nextBillingDate, setNextBillingDate] = useState<string>('');

    useEffect(() => {
        const storedLicense = localStorage.getItem('rapidLoadLicense');
        if (storedLicense) {
            try {
                setLicenseInfo(JSON.parse(storedLicense));
            } catch (error) {
                console.error("Error parsing license data", error);
            }
        } else if(license) {
            setLicenseInfo(license);
        }

        
        const currentDate = new Date();
        const nextMonth = new Date(currentDate);
        nextMonth.setMonth(currentDate.getMonth() + 1);

        if (licenseInfo?.next_billing) {
            const nextBilling = new Date(licenseInfo.next_billing * 1000);
            setNextBillingDate(nextMonth.toLocaleDateString('en-US', { month: 'short' }) + ' ' + nextBilling.getDate());
        }

    }, [license, licenseInfo?.next_billing]);

    const SectionHeader = ({title, tooltip}: SectionHeaderProps) => (
        <div className="flex gap-2 items-center pb-2">
            <div className="text-base font-semibold dark:text-brand-300">{title}</div>
        
            <TooltipText className='max-w-sm' text={tooltip}>
                <InformationCircleIcon className="h-[18px] w-[18px]"/>
            </TooltipText>
        </div>
    );


    // const UsageBar = ({label, usage = 0, allowedUsage = 0, note, used_gb_formatted, additional_usage_gb}: UsageBarProps) => {

    //     const progressWidth = allowedUsage ? `${(usage / allowedUsage) * 100}` : "0";
    
    //     return (
    //         <div className={cn("flex flex-col gap-2.5 py-2", className)}>
    
    //             <div className="flex items-center text-sm dark:text-brand-300 justify-between">
    //                 <div className="flex gap-2 items-baseline">
    //                     <span className={`font-semibold ${label === 'Additional Usage' && 'text-brand-400'}`}>{label}</span>
    //                     <span
    //                         className="text-brand-400 text-xs">{ usage? usage < 1 ? usage.toFixed(2) : usage : '0'} GB / {allowedUsage ? allowedUsage : 30} GB</span>
    //                 </div>
    //                 <div className="text-[10px] font-normal dark:text-brand-300 text-brand-400">
    //                     {note}
    //                 </div>
    //             </div>
                
    //             <div
    //                 className="relative w-full h-2.5 bg-brand-100 overflow-hidden rounded outline outline-1 outline-brand-200 dark:bg-brand-600/40 dark:outline-brand-600/40">
    //                 <div
    //                     className="absolute h-2.5 bg-brand-950 rounded dark:bg-brand-300"
    //                     style={{width: `${Number(progressWidth)}%`}}
    //                 ></div>
    //             </div>
    
    //         </div>
    //     );
    
    //     };

    const UsageBar = ({label, usage = 0, allowedUsage = 0, note, used_gb_formatted, additional_usage_gb = 0}: UsageBarProps) => {

        const totalUsage = usage;
        const allowedPercentage = allowedUsage ? Math.min((allowedUsage / totalUsage) * 100, 100) : 0;
        const additionalPercentage = totalUsage > allowedUsage ? Math.min(((totalUsage - allowedUsage) / totalUsage) * 100, 100) : 0;
    
        return (
            <div className={cn("flex flex-col gap-2.5 py-2", className)}>
    
                <div className="flex items-center text-sm dark:text-brand-300 justify-between">
                    <div className="flex gap-2 items-baseline">
                        <span className={`font-semibold ${label === 'Additional Usage' && 'text-brand-400'}`}>{label}</span>
                        <span className="text-brand-400 text-xs">
                            {usage ? (usage < 1 ? usage.toFixed(2) : usage) : '0'} GB / {allowedUsage ? allowedUsage : 30} GB
                        </span>
                    </div>
                    <div className="text-[10px] font-normal dark:text-brand-300 text-brand-400">
                        {note}
                    </div>
                </div>
                
                <div className="relative w-full h-2.5 bg-brand-100 overflow-hidden rounded outline outline-1 outline-brand-200 dark:bg-brand-600/40 dark:outline-brand-600/40">
                    {/* Allowed Usage Progress */}

                    <div
                        className={`absolute h-2.5 bg-brand-950 dark:bg-brand-300 rounded ${(additional_usage_gb ?? 0) > 0 ? 'rounded-r-none' : ''}`}
                        style={{ width: `${allowedPercentage}%` }}
                    ></div>
                    
                    {/* White Line at Additional Usage Start */}
                    {additional_usage_gb > 0 && (
                        <div
                            className="absolute h-2.5 bg-white"
                            style={{
                                width: '2px',
                                left: `${allowedPercentage}%`,
                                top: 0,
                                zIndex: 100,
                            }}
                        ></div>
                    )}

                    {/* Additional Usage Progress (Red) */}
                    
                    {additional_usage_gb > 0 && (
                        <>
                        <div
                            className="absolute h-2.5 bg-brand-950/70 dark:bg-brand-300/70 rounded-r right-0"
                            style={{ width: `${additionalPercentage}%` }}
                        ></div>
                        
                        <TooltipText className="absolute -top-10 left-36 whitespace-nowrap" text={`Additional Usage: ${additional_usage_gb} GB`}>
                            <span className="absolute w-full h-full "></span>
                        </TooltipText>
                        </>
                    )}
                    
                    
                </div>
                {additional_usage_gb > 0 && (
                <span className="text-brand-400 text-xs">
                            {additionalPercentage + 100}% of included limit
                        </span>
                )}
            </div>
        );
    };
    
   

    useEffect(() => {
        dispatch(getSummary(options, 'get_rapidload_cdn_usage'));
        dispatch(getSummary(options, 'get_rapidload_image_usage'));
      //  dispatch(getSummary(options, 'get_cache_file_size'));

    }, [dispatch]);

    // useEffect(() => {
    //     cdnUsage && console.log('CDN Summary', cdnUsage)
    //     imageUsage && console.log('Image Usage', imageUsage)
    //     cacheUsage && console.log('Cache Usage', cacheUsage)
    // }, [cdnUsage, imageUsage, cacheUsage]);

    return (
        <Card data-tour="license-widget" className="border flex flex-col">
            <div className="p-6">
                <SectionHeader title="CDN and Image Summary" tooltip="Comprehensive breakdown of CDN and Image CDN usage allocated through RapidLoad." />

                <UsageBar label="CDN" usage={cdnUsage.used_gb} allowedUsage={cdnUsage.allowed_gb} note={`Renews on ${nextBillingDate}`} used_gb_formatted={cdnUsage.used_gb_formatted} additional_usage_gb={cdnUsage.additional_usage_gb}/>
                {/* {cdnUsage.additional_usage_gb > 0 &&
                    <UsageBar label="Additional Usage" usage={cdnUsage.additional_usage_gb} />
                } */}

                {/* <UsageBar label="CDN" usage={50} allowedUsage={cdnUsage.allowed_gb} note={`Renews on ${nextBillingDate}`} used_gb_formatted={cdnUsage.used_gb_formatted} additional_usage_gb={20}/> */}

            </div>
            <div className="relative mt-4 mb-2 before:absolute before:left-0 before:right-0 before:top-0 before:h-[2px] before:bg-gradient-to-r before:from-white before:via-brand-200 before:to-white dark:before:bg-gradient-to-r dark:before:from-brand-800 dark:before:via-brand-900 dark:before:to-brand-800"/>
            <div
                className="p-6 ">
                <UsageBar label="Images" usage={imageUsage.used_gb} allowedUsage={imageUsage.allowed_gb} note={`Renews on ${nextBillingDate}`} used_gb_formatted={imageUsage.used_gb_formatted} additional_usage_gb={imageUsage.additional_usage_gb}/>
                {/* {imageUsage.additional_usage_gb > 0 &&
                    <UsageBar label="Additional Usage" usage={imageUsage.additional_usage_gb}/>
                } */}
            </div>

            <div className="flex justify-end p-6 pt-0 text-sm font-semibold">
                {/* <button className="cursor-pointer bg-brand-100/90 text-brand-950 py-1.5 px-4 rounded-lg">
                    Manage usage
                </button> */}
            </div>
        </Card>

    );
};

export default CDNSummary;
