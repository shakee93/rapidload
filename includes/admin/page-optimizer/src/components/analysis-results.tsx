import { AccordionItem, AccordionTrigger, AccordionContent } from "./ui/accordion";
import { Sparkles, MicroscopeIcon, LightbulbIcon, FileCodeIcon } from "lucide-react";
import { PlusCircleIcon, MinusCircleIcon, CheckCircleIcon } from "@heroicons/react/24/solid";
import AppButton from "components/ui/app-button";
import { cn } from "lib/utils";
import type { PartialObject } from '../../types/ai';
import { useState } from "react";
import Accordion from "components/accordion";
import Card from "@/components/ui/card";
import { InformationCircleIcon, MicrophoneIcon } from "@heroicons/react/24/outline";
import TooltipText from "./ui/tooltip-text";
import FileTable from "app/page-optimizer/components/audit/content/table";
import { Fail } from "app/page-optimizer/components/icons/icon-svg";
import FAQSection from "./faq-section";
import { AiAudit } from "./ai-audit";

interface AnalysisResultsProps {
    object: PartialObject<{
        AnalysisSummary: string;
        CriticalIssues: Array<{
            issue: string;
            description: string;
            howToFix: Array<{
                step: string;
                description: string;
                type: "rapidload_fix" | "wordpress_fix" | "theme_fix" | "plugin_fix" | "code_fix" | "server_config_fix" | "server_upgrade_fix";
                rapidload_setting_input?: { name: string; };
            }>;
            resources: Array<any>;
            
            pagespeed_insight_audits: string[];
            pagespeed_insight_metrics: string[];
            how_to_fix_questions: {
                question: string;
                explanation: string;
                type: 'single_choice' | 'multiple_choice' | 'text' | 'number';
                options: {
                    value: string;
                    label: string;
                    description: string;
                }[];
            }[];
        }>;
        PluginConflicts?: Array<{
            plugin: string;
            recommendedAction: string;
            categories?: string[];
        }>;
    }>;
    relatedAudits: Audit[];
    input: any;
}

export const AnalysisResults = ({ object, relatedAudits, input }: AnalysisResultsProps) => {
    const [openItems, setOpenItems] = useState<string[]>(["0"]);

    const toggleAccordion = (id: string) => {
        setOpenItems(prev =>
            prev.includes(id)
                ? prev.filter(item => item !== id)
                : [...prev, id]
        );
    };

    return (
        <div className="grid grid-cols-5 gap-4 mb-6">
            <div className={cn('relative col-span-5 flex flex-col gap-4',
                !object?.CriticalIssues?.length && 'items-center justify-center')}>
                <div className="w-full">
                    <div className="w-full mt-4">
                        <div className="flex flex-col gap-4">
                            {object?.CriticalIssues?.map((issue, index) => (
                                <AiAudit
                                    key={index}
                                    issue={issue}
                                    index={index}
                                    openItems={openItems}
                                    toggleAccordion={toggleAccordion}
                                    input={input}
                                />
                            ))}

                            {object?.PluginConflicts && object?.PluginConflicts.length > 0 && (
                                <Card
                                    className="overflow-hidden w-full flex justify-center flex-col items-center p-0 rounded-3xl"
                                >
                                    <div className="min-h-[56px] relative flex justify-between w-full py-2 px-4">
                                        <div className="flex gap-3 font-normal items-center text-base">
                                            <div className="flex flex-col justify-around">
                                                <div className="flex gap-3.5 items-center text-zinc-800 dark:text-zinc-200">
                                                    <div className="inline-flex items-center justify-center w-7 h-7 rounded-full dark:bg-brand-700 bg-brand-200/50">
                                                        <InformationCircleIcon className="w-5 h-5" />
                                                    </div>
                                                    Potential Plugin Conflicts
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="border-t w-full">
                                        <div className="p-4">
                                            <div className="space-y-2">
                                                {object?.PluginConflicts.map((conflict: any, index: number) => (
                                                    <div
                                                        key={index}
                                                        className="flex items-center gap-3 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-brand-100/30 dark:bg-brand-800"
                                                    >
                                                        <div className="w-2 h-2 rounded-full bg-red-500"></div>
                                                        <span className="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                                            {conflict.plugin}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </Card>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}; 