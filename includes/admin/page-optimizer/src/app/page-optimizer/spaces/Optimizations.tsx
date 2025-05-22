import React, { useEffect, useRef, useState, useMemo, useCallback } from "react";
import AppButton from "components/ui/app-button";
import { AccordionItem, AccordionTrigger, AccordionContent } from "../../../components/ui/accordion";
import { useCompletion, experimental_useObject as useObject } from 'ai/react'
import { AnimatePresence, m, motion } from "framer-motion"
import useCommonDispatch from "hooks/useCommonDispatch";
import { changeGear, fetchReport, fetchSettings, setDiagnosticProgress, updateDiagnosticResults } from '../../../store/app/appActions';
import { LoaderIcon, ChevronDown, GaugeCircle, RefreshCw, Sparkles, Lock } from "lucide-react";
import { useSelector } from "react-redux";
import { optimizerData } from "../../../store/app/appSelector";
import { CheckCircleIcon, Cog6ToothIcon } from '@heroicons/react/24/solid';
import OptimizationsProgress from '../../../components/optimizations-progress';
import { useAppContext } from "../../../context/app";
import ApiService from "../../../services/api";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "components/ui/collapsible";
import { Button } from "components/ui/button";
import { toast } from "components/ui/use-toast";
import { cn } from "lib/utils";
import { z } from 'zod';
import { AnimatedLogo } from "components/animated-logo";
import ProgressTracker from '../../../components/ProgressTracker';
import { compareVersions } from 'compare-versions';
import { AnalysisResults } from '../../../components/analysis-results';
import { setCommonState } from "../../../store/common/commonActions";
import AIDemoMessage from "../components/AIDemoMessage";
import ErrorFetch from "components/ErrorFetch";
import { ExclamationTriangleIcon } from "@heroicons/react/24/solid";
import AnimatedDiv from "components/ui/animatedDiv";
import TooltipText from "components/ui/tooltip-text";
import TimeAgo from "components/TimeAgo";
import { Dialog, DialogDescription, DialogHeader, DialogContent, DialogTitle, DialogTrigger } from "components/ui/dialog";
import { Checkbox } from "components/ui/checkbox";
import { isDev } from "lib/utils";
import Mode from "../components/Mode";
import ProTooltip from "components/ProTooltip";

const DiagnosticSchema = z.object({
    // active_settings_inputs: z.array(z.object({
    //     name: z.string(),
    //     value: z.string().nullable(),
    // })),
    AnalysisTitle: z.string().optional(),
    AnalysisSummary: z.string(),
    // PluginConflicts: z.array(
    //     z.object({
    //         plugin: z.string(),
    //         recommendedAction: z.string(),
    //     })
    // ),
    CriticalIssues: z.array(
        z.object({
            issue: z.string(),
            description: z.string(),
            solutions: z.object({
                solutions_reasoning: z.array(z.object({
                    block_type: z.enum(['observation', 'analysis', 'hypothesis', 'validation', 'conclusion']),
                    thought: z.string(),
                    reasoning: z.string(),
                    confidence_level: z.number().min(0).max(100),
                    supporting_evidence: z.array(z.string()),
                    related_blocks: z.array(z.string()).optional(),
                })),
                solutions_list: z.array(z.object({
                    type: z.enum(['rapidload_fix', 'wordpress_fix', 'theme_fix', 'another_plugin_fix', 'code_fix', 'server_config_fix', 'server_upgrade_fix']),
                    title: z.string(),
                    description: z.string(),
                    steps: z.array(z.object({
                        step: z.number(),
                        action: z.string(),
                        details: z.string(),
                        verification: z.string().optional()
                    })),
                })),
            }),
            resources: z.array(z.object({
                name: z.string(),
                url: z.string(),
                type: z.enum(['javascript', 'css', 'image', 'html', 'other']),
                relatedAudit: z.string().optional().describe('Related audit from PageSpeed Insights.'),
                reason: z.string().optional().describe('Reason why this resource is related to the issue.'),
            })).optional(),
            pagespeed_insight_audits: z.array(z.string()),
            pagespeed_insight_metrics: z.array(z.string()),
            anyAdditionalTips: z.array(z.string()).optional().describe('Any additional tips to fix the issue.'),
        })
    ),
});


// Group progress-related state
interface ProgressState {
    isFlushingProgress: number;
    settingsProgress: number;
    pageSpeedProgress: number;
    serverInfoProgress: number;
    diagnosticsProgress: number;
    currentStep: number;
}

// TODO: create an env variable for this
// const AIBaseURL = "http://localhost:3000/api"
const AIBaseURL = window.rapidload_optimizer.ai_root || "https://ai.rapidload.io/api"

const Optimizations = ({ }) => {
    const { settings, data, activeReport, diagnosticResults, diagnosticProgress, loading, error } = useSelector(optimizerData);
    const [diagnosticComplete, setDiagnosticComplete] = useState(false);
    const [loadingText, setLoadingText] = useState<string | null>(null);
    const [aiLoading, setAiLoading] = useState(false);
    const [currentStep, setCurrentStep] = useState<number>(0);
    const [showDialog, setShowDialog] = useState(false);

    // Add cleanup effect to log component unmount
    useEffect(() => {
        return () => {
            setDiagnosticError(null);
            dispatch(setCommonState('diagnosticLoading', false));
            setAiLoading(false);
            setDiagnosticData(null);
        };
    }, []);

    // Consolidated progress state
    const [progressState, setProgressState] = useState<ProgressState>({
        isFlushingProgress: 0,
        settingsProgress: 0,
        pageSpeedProgress: 0,
        serverInfoProgress: 0,
        diagnosticsProgress: 0,
        currentStep: 0
    });

    const { headerUrl, diagnosticLoading, licenseConnected } = useCommonDispatch();
    const [remainingTime, setRemainingTime] = useState(0);
    const [serverDetails, setServerDetails] = useState(null);
    const [input, setInput] = useState(null);
    const [diagnosticError, setDiagnosticError] = useState<string | null>(null);
    const [aiResponding, setAiResponding] = useState(false);
    const abortControllerRef = useRef<AbortController | null>(null);
    const [privacyPolicy, setPrivacyPolicy] = useState(false);
    const [diagnosticData, setDiagnosticData] = useState<DiagnosticResults | null>(null);
    const [lastDiagnosticData, setLastDiagnosticData] = useState<DiagnosticResults | null>(null);
    const { options } = useAppContext();
    

    useEffect(() => {
        const storedValue = localStorage.getItem("rapidload_privacy_policy");
        if (storedValue === "true") {
            setPrivacyPolicy(true);
        }
    }, [privacyPolicy]);

    // Helper functions to update grouped state
    const updateProgressState = (updates: Partial<ProgressState>) => {
        setProgressState(prev => ({ ...prev, ...updates }));
    };


    const relatedAudits = useMemo(() => {
        if (!data?.grouped) return [];

        return [
            ...(data.grouped as Record<AuditTypes, Audit[] | undefined>)["opportunities"] || [],
            ...(data.grouped as Record<AuditTypes, Audit[] | undefined>)["diagnostics"] || []
        ];
    }, [data?.grouped]);


    const { object, submit, isLoading } = useObject({
        api: `${AIBaseURL}/diagnosis`,
        schema: DiagnosticSchema,
        headers: {
            'Authorization': `Bearer ${isDev ? import.meta.env.VITE_KEY : options.license_key!}`
        },
        onFinish: (diagnostic: any) => {
            //console.log(diagnostic)
            aiResultsComplete();

            toast({
                title: "AI Diagnostic Complete",
                description: "AI analysis of your page has been completed successfully.",
                // variant: "default",
            });
        }
    });

    const { dispatch } = useCommonDispatch()
    const optimizerUrl = options?.optimizer_url;
    const [showIframe, setShowIframe] = useState(false);

    const progressSteps = [
        { duration: '15s', label: 'Flush Cache', progress: progressState.isFlushingProgress },
        { duration: '5s', label: 'Optimizations', progress: progressState.settingsProgress },
        { duration: '10s', label: 'Server Info', progress: progressState.serverInfoProgress },
        { duration: '40s', label: 'New Page Speed', progress: progressState.pageSpeedProgress },
        { duration: '10s', label: 'Page Diagnostics', progress: progressState.diagnosticsProgress },
    ];

    const handleRemainingTimeUpdate = (time: number) => {
        setRemainingTime(time);
    };

    useEffect(() => {
        if (object?.AnalysisSummary && object.AnalysisSummary.length) {
            setDiagnosticData(object as DiagnosticResults);
        }
    }, [object]);

    useEffect(() => {
        setLastDiagnosticData(diagnosticResults);
       // console.log("lastDiagnosticData", diagnosticResults)
    }, [diagnosticResults]);

    const aiResultsComplete = () => {
        //  setLoadingText(null)
        setDiagnosticComplete(true)
        updateProgressState({ diagnosticsProgress: 100 });
        resetDiagnosticResults();
        setAiLoading(false);
        setAiResponding(false);
    };



    const resetDiagnosticResults = () => {
        setCurrentStep(0);
        updateProgressState({ settingsProgress: 0 });
        updateProgressState({ serverInfoProgress: 0 });
        updateProgressState({ pageSpeedProgress: 0 });
        updateProgressState({ diagnosticsProgress: 0 });
        updateProgressState({ isFlushingProgress: 0 });
        setAiLoading(false);
        setAiResponding(false);
        setLoadingText(null)
        dispatch(setCommonState('diagnosticLoading', false));

    }

    useEffect(() => {
        //  console.log("diagnosticComplete", diagnosticComplete);
        saveDiagnosticResults();
    }, [diagnosticComplete]);

    const saveDiagnosticResults = () => {

        if (!diagnosticData || !diagnosticComplete) {
            return;
        }

        const data = { ...diagnosticData, timeStamp: Date.now() }
        try {
            dispatch(updateDiagnosticResults(options, headerUrl ? headerUrl : optimizerUrl, data));
        } catch (error: any) {
            console.error('Error on updating Diagnostic Results:', error);
        }
    };


    const handleDiagnosticError = (error: string) => {

        return <div className="flex gap-8 items-center mt-8">
            <div className='border-2 border-red-500 rounded-xl flex items-start gap-4 p-4'>
                <div className='p-2 bg-red-200/30 rounded-lg'>
                    <ExclamationTriangleIcon className='w-10 h-10 text-red-500' />
                </div>
                <div className='flex flex-col gap-1'>
                    <span className='font-medium text-md '>Oops! Something went wrong</span>
                    <span className='text-sm text-brand-700 dark:text-brand-300'>Run Diagnostics Test Again</span>
                    <div className='text-sm text-brand-500 dark:text-brand-200 border-t mt-2 pt-2 max-w-[350px]'>
                        <span className='font-medium text-brand-800 dark:text-brand-400'>Details:</span> {error}</div>
                </div>
            </div>
        </div>
    }


    const doAnalysis = useCallback(async (diagnostics: any) => {
        setLoadingText('Collecting active plugins...')
        const api = new ApiService(options);
        // const plugins = await api.getActivePlugins();
        // const server_data = await api.post('rapidload_server_info');
        let plugins, server_data;

        try {
            plugins = await api.getActivePlugins();
            server_data = await api.post('rapidload_server_info');
        } catch (error: any) {
          //  console.log("plugins error", error?.message)
            setDiagnosticError(error?.message || "Failed to fetch plugins or server info");
            return; // Exit early if API calls fail
        }


        const _diagnostics = Object.entries(diagnostics).map(([key, value]) => value)
        // console.log(_diagnostics)

        const input = {
            settings: settings.map(({ status, ...rest }) => ({
                ...rest,
                enabled: rest.inputs[0]?.value || false,
            })),
            plugins: plugins.data,
            report: {
                url: options?.optimizer_url,
                performance: data?.performance,
                metrics: data?.metrics.map((m: any) => ({
                    metric: m.refs.acronym,
                    displayValue: m.displayValue,
                    score: m.score,
                })),
                opportunities: data?.grouped?.opportunities.map((o: Audit) => ({
                    name: o.name,
                    score: o.score,
                    metrics: o.metrics.map((m: any) => m.refs.acronym),
                    settings: o.settings.map((s: any) => s.name),
                    files: o.files.items.map((f: AuditTableResource) => f?.url),
                })),
                diagnostics: data?.grouped?.diagnostics.map((d: any) => ({
                    name: d.name,
                    score: d.score,
                    metrics: d.metrics.map((m: any) => m.refs.acronym),
                    settings: d.settings.map((s: any) => s.name),
                })),
            },
            plugin_diagnostics: settings.map((s: any) => ({
                name: s.name,
                settings_diagnostics: s.status,
                real_user_diagnostics: _diagnostics.find((d: any) => d.name === s.name),
                value: s.name === "Cache Policy" ? "one_time_action" : (s.inputs[0]?.value || false)
            })).filter((s: any) => s.settings_diagnostics || s.real_user_diagnostics),
            server_details: server_data.data ? {
                ...server_data.data,
                headers: {
                    ...server_data.data.headers,
                    'x-cache-handler': undefined,
                    'x-cache-status': undefined,
                    server: undefined,
                }
            } : null,
        }

        setLoadingText('Rapidload AI is analyzing your page...')
        setAiResponding(true);

        try {

            // setInput(input)
            submit(input)
            updateProgressState({ diagnosticsProgress: 95 });

        } catch (error: any) {
           // console.error('AI Diagnosis Error:', error);
            dispatch(setCommonState('diagnosticLoading', false));
            setAiLoading(false);
            setLoadingText(null);
            setDiagnosticError(error?.message || "Failed to complete AI analysis. Please try again.");

            // Show error toast
            toast({
                title: "AI Diagnostic Failed",
                description: error?.message || "Failed to complete AI analysis. Please try again.",
                // variant: "destructive",
            });

            // Show error in loading area
            setLoadingText(`❌ ${error?.message || "Failed to complete AI analysis. Please try again."}`);
        }
    }, [loadingText, serverDetails, settings])

    useEffect(() => {
        const handleMessage = (event: MessageEvent) => {
            if (event.data.type === "RAPIDLOAD_CHECK_RESULTS") {
                setLoadingText('Collected data from your page...')
                doAnalysis(event.data.data)
            } else if (event.data.type === "RAPIDLOAD_DIAGNOSTIC_PAGE_LOAD_ERROR") {
                setDiagnosticError(event.data.data)
            }
        };

        window.addEventListener("message", handleMessage);

        return () => {
            window.removeEventListener("message", handleMessage);
        };
    }, [settings]);

    const startDiagnostics = async () => {

        try {

            updateProgressState({ diagnosticsProgress: 25 });

            // const progressInterval = setInterval(() => {
            //     setDiagnosticsProgress(prev => Math.min(prev + 10, 90));
            // }, 1000);

            if (diagnosticComplete) {
                setDiagnosticComplete(false)
                setShowIframe(false);
                setLoadingText('Refreshing diagnostics...');
                await new Promise(resolve => setTimeout(resolve, 200));
                setShowIframe(true);
            } else {
                setLoadingText('Collecting Diagnostics from your page...')
                setShowIframe(true)
                // i want to handle the iframe onload error here
            }


            updateProgressState({ diagnosticsProgress: 50 });

        } catch (error: any) {
            updateProgressState({ diagnosticsProgress: 0 });
            setDiagnosticError(error?.message || "Failed to run diagnostics");
            // console.error('❌ Diagnostics failed:', error);
            // toast({
            //     title: "Diagnostics Failed",
            //     description: error?.message || "Failed to run diagnostics",
            //     variant: "destructive",
            // });
        }

    };





    const preloadPage = async (previewUrl: string) => {
        const USER_AGENTS = {
            mobile: 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.133 Mobile Safari/537.36',
            desktop: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36'
        };

        const api = new ApiService(options);

        if (compareVersions(options?.rapidload_version, '2.2.11') > 0) {
            //   console.log('Preloading page with URL:', previewUrl);
            await api.post(`preload_page`, {
                url: previewUrl,
                user_agent: activeReport === 'mobile' ? USER_AGENTS.mobile : USER_AGENTS.desktop,
                nonce: options.nonce as string,
                job_id: data?.job_id as string,
            });
        } else {
            const rest = api.rest();
            await rest.request('/ping', {
                'url': previewUrl,
                'user_agent': activeReport === 'mobile' ? USER_AGENTS.mobile : USER_AGENTS.desktop,
                'nonce': options?.nonce as string,
                'job_id': data?.job_id as string,
            });
        }
    };

    const handleFlushCache = async () => {

        const api = new ApiService(options);
        const previewUrl = optimizerUrl + '?rapidload_preview'

        try {
            updateProgressState({ isFlushingProgress: 10 });

            await api.post('clear_page_cache', {
                url: optimizerUrl
            });

            updateProgressState({ isFlushingProgress: 60 });

            await preloadPage(previewUrl);

            updateProgressState({ isFlushingProgress: 100 });
            setCurrentStep(1);
            runParallelSteps();

            // toast({
            //     title: "Cache Flushed",
            //     description: "Page cache has been cleared successfully.",
            //     variant: "default",
            // });

        } catch (error: any) {
            updateProgressState({ isFlushingProgress: 0 });
            setDiagnosticError(error.message || "Failed to clear page cache");
            // console.error('❌ Flushing cache failed:', error);
            // toast({
            //     title: "Cache Flush Failed",
            //     description: error.message || "Failed to clear page cache",
            //     variant: "destructive",
            // });
        }
    };

    // const simulateProgress = (setter: React.Dispatch<React.SetStateAction<number>>, start: number, end: number) => {
    //     if (start >= end) return;

    //     setTimeout(() => {
    //         setter(start + 5); 
    //         simulateProgress(setter, start + 5, end); 
    //     }, 100); 
    // };
    const simulateProgress = (
        progressKey: keyof ProgressState,
        start: number,
        end: number
    ) => {
        if (start >= end) return;

        setTimeout(() => {
            updateProgressState({ [progressKey]: start + 5 });
            simulateProgress(progressKey, start + 5, end);
        }, 100);
    };

    const runParallelSteps = async () => {

        try {

            await Promise.all([
                // Fetch Settings
                (async () => {
                    try {
                        simulateProgress('settingsProgress', 25, 90);

                        await dispatch(fetchSettings(options, headerUrl ? headerUrl : options.optimizer_url, true));

                        updateProgressState({ settingsProgress: 100 });
                        //  console.log('✅ Settings fetch completed');
                    } catch (error: any) {

                        setDiagnosticError(error?.message || "Failed to run settings");
                        throw error;
                    }
                })(),

                // Server Info Check
                (async () => {
                    try {
                        simulateProgress('serverInfoProgress', 25, 90);

                        const api = new ApiService(options);
                        const data = await api.post('rapidload_server_info');
                        setServerDetails(data)

                        updateProgressState({ serverInfoProgress: 100 });
                        // console.log('✅ Server info check completed');
                    } catch (error: any) {
                        setDiagnosticError(error?.message || "Failed to run server info");
                        throw error;
                    }
                })(),

                // New Page Speed
                (async () => {
                    try {
                        // abortControllerRef.current = new AbortController();
                        simulateProgress('pageSpeedProgress', 25, 90);

                        // await dispatch(fetchReport(options, headerUrl ? headerUrl : options.optimizer_url, true, abortControllerRef.current));
                         await dispatch(fetchReport(options, headerUrl ? headerUrl : options.optimizer_url, true));

                        updateProgressState({ pageSpeedProgress: 100 });
                        // abortControllerRef.current = null; 
                        // console.log('✅ PageSpeed fetch completed');
                    } catch (error: any) {
                        updateProgressState({ pageSpeedProgress: 0 });
                        setDiagnosticError(error?.message || "Failed to run page speed");
                        throw error;
                    }
                })()
            ]);

            // All steps completed successfully
            // toast({
            //     title: "All Steps Completed",
            //     description: "Settings, server info, and PageSpeed data updated successfully.",
            //     variant: "default",
            // });

            setTimeout(async () => {
                setCurrentStep(4);
                startDiagnostics();

            }, 1000);



        } catch (error: any) {
            setDiagnosticError(error?.message || "One or more steps failed to complete");

        }
    };


    // useEffect(() => {
    //     return () => {
    //         if (abortControllerRef.current) {
    //             abortControllerRef.current.abort();
    //         }
    //     };
    // }, []);

    useEffect(() => {

        if (error) {
            setDiagnosticError(error)
        }
    }, [error])

    const handleIframeError = (error: React.SyntheticEvent<HTMLIFrameElement, Event>) => {
        setDiagnosticError("Failed to load preview page. Please check if the page is accessible.");
    };

    const handleIframeLoad = () => {
       // console.log("iframe loaded")
    };

   useEffect(() => {

    if(diagnosticError?.length && (progressState.pageSpeedProgress === 100 || progressState.pageSpeedProgress === 0 || progressState.isFlushingProgress === 0)){
        resetDiagnosticResults(); 
       
        // if (abortControllerRef.current) {
        //     abortControllerRef.current.abort();
        //     abortControllerRef.current = null; // Reset controller
        //     resetDiagnosticResults();
        //     console.log('abortController aborted');
        // }
        
    }
   }, [diagnosticError, progressState.pageSpeedProgress, progressState.isFlushingProgress])


    return (
        <AnimatePresence>
            <m.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.2, delay: 0.05 }}
                className='dark:bg-brand-900 bg-[#F0F0F1]'
            >


                <div className={cn('px-6 py-6 bg-white z-50 relative', aiLoading && !aiResponding ? 'rounded-t-3xl dark:bg-brand-800' : 'rounded-3xl dark:bg-brand-800')}>
                    <div className="flex gap-4 w-full items-start">
                        {/* Logo Column */}
                        <div className="flex justify-start items-center gap-2 w-10">

                            <AnimatedLogo size="lg" isPlaying={aiLoading} animationType={aiResponding ? "path" : "moving"} />
                        </div>

                        {/* Content Column */}
                        <div className="flex flex-col gap-1 flex-grow">
                            <span className="text-base font-normal text-zinc-900 dark:text-zinc-100">
                                {aiLoading ? (
                                    <>
                                        {currentStep === 0 && "Clearing all cached data to ensure fresh analysis..."}
                                        {currentStep === 1 && "Gathering current optimization settings..."}
                                        {currentStep === 2 && "Analyzing server configuration and performance..."}
                                        {currentStep === 3 && "Running comprehensive PageSpeed diagnostics..."}
                                        {currentStep === 4 && (loadingText ? loadingText : "Processing data through AI for insights...")}
                                    </>
                                ) : (
                                    "Do you need any AI assistance?"
                                )}
                            </span>
                            <span className="font-normal text-sm text-zinc-600 dark:text-brand-300 max-w-[600px]">
                                {aiLoading && !object?.AnalysisSummary?.length ? (
                                    remainingTime === 0 ?
                                        "It's taking a bit longer than expected, hang tight..." :
                                        `Looks like I'll need to wait ${remainingTime}s more...`
                                ) : (
                                    object?.AnalysisSummary ?
                                        object.AnalysisSummary :
                                        "Let's check if your optimizations are working properly. Things might not update right away due to caching or conflicts. We should test everything to make sure it's running well."
                                )}

                            </span>
                        </div>

                        {/* Button Column */}
                        {/* Button Column */}
                        {!aiLoading &&

                            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                                <DialogTrigger asChild>
                                    <TooltipText text={loading ? "Please wait while applying optimizations" : null}>
                                        <div className={cn('flex justify-end items-center mt-2 gap-2')}>
                                            {!licenseConnected && (
                                                <Mode >
                                                    <ProTooltip
                                                       >
                                                        <Lock className='w-4 text-brand-400' />
                                                    </ProTooltip>
                                                </Mode>
                                            )}
                                            <AppButton
                                                disabled={aiLoading || !licenseConnected}
                                                className={cn("rounded-xl px-8 py-6 whitespace-nowrap dark:bg-brand-950 dark:hover:bg-brand-900 dark:text-brand-300", loading || !licenseConnected && 'cursor-not-allowed opacity-60 pointer-events-none')}
                                                onClick={() => {

                                                    if (!privacyPolicy) {
                                                        setShowDialog(true);
                                                        return;
                                                    }

                                                    setDiagnosticError(null);
                                                    dispatch(setCommonState('diagnosticLoading', true));
                                                    setAiLoading(true);
                                                    handleFlushCache();
                                                    setDiagnosticData(null);
                                                }}
                                            >
                                                {diagnosticData?.AnalysisSummary?.length ? 'Run Diagnostics Test Again' : 'Run Diagnostics Test '}

                                            </AppButton>
                                        </div>
                                    </TooltipText>
                                </DialogTrigger>
                               
                                <DialogContent className="sm:max-w-[650px]">
                                    <DialogHeader className='border-b px-6 py-4 mt-1'>
                                        <DialogTitle>To Work Best, RapidLoad Needs These Insights</DialogTitle>
                                    </DialogHeader>
                                    <div className="py-2 px-6">
                                        <div className="flex text-sm">
                                            <span>To provide the best AI-driven performance optimization, RapidLoad requires certain website details. By sharing this data, our AI can analyze and enhance your page speed more effectively.</span>
                                        </div>

                                        <Collapsible className="w-full mt-2">
                                            <CollapsibleTrigger className="flex text-sm w-full items-center justify-between py-2 font-medium hover:underline">
                                                <span>What data do we collect and send?</span>
                                                <ChevronDown className="h-4 w-4 transition-transform duration-200" />
                                            </CollapsibleTrigger>
                                            <CollapsibleContent className="text-sm text-muted-foreground">
                                                <ul className="list-disc pl-4 space-y-2 py-2">
                                                    <li>Active plugin list</li>
                                                    <li>Page Speed Report</li>
                                                    <li>Server type details</li>
                                                    <li>RapidLoad Settings information</li>
                                                </ul>

                                            </CollapsibleContent>
                                        </Collapsible>

                                        <div className="mt-2 text-sm">
                                            <p className="mt-2">
                                                <Lock className="w-4 h-4 inline-block text-green-700 mr-0.5 -mt-0.5" /> <span className="font-medium">Privacy First:</span>  We never collect or store personal data, credentials, or sensitive information. Your privacy remains our top priority.
                                            </p>
                                        </div>

                                        <div className="flex gap-2 font-medium text-base w-full justify-between mt-6 items-center">
                                            <div></div>
                                            <div className="flex gap-2">
                                                <Button
                                                    onClick={() => {
                                                        if (!privacyPolicy) {
                                                            localStorage.setItem("rapidload_privacy_policy", "true");
                                                            setShowDialog(false);

                                                            setDiagnosticError(null);
                                                            dispatch(setCommonState('diagnosticLoading', true));
                                                            setAiLoading(true);
                                                            handleFlushCache();
                                                        }
                                                    }}
                                                >
                                                    Opt-in & Improve
                                                </Button>

                                                <Button
                                                    variant="outline"
                                                    onClick={() => {
                                                        if (privacyPolicy) {
                                                            localStorage.setItem("rapidload_privacy_policy", "false");
                                                            setShowDialog(false);
                                                        }
                                                    }}
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        </div>

                                    </div>

                                    <DialogDescription>
                                        {/* Additional description if needed */}
                                    </DialogDescription>

                                </DialogContent>
                            </Dialog>

                        }
                    </div>

                    {diagnosticError?.length && (progressState.pageSpeedProgress === 100 || progressState.pageSpeedProgress === 0 || progressState.isFlushingProgress === 0) && handleDiagnosticError(diagnosticError)}


                    {/* {object?.AnalysisSummary?.length &&
                        <div className="grid grid-cols-5 gap-4 mb-6">
                            <div className={cn('relative col-span-5 bg-brand-0 rounded-2xl p-10 flex flex-col gap-4 text-center', !object?.CriticalIssues?.length && 'items-center justify-center')}>
                                


                            </div>
                        </div>
                    } */}


                    {showIframe && (
                        <div
                            className="h-0 overflow-hidden"
                        >
                            <div className="relative w-full rounded-xl overflow-hidden bg-white shadow-lg">
                                <div className="absolute top-4 right-4 z-10">
                                    <button
                                        onClick={() => setShowIframe(false)}
                                        className="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <iframe
                                    
                                 //   src={showIframe ? `${optimizerUrl.endsWith('/') ? `${optimizerUrl}?` : `${optimizerUrl}/?`}rapidload_preview` : ''}
                                    src={showIframe ? `${optimizerUrl}/?rapidload_preview` : ''}
                                  //  src={showIframe ? 'http://rapidload.local/?rapidload_preview' : ''}
                                    className="w-full h-[600px] border-0"
                                    title="Optimization Test"
                                    onError={handleIframeError}
                                    onLoad={handleIframeLoad}
                                />
                            </div>
                        </div>
                    )}
                </div>

                <m.div
                    initial={{ y: -50, opacity: 0, height: 0 }}
                    animate={{
                        y: aiLoading && !aiResponding ? 0 : -50,
                        opacity: aiLoading && !aiResponding ? 1 : 0,
                        height: aiLoading && !aiResponding ? 'auto' : 0
                    }}
                    transition={{
                        type: "spring",
                        duration: 0.5,
                        bounce: 0.2
                    }}

                    className="bg-white rounded-b-3xl border-t border-zinc-200 dark:border-zinc-800 overflow-hidden"
                >
                    {/* <ProgressTracker steps={progressSteps} currentStep={0} /> */}

                    <div className="flex flex-col gap-4 p-6 dark:bg-brand-950 dark:text-brand-300">
                        <ProgressTracker
                            steps={progressSteps}
                            currentStep={currentStep}
                            onTimeUpdate={handleRemainingTimeUpdate}
                        />
                    </div>

                </m.div>


                {diagnosticData?.AnalysisSummary?.length &&
                    <AnalysisResults
                        object={diagnosticData}
                        relatedAudits={relatedAudits}
                        input={input}
                        loading={!diagnosticComplete && aiResponding }
                    />
                }

                {lastDiagnosticData?.AnalysisSummary?.length && !diagnosticData?.AnalysisSummary?.length &&
                    <div className={lastDiagnosticData.timeStamp ? 'mt-6' : 'mt-0'}>

                        {lastDiagnosticData.timeStamp &&
                            <>
                                <div className='border-t border-zinc-300 dark:border-zinc-800' />
                                <div className="text-xs font-normal text-brand-450 dark:text-brand-300 mt-4">Last Diagnostics <TimeAgo timestamp={lastDiagnosticData.timeStamp} /></div>
                            </>
                        }

                        <AnalysisResults
                            object={lastDiagnosticData}
                            relatedAudits={relatedAudits}
                            input={input}
                        />
                    </div>
                }
            </m.div>
        </AnimatePresence>
    )
}

export default Optimizations;   