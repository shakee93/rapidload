import Card from "@/components/ui/card";
import {PlusCircleIcon, MinusCircleIcon, CheckCircleIcon} from "@heroicons/react/24/solid";
import React, {useState, useRef, useEffect, forwardRef, useMemo} from "react";
import PerformanceIcons from '../performance-widgets/PerformanceIcons';
import 'react-json-view-lite/dist/index.css';
import AuditContent from "app/page-optimizer/components/audit/content";
import {JsonView} from "react-json-view-lite";
import {useSelector} from "react-redux";
import {optimizerData} from "../../../../store/app/appSelector";
import {AuditComponentRef} from "app/page-optimizer";
import TooltipText from "components/ui/tooltip-text";
import {useAppContext} from "../../../../context/app";
import {cn, isDev} from "lib/utils";
import {Cog6ToothIcon, HandRaisedIcon, InformationCircleIcon} from "@heroicons/react/20/solid";
import {AnimatePresence, m} from "framer-motion";
import {Accordion} from "components/accordion";
import Settings from "app/page-optimizer/components/audit/Settings";


import {
    HoverCard,
    HoverCardContent,
    HoverCardTrigger,
} from "@/components/ui/hover-card"
import {toast} from "components/ui/use-toast";
import metrics from "app/page-optimizer/components/performance-widgets/Metrics";

export interface AuditProps {
    audit: Audit;
    index?: number;
    actions?: boolean
    metrics?: boolean
}

const Audit = forwardRef<AuditComponentRef, AuditProps>(({audit, index, actions = true, metrics = true }, ref) => {

    // const [toggleFiles, setToggleFiles] = useState(false);
    // const shouldOpen = index === 0 && ['opportunities', 'diagnostics'].includes(activeTab)  && (audit?.files?.items?.length > 0 || audit?.settings.length > 0)
    const [toggleFiles, setToggleFiles] = useState( false);

    const {settings, activeReport, data} = useSelector(optimizerData);
    const divRef = useRef<HTMLDivElement>(null);
    const {openAudits, setOpenAudits} = useAppContext()

    const [showJson, setShowJson] = useState<boolean>(false)
    const [filesMounted, setFilesMounted] = useState(false)
    const filesOrActions = (audit.files?.items?.length > 0 || audit.settings.length > 0)


    if (!audit?.id) {
        return <></>;
    }

    let icon = audit.icon

    if (audit.type === 'passed_audit') {
        icon = 'pass'
    }

    useEffect(() => {

        // setOpenAudits(prevOpenAudits => {
        //     if (toggleFiles) {
        //         // Add if not present
        //         if (!prevOpenAudits.includes(audit.id)) {
        //             return [...prevOpenAudits, audit.id];
        //         }
        //     } else {
        //         // Remove if present
        //         return prevOpenAudits.filter(id => id !== audit.id);
        //     }
        //
        //     return prevOpenAudits; // Return the array unchanged if no action is taken
        // });

    }, [toggleFiles, activeReport]);



    const summary = () => {
        const numItems = audit.files?.items?.length || 0;
        const numSettings = audit.settings.length || 0;

        if (numItems === 0 && numSettings === 0) {
            return '';
        }

        let summaryText = ``;

        if (numSettings > 0) {
            summaryText += ` ${numSettings} ${numSettings === 1 ? 'Action' : 'Actions'}`;
        }
        if (numItems > 0) {
            if (numSettings > 0) {
                summaryText += ', ';
            }


            summaryText += ` ${numItems} ${numItems === 1 ? 'Resource' : 'Resources'}`;
        }

        return `${summaryText}`;
    };

    const activeSettings = useMemo(() => (audit.settings.filter(s => s.inputs[0].value)), [audit.settings])
    const totalMetricsGain = useMemo(() => audit.metrics.reduce((total,b) => b.potentialGain + total, 0), [audit.metrics])

    return (
        <Card spreader={(!!audit?.files?.items?.length) && !toggleFiles} ref={divRef}
              className={cn(
                  `overflow-hidden hover:opacity-100 w-full flex justify-center flex-col items-center p-0`,
                  toggleFiles ? 'shadow-xl dark:shadow-brand-800/70' : 'dark:hover:border-brand-500 hover:border-brand-400/60'
              )}
        >
            <div className={cn(
                'min-h-[56px] relative flex justify-between w-full py-2 px-4',
            )}>
                <div className='flex gap-3 font-normal  items-center text-base'>

                    <div
                        className={`inline-flex items-center justify-center w-7 h-7 rounded-full dark:bg-brand-700 bg-brand-100`}>
                        {audit.scoreDisplayMode === 'informative' ? <span className='w-3 h-3 border-2 rounded-full'></span> : <PerformanceIcons icon={icon}/> }
                    </div>

                    <div className='flex flex-col justify-around'>
                        <div className='flex gap-1.5 items-center'>
                            {audit.name}
                            {/*<span className='text-xxs leading-tight border border-purple-300 rounded-2xl py-0.5 px-2 bg-purple-100'>*/}
                            {/*    2 changes*/}
                            {/*</span>*/}

                            {metrics && audit.metrics && (
                                <div className='flex gap-1.5 text-xxs'>
                                    {audit.metrics.map(metric => (
                                        <>
                                            {
                                                ((audit.type !== 'passed_audit' || audit.scoreDisplayMode !== 'informative') && metric.potentialGain > 0) ?
                                                    <TooltipText asChild
                                                                 text={
                                                                     (audit.type !== 'passed_audit' && audit.scoreDisplayMode !== 'informative' && metric.potentialGain > 0) && `Potential +${metric.potentialGain.toFixed(0)} Score Boost`
                                                                 }>
                                                        <div className={cn(
                                                            'transition-colors flex gap-1 cursor-default hover:bg-brand-100 dark:hover:bg-brand-800 border py-1 px-1.5 rounded-md',
                                                        )} key={metric.id}>
                                                            {metric.refs.acronym}
                                                            {(audit.type !== 'passed_audit' && audit.scoreDisplayMode !== 'informative' && metric.potentialGain > 0) && (
                                                                <>
                                                                    {metric.potentialGain >= 0.5 && (
                                                                        <span className={cn(
                                                                            'text-green-800',
                                                                        )}> +{metric.potentialGain.toFixed(0)}</span>
                                                                    )}
                                                                </>
                                                            )}
                                                        </div>
                                                    </TooltipText>
                                                    :
                                                    <div className={cn(
                                                        'transition-colors flex gap-1 cursor-default hover:bg-brand-100 dark:hover:bg-brand-800 border py-1 px-1.5 rounded-md',
                                                    )} key={metric.id}>
                                                        {metric.refs.acronym}
                                                        <>
                                                            {metric.potentialGain >= 0.5 && (
                                                                <span className={cn(
                                                                    'text-green-800',
                                                                )}> +{metric.potentialGain.toFixed(0)}</span>
                                                            )}
                                                        </>
                                                    </div>
                                            }
                                        </>


                                    ))}

                                    {/*{totalMetricsGain ? (*/}
                                    {/*    <div className='flex group gap-2'>*/}
                                    {/*        <div className='flex gap-1 cursor-default text-green-800 hover:bg-brand-100 border py-1 px-1.5 rounded-md'>*/}
                                    {/*            {totalMetricsGain.toFixed(0)}+*/}
                                    {/*        </div>*/}
                                    {/*        {audit.metrics.map(metric => (*/}
                                    {/*            <div className={cn(*/}
                                    {/*                'group-hover:flex hidden transition-colors gap-1 cursor-default hover:bg-brand-100 border py-1 px-1.5 rounded-md',*/}
                                    {/*            )} key={metric.id}>*/}
                                    {/*                {metric.refs.acronym}*/}
                                    {/*                {(audit.type !== 'passed_audit' && audit.scoreDisplayMode !== 'informative' && metric.potentialGain > 0) && (*/}
                                    {/*                    <div key={index}>*/}
                                    {/*                        {metric.potentialGain > 0 && (*/}
                                    {/*                            <span className={cn(*/}
                                    {/*                                'text-green-800',*/}
                                    {/*                            )}> +{metric.potentialGain.toFixed(0)}</span>*/}
                                    {/*                        )}*/}
                                    {/*                    </div>*/}
                                    {/*                )}*/}
                                    {/*            </div>*/}
                                    {/*        ))}*/}
                                    {/*    </div>*/}
                                    {/*) : ''}*/}

                                    </div>
                            )}


                            {(activeSettings.length > 0 && !toggleFiles) && (
                                <TooltipText delay={0} text={
                                    <span className='text-sm text-center'>
                                             {activeSettings.length} Active Action{activeSettings.length > 1 ? 's' : ''}
                                         </span>
                                } className='flex flex-col border gap-2 bg-white dark:bg-brand-950 rounded-lg py-2 px-2'>
                                    <div
                                        onClick={() => setToggleFiles(prev => !prev)}
                                        className={cn(
                                            'cursor-pointer select-none text-xs text-brand-700 dark:text-brand-500 hover:bg-brand-100 dark:hover:bg-brand-800 transition-colors items-center flex gap-1.5 rounded-2xl',
                                            activeSettings.length > 1 && 'border py-0.5 px-2'
                                        )}>
                                        {activeSettings.length > 1 ? activeSettings.length : ''}
                                        <div className='bg-blue-500 w-1.5 h-1.5 shadow-lg rounded-full -right-1 -top-1'></div>
                                    </div>
                                </TooltipText>
                            )}



                        </div>
                        <span className='flex text-xxs leading-tight opacity-70'>
                             {audit.displayValue && (
                                 <span>{audit.displayValue}</span>
                             )}
                            {isDev && (<span> - {audit.id}</span>)}
                        </span>
                    </div>

                </div>

                <div className='flex gap-4 items-center'>

                    { (audit.files?.items?.length > 0 || audit.settings.length > 0) && (
                        <div className='text-xs opacity-50'>
                            {summary()}
                        </div>
                    )}


                    {actions &&
                        <TooltipText
                            text={filesOrActions ? 'Show resources and actions' : 'Learn more about this audit'}
                        >
                            <div onClick={() => setToggleFiles(prev => !prev)}
                                 className={`min-w-[125px] cursor-pointer flex items-center gap-2 pl-4 pr-2 py-1.5 text-sm rounded-2xl dark:hover:bg-brand-800 hover:bg-brand-100 transition-colors ${toggleFiles ? ' dark:bg-brand-900 border ': 'border '}`}>

                                {filesOrActions ? (
                                    toggleFiles ? 'Hide Actions' : 'Show Actions'
                                ) : 'Learn More'}


                                {filesOrActions ? (
                                    (toggleFiles) ?
                                        <MinusCircleIcon className='w-6 h-6 dark:text-brand-500 text-brand-900'/> :
                                        <PlusCircleIcon className='w-6 h-6 dark:text-brand-500 text-brand-900'/>
                                ) : (
                                    (toggleFiles) ?
                                        <MinusCircleIcon className='w-6 h-6 dark:text-brand-500 text-brand-900'/> :
                                        <InformationCircleIcon className='w-6 h-6 dark:text-brand-500 text-brand-900'/>
                                )}
                            </div>
                        </TooltipText>
                    }


                </div>



            </div>

            {showJson && (
                <div className='w-full p-4'>
                    <JsonView data={audit} shouldInitiallyExpand={(level) => false} />
                </div>
            )}

            <Accordion

                initialRender={true}
                isOpen={toggleFiles}>
                <AuditContent audit={audit} />
            </Accordion>

        </Card>
    );
})

export default React.memo(Audit)
