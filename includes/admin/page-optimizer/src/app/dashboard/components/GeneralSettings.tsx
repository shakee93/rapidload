import React, { useEffect, useState } from 'react';
import { Textarea } from 'components/ui/textarea';
import { Checkbox } from 'components/ui/checkbox';
import useCommonDispatch from 'hooks/useCommonDispatch';
import { saveGeneralSettings, updateGeneralSettings } from '../../../store/app/appActions';
import { useAppContext } from '../../../context/app';
import AppButton from "components/ui/app-button";
import { Select, SelectTrigger, SelectValue, SelectContent, SelectGroup, SelectLabel, SelectItem } from 'components/ui/select';
import {CheckCircleIcon, ChevronRightIcon, XCircleIcon} from "@heroicons/react/24/solid";
import Accordion from "components/accordion";
import {Label} from "components/ui/label";
import { Button } from 'components/ui/button';
import { useToast } from "components/ui/use-toast"
import { Loader } from 'lucide-react';
import { optimizerData } from '../../../store/app/appSelector';
import { useSelector } from 'react-redux';


interface GeneralSettingsProps {
    onClose: (open: boolean) => void;
}

interface QueueOption {
    value: string;
    label: string;
}

const JOB_OPTIONS: QueueOption[] = [
    { value: '1', label: '1 Job' },
    { value: '2', label: '2 Jobs' },
    { value: '3', label: '3 Jobs' },
];

const TIME_INTERVAL_OPTIONS: QueueOption[] = [
    { value: '60', label: '1 Minute' },
    { value: '300', label: '5 Minutes' },
    { value: '600', label: '10 Minutes' },
    { value: '1800', label: '30 Minutes' },
    { value: '3600', label: '1 Hour' },
];

const GeneralSettings: React.FC<GeneralSettingsProps> = ({ onClose }) => {
    const { dispatch } = useCommonDispatch();
    const { options, uucssGlobal } = useAppContext();
    const [settingsData, setSettingsData] = useState<GeneralSettings>((uucssGlobal as Required<typeof uucssGlobal>).active_modules.general.options);
    const [jobCount, setJobCount] = useState(() => {
        const savedJobs = settingsData.uucss_jobs_per_queue?.toString() || '1';
        return JOB_OPTIONS.find(opt => opt.value === savedJobs)?.value || JOB_OPTIONS[0].value;
    });
    const [timeInterval, setTimeInterval] = useState(() => {
        const savedInterval = settingsData.uucss_queue_interval?.toString() || '600';
        return TIME_INTERVAL_OPTIONS.find(opt => opt.value === savedInterval)?.value || TIME_INTERVAL_OPTIONS[2].value;
    });
    const { toast } = useToast();
    const [loading, setLoading] = useState(false);
    const { generalSettings } = useSelector(optimizerData);

    useEffect(() => {
        if (generalSettings) {
            const generalOptions = generalSettings;
            const hasChanges = (Object.keys(generalOptions) as Array<keyof GeneralSettings>)
                .some(key => generalOptions[key] !== settingsData[key]);
            
            if (hasChanges) {   
                setSettingsData(generalOptions);
            }
        }
       
    }, [uucssGlobal, generalSettings]);
    
  
    useEffect(() => {
        if (settingsData) {
            setJobCount(settingsData.uucss_jobs_per_queue?.toString() || '1');
            setTimeInterval(settingsData.uucss_queue_interval?.toString() || '600');
        }
        
    }, [settingsData]);

    const handleCheckboxChange = (key: keyof GeneralSettings) => {
        setSettingsData(prev => ({ ...prev, [key]: !prev[key] }));
    };

    const handleSaveSettings = async () => {
        setLoading(true);
        try {
            const updatedSettings = {
                ...settingsData,
                uucss_jobs_per_queue: parseInt(jobCount),
                uucss_queue_interval: parseInt(timeInterval)
            };
            // dispatch(updateGeneralSettings(updatedSettings));
            // console.log(generalSettings);
            const response = await dispatch(saveGeneralSettings(options, updatedSettings));
            
            if (response.success) {
                dispatch(updateGeneralSettings(updatedSettings));
                toast({
                    description: (
                        <div className='flex w-full gap-2 text-center'>
                            Settings saved successfully <CheckCircleIcon className='w-5 text-green-600' />
                        </div>
                    ),
                });
            } else {
                toast({
                    description: (
                        <div className='flex w-full gap-2 text-center'>
                            {response.error || "Failed to save settings. Please try again."} <XCircleIcon className='w-5 text-red-600' />
                        </div>
                    ),
                });
            }
        } catch (error) {
            toast({
                description: (
                    <div className='flex w-full gap-2 text-center'>
                        {typeof error === 'string' ? error : "Error on Saving general settings"}  <XCircleIcon className='w-5 text-red-600' />
                    </div>
                ),
            });
        } finally {
            setLoading(false);
        }
    };

    const renderCheckbox = (label: string, description: string, key: keyof GeneralSettings) => {
        const isChecked = typeof settingsData[key] === 'boolean' ? settingsData[key] : false;
        return (
            <div className="relative flex gap-2 font-medium text-base w-fit items-center py-1 dark:text-brand-300">
                <Checkbox
                    checked={isChecked}
                    onCheckedChange={() => handleCheckboxChange(key)}
                />
                <div className="flex flex-col">
                    <div className="select-none cursor-pointer">{label}</div>
                    <p className="text-sm font-normal select-none">{description}</p>
                </div>
            </div>
        );
    };



    
    const renderTextarea = (label: string, description: string, key: keyof GeneralSettings) => {
        const value = typeof settingsData[key] === 'string' ? settingsData[key] : '';
    
        return (
            <div className="grid items-center my-4">
                <div className="text-sm font-semibold dark:text-brand-300">{label}</div>
                <div className="text-xs font-normal dark:text-brand-300 text-brand-500 mt-1">
                    {description}
                </div>
                <Textarea
                    className="mt-1 focus:outline-none focus-visible:ring-0 dark:text-brand-300 rounded-2xl"
                    value={value.replace(/,/g, '\n')}  
                    onChange={(e) =>
                        setSettingsData({
                            ...settingsData,
                            [key]: e.target.value.replace(/\n/g, ',') // Replace newlines with commas when saving
                        })
                    }
                />
            </div>
        );
    };
    
    


    const [isOpen, setIsOpen] = useState(false);
    const toggleIsOpen = () => {
        setIsOpen(prevState => !prevState);
    }

    return (
        <>
            <div className="content flex w-full sm:w-1/2 lg:w-full flex-col px-6">

                {renderCheckbox('Minify HTML', 'Minify the HTML output of your pages.', 'rapidload_minify_html')}
                {renderCheckbox('Query String', 'Identify URLs with query strings as separate URLs.', 'uucss_query_string')}
                {renderCheckbox('Preload Links', 'Preload internal links for faster navigation.', 'preload_internal_links')}
                {renderCheckbox('Debug Mode', 'Enable debug logs for RapidLoad.', 'uucss_enable_debug')}

                
                <div className="flex flex-col text-left dark:bg-brand-800/40 w-full dark:text-brand-300 bg-brand-100/30 rounded-xl py-4 px-4 border border-brand-200/60 my-2">
                    <div className="flex items-center justify-between cursor-pointer " onClick={toggleIsOpen} >
                        <div className="flex flex-col">
                            <span>
                            Queue Options
                            </span>
                            <span className="text-sm font-normal text-gray-600 sm:max-w-[425px] dark:text-brand-300">
                            More advanced options for pro users.
                        </span>
                        </div>
                        <ChevronRightIcon  className={`h-5 transition-all ${isOpen && 'rotate-[90deg]'}`} />
                    </div>

                    <Accordion
                        className="flex flex-col text-left w-full gap-1 mt-6 ml-3"
                        initialRender={true}
                        isOpen={isOpen}
                    >

                        {/* Queue Dropdowns */}
                        <div className="flex items-center space-x-4 mb-1">
                            <div className="flex items-center gap-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-brand-300">Run</label>
                                <Select value={jobCount} onValueChange={(v) => setJobCount(v)}>
                                    <SelectTrigger className="w-[130px] capitalize bg-brand-0 dark:text-brand-300 dark:bg-brand-950">
                                        <SelectValue placeholder="1 Job" />
                                    </SelectTrigger>
                                    
                                    <SelectContent className="z-[100001]">
                                        <SelectGroup>
                                            <SelectLabel>Jobs</SelectLabel>
                                            
                                            {JOB_OPTIONS.map((option, index) => (
                                                <SelectItem
                                                    className="capitalize cursor-pointer"
                                                    key={index}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-center gap-2">
                                <label className="text-sm font-medium text-gray-700 dark:text-brand-300">Per</label>
                                <Select value={timeInterval} onValueChange={(v) => setTimeInterval(v)}>
                                    <SelectTrigger className="w-[130px] capitalize bg-brand-0 dark:text-brand-300 dark:bg-brand-950">
                                        <SelectValue placeholder="10 Minutes" />
                                    </SelectTrigger>
                                    <SelectContent className="z-[100001]">
                                        <SelectGroup>
                                            <SelectLabel>Time Interval</SelectLabel>
                                            {TIME_INTERVAL_OPTIONS.map((option, index) => (
                                                <SelectItem
                                                    className="capitalize cursor-pointer"
                                                    key={index}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        {renderCheckbox('Disable Auto Queue', 'Disable jobs adding to queue on user visits.', 'uucss_disable_add_to_queue')}
                        {renderCheckbox('Disable Re-Queue', 'Disable jobs re-queuing on warnings.', 'uucss_disable_add_to_re_queue')}
                    </Accordion>


                </div>

                <div className="text-left flex w-fit gap-2 mt-2">
                    <Button  
                        className='flex gap-2 dark:bg-brand-800/40 dark:text-brand-300 dark:hover:bg-brand-800/50' 
                        onClick={() => {
                            window.open('/wp-admin/options-general.php?page=uucss_legacy&uucss_jobs', '_blank');
                        }}
                        variant='outline'>
                        CSS Job Optimizations Table
                    </Button>
                    <Button  
                        className='flex gap-2 dark:bg-brand-800/40 dark:text-brand-300 dark:hover:bg-brand-800/50' 
                        onClick={() => {
                            window.open('/wp-admin/admin.php?page=rapidload-legacy-dashboard#/', '_blank');
                        }}
                        variant='outline'>
                        Dashboard 2.0
                    </Button>
                </div>

                {renderTextarea('Exclude URLs', 'URLs that need to be excluded from RapidLoad optimization.', 'uucss_excluded_links')}

                
            </div>
            <div className="border-t flex justify-end mt-4 px-4 pt-4 gap-2">
                <AppButton
                    onClick={handleSaveSettings}
                    className="text-sm font-semibold text-white py-1.5 px-4 rounded-lg bg-primary hover:bg-primary/90 dark:text-brand-950"
                >
                    {loading && <Loader className='w-4 animate-spin '/> } Save Changes
                </AppButton>
                <AppButton onClick={() => onClose(false)}
                           className='mr-2 text-sm text-gray-500 bg-brand-0 hover:bg-accent border border-input dark:bg-brand-800/40 dark:text-brand-300 dark:hover:bg-brand-800/50'>
                    Close
                </AppButton>

            </div>
        </>
    );
};

export default GeneralSettings;
