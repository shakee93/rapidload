import React, { useEffect, useRef, useState, useMemo, useCallback } from "react";
import AppButton from "components/ui/app-button";
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from "../../../components/ui/accordion";
import { useCompletion, experimental_useObject as useObject } from 'ai/react'
import { AnimatePresence, m, motion } from "framer-motion"
import useCommonDispatch from "hooks/useCommonDispatch";
import { changeGear } from '../../../store/app/appActions';
import { LoaderIcon, ChevronDown, GaugeCircle, RefreshCw, Sparkles } from "lucide-react";
import { useSelector } from "react-redux";
import { optimizerData } from "../../../store/app/appSelector";
import { CheckCircleIcon } from '@heroicons/react/24/solid';
import OptimizationsProgress from '../../../components/optimizations-progress';
import { useAppContext } from "../../../context/app";
import ApiService from "../../../services/api";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "components/ui/collapsible";
import { Button } from "components/ui/button";
import { toast } from "components/ui/use-toast";
import { cn } from "lib/utils";
import { z } from 'zod';
import Setting from "../components/audit/Setting";

const DiagnosticSchema = z.object({
    AnalysisSummary: z.string(),
    PluginConflicts: z.array(
        z.object({
            plugin: z.string(),
            recommendedAction: z.string(),
        })
    ),
    CriticalIssues: z.array(
        z.object({
            issue: z.string(),
            description: z.string(),
            howToFix: z.array(z.object({
                step: z.string(),
                description: z.string(),
                type: z.enum(['rapidload_fix', 'wordpress_fix', 'code_fix', 'other']),
                substeps: z.array(z.object({
                    step: z.string(),
                    description: z.string(),
                })).optional().describe('Substeps to fix the issue.'),
            })),
            resources: z.array(z.object({
                name: z.string(),
                url: z.string(),
                type: z.enum(['javascript', 'css', 'image', 'html', 'other']),
                relatedAudit: z.string().optional().describe('Related audit from PageSpeed Insights.'),
                reason: z.string().optional().describe('Reason why this resource is related to the issue.'),
            })),
            pagespeed_insight_audits: z.array(z.string()),
            pagespeed_insight_metrics: z.array(z.string()),
            anyAdditionalTips: z.array(z.string()).optional().describe('Any additional tips to fix the issue.'),
        })
    ),
});

// TODO: create an env variable for this
// const AIBaseURL = "http://localhost:3000/api"
const AIBaseURL = "https://ai.rapidload.io/api"

const Optimizations = ({ }) => {
    const { settings, data } = useSelector(optimizerData);
    const [diagnosticsLoading, setDiagnosticsLoading] = useState(false);
    const [loadingText, setLoadingText] = useState<string | null>(null);

    const { object, submit, isLoading, error } = useObject({
        api: `${AIBaseURL}/diagnosis`,
        schema: DiagnosticSchema,
        onFinish: (diagnostic: any) => {
            console.log(diagnostic)
            setDiagnosticsLoading(false)
            setLoadingText(null)

            toast({
                title: "AI Diagnostic Complete",
                description: "AI analysis of your page has been completed successfully.",
                variant: "default",
            });
        }
    });

    const { dispatch } = useCommonDispatch()
    const { options } = useAppContext()
    const optimizerUrl = options?.optimizer_url;
    const [showIframe, setShowIframe] = useState(false);

    const doAnalysis = useCallback(async (diagnostics: any) => {
        setLoadingText('Collecting active plugins...')
        const api = new ApiService(options);
        const plugins = await api.getActivePlugins();


        const _diagnostics = Object.entries(diagnostics).map(([key, value]) => value)
        console.log(_diagnostics)

        const input = {
            settings: settings.map((s: any) => ({
                ...s,
                inputs: s.inputs.map((i: any) => ({
                    ...i,
                    diagnostics: i.value ? _diagnostics.filter((d: any) => d.key === i.key).map(({ key, ...rest }: any) => rest) : []
                })),
            })),
            plugins: plugins.data,
            report: {
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
            // diagnostics: Object.entries(diagnostics).map(([key, value]) => value),
        }

        setLoadingText('Hermes AI is analyzing your page...')

        try {

            console.log(input)
            submit(input)


        } catch (error: any) {
            console.error('AI Diagnosis Error:', error);
            setDiagnosticsLoading(false);
            setLoadingText(null);

            // Show error toast
            toast({
                title: "AI Diagnostic Failed",
                description: error?.message || "Failed to complete AI analysis. Please try again.",
                variant: "destructive",
            });

            // Show error in loading area
            setLoadingText(`❌ ${error?.message || "Failed to complete AI analysis. Please try again."}`);
        }
    }, [loadingText])

    useEffect(() => {
        const handleMessage = (event: MessageEvent) => {
            if (event.data.type === "RAPIDLOAD_CHECK_RESULTS") {
                console.log("Received data from iframe:", event.data);

                setLoadingText('Collected data from your page...')
                // Compare received data with settings
                const receivedData = event.data.data;
                const matches: any[] = [];

                settings.forEach((setting: AuditSetting) => {
                    const mainInput = setting.inputs[0];
                    if (!mainInput) return;
                    Object.entries(receivedData).forEach(([category, data]: [string, any]) => {
                        if (data.key === mainInput.key) {
                            matches.push({
                                key: category,
                                optimizerSettings: {
                                    settingName: setting.name,
                                    settingKey: mainInput.key,
                                    settingValue: mainInput.value,
                                    status: setting.status,
                                },
                                receivedData: {
                                    key: data.key,
                                    status: data.status,
                                    nonOptimizedItems: data.non_optimized_css || data.non_minified_css || data.non_minified_js || data.non_deferred_js || data.non_delayed_js
                                }
                            });
                        }
                    });
                });

                console.log(event.data.data)
                doAnalysis(event.data.data)
            }
        };

        window.addEventListener("message", handleMessage);

        return () => {
            window.removeEventListener("message", handleMessage);
        };
    }, [settings]);

    const getFilteredSettings = (settings: any) => {
        return settings.filter((setting: any) => setting.inputs.some((input: any) => input.value === true));
    };
    ;

    const renderOptimizationStep = useCallback((step: string, index: number) => (
        <div key={index} className="flex items-start gap-2 py-1 relative">
            <div className=" bg-gray-100 rounded-full flex items-center justify-center p-1">
                <LoaderIcon className="h-4 w-4 text-gray-600 animate-spin" />
            </div>
            <CheckCircleIcon className="h-6 w-6 text-green-600" />
            <span className={`text-sm transition-colors text-gray-900`}>{step.name}</span>
        </div>
    ), []);

    return (
        <AnimatePresence>
            <m.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.2, delay: 0.05 }}
                className=''
            >
                <div className='px-11 py-4'>
                    <div className="pb-4">
                        {false &&
                            <div>loading...</div>
                        }
                        <h3 className="font-semibold text-lg">Optimizations Summary</h3>
                        <span className="font-normal text-sm text-zinc-600 dark:text-brand-300">Let’s confirm that all optimizations are working as expected...</span>
                    </div>

                    <div className="grid grid-cols-5 gap-4 mb-6">
                        {/* <div className='col-span-2 bg-brand-0 rounded-2xl p-4'>

                            <div className="flex flex-col pt-1 gap-2 w-full">
                                <OptimizationsProgress />
                            </div>

                        </div> */}

                        <div className={cn('relative col-span-5 bg-brand-0 rounded-2xl p-10 flex flex-col gap-4 text-center', !object?.CriticalIssues?.length && 'items-center justify-center')}>


                            {!object?.AnalysisSummary?.length ? <>


                                <h3 className="font-semibold text-lg">Test Your Optimizations</h3>
                                <span className="font-normal text-sm text-zinc-600 dark:text-brand-300">Your optimizations are complete! However, changes might not take effect due to factors like caching, conflicts with plugins or themes, or dynamic content. Let's test to ensure everything is working smoothly and identify any bottlenecks.</span>
                                <AppButton
                                    disabled={diagnosticsLoading}
                                    className="rounded-xl px-8 py-4"
                                    onClick={() => {
                                        setLoadingText('Collecting Diagnostics from your page...')
                                        setDiagnosticsLoading(true)
                                        setShowIframe(true)
                                    }}
                                >
                                    {diagnosticsLoading && <LoaderIcon className="h-4 w-4 text-white animate-spin" />}
                                    Run Optimization Test
                                </AppButton>
                                <span className="font-normal text-xs text-zinc-600 dark:text-brand-300">Disabled: All Optimizations needs to be completed to run the test</span>

                            </> :
                                <div className="w-full">
                                    <div className="absolute top-4 right-4 flex justify-end mb-4">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            hidden={isLoading}
                                            disabled={diagnosticsLoading}
                                            className="p-2 py-4 h-6 flex items-center gap-2"
                                            onClick={async () => {
                                                setShowIframe(false);
                                                setDiagnosticsLoading(true);
                                                setLoadingText('Refreshing diagnostics...');
                                                await new Promise(resolve => setTimeout(resolve, 200));
                                                setShowIframe(true);
                                            }}
                                        >
                                            <RefreshCw className={cn("h-3.5 w-3.5", diagnosticsLoading && "animate-spin")} /> Test Again
                                        </Button>
                                    </div>
                                    <h3 className="font-semibold text-lg flex items-center gap-2 ">
                                        <Sparkles className="h-5 w-5 text-brand-600" />
                                        Hermes AI Diagnosis</h3>
                                    <div className="w-full mt-4 text-left">
                                        <div className="text-sm text-zinc-600 dark:text-zinc-300">
                                            {object?.AnalysisSummary}
                                        </div>

                                        <div className="flex flex-col gap-2">
                                            <Accordion type="multiple" defaultValue={["0"]}>
                                                {object?.CriticalIssues?.map((result: any, index: number) => (
                                                    <AccordionItem key={index} value={index.toString()}>
                                                        <AccordionTrigger className=" font-semibold text-zinc-900 dark:text-zinc-100">

                                                            <div className="flex flex-col justify-start items-start gap-0.5 w-full">
                                                                <div>
                                                                    {result?.issue}
                                                                </div>

                                                            </div>


                                                        </AccordionTrigger>
                                                        <AccordionContent>
                                                            <div className="px-4 py-4 border-2 shadow-md rounded-lg space-y-2 flex flex-col justify-start items-start text-left">
                                                                <div>
                                                                    <p className="text-sm text-left font-normal text-zinc-600 dark:text-zinc-300">{result?.description}</p>
                                                                </div>
                                                                <div>
                                                                    <p className="text-lg font-medium text-zinc-800 dark:text-zinc-200 mb-2">How to fix</p>
                                                                    <ul className="list-disc space-y-4 pl-4 mt-4">
                                                                        {result?.howToFix?.map((fix: any) => (
                                                                            <li key={fix} className="text-sm text-zinc-600 dark:text-zinc-300">
                                                                                <div className="flex flex-col gap-1">
                                                                                    <span className="text-sm font-medium text-zinc-800 dark:text-zinc-200">{fix.step}</span>
                                                                                    <span className="text-xs text-zinc-600 dark:text-zinc-300">{fix.description}</span>
                                                                                    {fix.type === 'rapidload_fix' &&
                                                                                        <>
                                                                                            <AppButton
                                                                                                size="sm"
                                                                                                className="mt-2 w-fit px-4 text-xs flex items-center gap-2"
                                                                                                onClick={() => {
                                                                                                    console.log(settings.find((s: any) => s.inputs.find((i: any) => i.key === fix.rapidload_setting_input?.name)))
                                                                                                }}
                                                                                            >
                                                                                                <Sparkles className="h-3.5 w-3.5 text-white -ml-1.5" /> Fix with AI
                                                                                            </AppButton>
                                                                                            {/* <span className="mt-2">
                                                                                                <Setting
                                                                                                    index={index}
                                                                                                    settings={settings.find((s: any) => s.inputs.find((i: any) => i.key === fix.rapidload_setting_input?.name))}
                                                                                                />
                                                                                            </span> */}

                                                                                        </>
                                                                                    }

                                                                                </div>
                                                                            </li>
                                                                        ))}

                                                                    </ul>

                                                                    <div className="flex flex-col gap-2 mt-4">
                                                                        <span className="text-sm font-medium text-zinc-800 dark:text-zinc-200">Related Resources:</span>
                                                                        <span className="text-sm text-blue-600 dark:text-blue-300"> <a href={result?.resources?.map((r: any) => r.url).join(', ')} target="_blank" rel="noopener noreferrer">{result?.resources?.map((r: any) => r.url).join(', ')}</a> </span>
                                                                        <span className="text-sm text-zinc-600 dark:text-zinc-300"> <a href={result?.resources?.map((r: any) => r.url).join(', ')} target="_blank" rel="noopener noreferrer">{result?.resources?.map((r: any) => r.reason).join(', ')}</a> </span>
                                                                    </div>

                                                                    <div className="flex flex-col gap-2 mt-4">
                                                                        <span className="text-sm font-medium text-zinc-800 dark:text-zinc-200">Related Audit:</span>
                                                                        <span className="text-sm dark:text-blue-300"> <a href={result?.pagespeed_insight_audits?.join(', ')} target="_blank" rel="noopener noreferrer">
                                                                            {result?.pagespeed_insight_audits?.join(', ')}
                                                                        </a> </span>
                                                                    </div>

                                                                    <div className="flex flex-col gap-2 mt-4">
                                                                        <span className="text-sm font-medium text-zinc-800 dark:text-zinc-200">Related Metrics:</span>
                                                                        <span className="text-sm dark:text-blue-300"> <a href={result?.pagespeed_insight_metrics?.join(', ')} target="_blank" rel="noopener noreferrer">
                                                                            {result?.pagespeed_insight_metrics?.join(', ')}
                                                                        </a> </span>
                                                                    </div>



                                                                </div>
                                                            </div>

                                                            {/* <Collapsible>
                                                                <CollapsibleTrigger className="mt-6 text-sm font-medium text-zinc-800 dark:text-zinc-200 mb-2">
                                                                    View Raw Data
                                                                </CollapsibleTrigger>
                                                                <CollapsibleContent>
                                                                    <pre className="mt-2 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg overflow-auto">
                                                                        {JSON.stringify(result, null, 2)}
                                                                    </pre>
                                                                </CollapsibleContent>
                                                            </Collapsible> */}
                                                        </AccordionContent>
                                                    </AccordionItem>
                                                ))}
                                            </Accordion>


                                            {object?.PluginConflicts && object?.PluginConflicts.length > 0 && (
                                                <div className="flex flex-col gap-2 mt-6">
                                                    <span className="text-sm font-medium text-zinc-800 dark:text-zinc-200">Potential Plugin Conflicts:</span>

                                                    <span className="text-sm text-zinc-600 dark:text-zinc-300">
                                                        These plugins may conflict with each other, causing issues with your page.

                                                        WORK IN PROGRESS
                                                    </span>
                                                    <ul className="space-y-3">
                                                        {object?.PluginConflicts.map((conflict: any, index: number) => (
                                                            <li key={index} className="flex flex-col gap-1">
                                                                <span className="text-sm font-medium text-red-600 dark:text-red-400">
                                                                    {conflict.plugin}
                                                                </span>
                                                                <span className="text-sm text-zinc-600 dark:text-zinc-300">
                                                                    Recommended Action: {conflict.recommendedAction}
                                                                </span>
                                                                <span className="text-sm text-zinc-600 dark:text-zinc-300">
                                                                    Categories: {conflict?.categories?.join(', ')}
                                                                </span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                            }

                        </div>
                    </div>

                    {diagnosticsLoading && <div className="flex items-center gap-2 mt-4 bg-brand-0 rounded-2xl p-4 py-3">
                        <LoaderIcon className="h-4 w-4 text-gray-600 animate-spin" />
                        <span className="text-sm text-gray-600">{loadingText}</span>
                    </div>}

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
                                    src={showIframe ? `${optimizerUrl}/?rapidload_preview` : ''}
                                    // src={`${window.uucss_global.home_url}/?rapidload_preview`} 
                                    className="w-full h-[600px] border-0"
                                    title="Optimization Test"
                                />
                            </div>
                        </div>
                    )}
                </div>
            </m.div>
        </AnimatePresence>
    )
}

export default Optimizations;   