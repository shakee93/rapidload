import React, { ReactNode, Suspense, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import PerformanceIcons from 'app/page-optimizer/components/performance-widgets/PerformanceIcons';
import { useSelector } from "react-redux";
import { optimizerData } from "../../../../store/app/appSelector";
import { useAppContext } from "../../../../context/app";
import { Skeleton } from "components/ui/skeleton"
import { cn, timeAgo } from "lib/utils";
import Card from "components/ui/card";
import PerformanceProgressBar from "components/performance-progress-bar";
import Metrics from "app/page-optimizer/components/performance-widgets/Metrics";
import useCommonDispatch from "hooks/useCommonDispatch";
import { setCommonRootState, setCommonState } from "../../../../store/common/commonActions";
import {
    Circle, GraduationCapIcon,
    Hash, History,
} from "lucide-react";
import SideBarActions from "app/page-optimizer/components/performance-widgets/SideBarActions";
import xusePerformanceColors from "hooks/usePerformanceColors";
import AppButton from "components/ui/app-button";



const Feedback = React.lazy(() =>
    import('app/page-optimizer/components/performance-widgets/Feedback'))

interface PageSpeedScoreProps {
    pagespeed?: any;
    priority?: boolean;
    dashboardMode?: boolean;
}

const MetricValue = ({ metric }: { metric: Metric }) => {
    const [x, y, z, progressBarColorCode] = xusePerformanceColors(metric.score)

    return <div
        style={{
            color: y || '#515151'
        }}
        className='text-md font-medium text-brand-500'>
        {metric.displayValue}
    </div>
}


const PageSpeedWidget = ({ pagespeed, priority = true, dashboardMode = false }: PageSpeedScoreProps) => {
    const [isCoreWebClicked, setCoreWebIsClicked] = useState(false);
    const [expanded, setExpanded] = useState(false)



    const { data, error, loading, revisions } = useSelector(optimizerData);
    const [performance, setPerformance] = useState<number>(0)
    const [on, setOn] = useState<boolean>(false)

    const { dispatch, hoveredMetric, activeMetric } = useCommonDispatch()

    const handleCoreWebClick = useCallback(() => {
        setCoreWebIsClicked(!isCoreWebClicked);
    }, [isCoreWebClicked]);

    // reorder experience start
    const metricNameMappings: Record<string, string> = {
        "LARGEST_CONTENTFUL_PAINT_MS": "LCP",
        "FIRST_INPUT_DELAY_MS": "FID",
        "CUMULATIVE_LAYOUT_SHIFT_SCORE": "CLS",
        "FIRST_CONTENTFUL_PAINT_MS": "FCP",
        "INTERACTION_TO_NEXT_PAINT": "INP",
        "EXPERIMENTAL_TIME_TO_FIRST_BYTE": "TTFB",

    };

    const experianceOrder: string[] = [
        "LARGEST_CONTENTFUL_PAINT_MS",
        "FIRST_INPUT_DELAY_MS",
        "CUMULATIVE_LAYOUT_SHIFT_SCORE",
        "FIRST_CONTENTFUL_PAINT_MS",
        "INTERACTION_TO_NEXT_PAINT",
        "EXPERIMENTAL_TIME_TO_FIRST_BYTE",

    ];

    const getAbbreviation = (metricName: string): string => metricNameMappings[metricName] || metricName;

    const sortedExperience = experianceOrder.map(metricName => ({
        metricName: getAbbreviation(metricName),
        metric: data?.loadingExperience?.metrics ? data?.loadingExperience?.metrics[metricName] : null,
    }));

    const FirstLettersComponent = ({ text }: { text: string }) => {
        const replacedText = getAbbreviation(text);
        return <>{replacedText}</>;
    };

    let metric = hoveredMetric

    let gain = Number((metric?.potentialGain ? metric?.potentialGain : 0)?.toFixed(0))

    const [key, setKey] = useState(1);

    useEffect(() => {
        setKey(prevKey => prevKey + 1);
    }, []);



    return <>

        <div className={cn('w-full flex flex-col gap-4 ', dashboardMode ? 'w-min min-w-[350px]' : '')}>
            <Card data-tour='speed-insights'
                className={cn(
                    'overflow-hidden  border-transparent flex flex-col sm:flex-row lg:flex-col justify-around dark:bg-brand-800',
                    expanded && 'border-brand-200 dark:border-brand-800',
                    dashboardMode ? '' : 'border'
                )}>
                <div
                    className="content flex w-full sm:w-1/2 lg:w-full flex-col justify-center items-center gap-3 px-4 lg:px-4 lg:pb-0 xl:px-8 py-2.5">

                    <div className='flex gap-6'>
                        <div className='flex flex-col gap-3 px-4 items-center'>
                            <div className={`${dashboardMode ? 'mt-3.5' : 'mt-6'}`}>
                                {loading || on ? (
                                    <Skeleton className="w-44 h-44 rounded-full" />
                                ) : (
                                    <PerformanceProgressBar

                                        performance={(data?.performance && gain && metric) ?
                                            (data.performance + gain >= 99) ? 99 :
                                                data.performance + gain : data?.performance}>
                                        {!!(metric && gain) && (
                                            <div className='flex gap-1 flex-col text-xxs font-normal'>
                                                <span>
                                                    {metric?.title}
                                                </span>
                                                <span className='text-sm text-green-600 -ml-1'>+{gain}</span>
                                            </div>
                                        )}
                                    </PerformanceProgressBar>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col text-center gap-1">
                        <div>{metric ? 'Forecasted Score' : 'Performance'} </div>
                        <div className='text-xs text-brand-500 dark:text-brand-300 font-light'>
                            Values are estimated and may vary with Google Page Speed Insights.
                        </div>
                    </div>
                    <div className={`flex justify-around text-sm gap-4 font-normal w-full text-brand-700 dark:text-brand-300 ${dashboardMode ? '' : 'mb-5 '}`}>
                        <div className="flex lg:flex-col xl:flex-row items-center gap-1">
                            <PerformanceIcons icon={'fail'} />
                            0-49
                        </div>
                        <div className="flex lg:flex-col xl:flex-row items-center gap-1">
                            <PerformanceIcons icon={'average'} />
                            50-89
                        </div>
                        <div className="flex lg:flex-col xl:flex-row items-center gap-1">
                            <PerformanceIcons icon={'pass'} />
                            89-100
                        </div>
                    </div>
                </div>

                {data?.metrics &&
                    <AppButton
                        onClick={e => setExpanded(p => !p)}
                        variant='outline' className='select-none border-none bg-transparent dark:bg-brand-950 hover:bg-transparent text-center text-xs text-brand-600 py-2'>
                        {expanded ? 'Collapse' : 'Expand'} Metrics
                    </AppButton>
                }


                {(data?.metrics && !expanded) && (
                    <>
                        <div className={`flex justify-around ${dashboardMode ? '' : 'mb-3 px-2 '}`}
                            onMouseLeave={() => dispatch(setCommonState('hoveredMetric', null))}
                        >
                            {data.metrics.map(metric => (
                                <div key={metric.id}
                                    onMouseEnter={() => dispatch(setCommonState('hoveredMetric', metric))}

                                    className='text-xs border text-center flex flex-col
                             gap-0.5 px-3 py-2 bg-brand-100/20 dark:hover:bg-brand-600 hover:bg-brand-100 cursor-default rounded-[14px]'>
                                    <div className='font-medium tracking-wider '>{metric.refs.acronym}</div>
                                    <MetricValue metric={metric} />
                                </div>
                            ))}
                        </div>

                    </>
                )}



                {(data?.metrics && expanded) && (
                    <div className={cn(
                        'sticky top-0 w-full sm:w-1/2 lg:w-full border-l lg:border-l-0'
                    )
                    } data-tour='metrics'>
                        <div
                            onClick={e => dispatch(setCommonState('activeMetric', null))}
                            className={cn(
                                'flex gap-3 items-center font-medium dark:hover:bg-brand-900/70  px-6 py-3 border-b lg:border-b-0 lg:border-t cursor-pointer text-sm',
                                !activeMetric && 'bg-brand-100/80 dark:bg-brand-900/80 '
                            )
                            }>
                            <span><Hash className='w-4 text-brand-400' /></span> All Audits
                        </div>
                        <Metrics performance={data?.performance} metrics={data.metrics} />
                    </div>
                )}
            </Card>

            {!dashboardMode && (
                <>
                    <SideBarActions />

                    <Suspense>
                        <Feedback key={key} />
                    </Suspense>
                </>
            )}


            {/*{data?.loadingExperience?.metrics && (*/}
            {/*    <Card>*/}
            {/*        <div onClick={handleCoreWebClick} className={`flex justify-around px-6 py-4 cursor-pointer`}>*/}
            {/*            <div>*/}
            {/*                <p className="">Real Experience (28 days)</p>*/}
            {/*                <p className="text-xs opacity-60">Real user experience from Google</p>*/}
            {/*            </div>*/}
            {/*            <CheckBadgeIcon className='w-[30px] h-[30px] ml-4 mt-1 text-green-600'/>*/}
            {/*        </div>*/}
            {/*        {isCoreWebClicked && (*/}
            {/*            <div className='border-t dark:border-zinc-700'>*/}

            {/*                <div className="p-5 grid grid-cols-3 gap-3 pl-6">*/}
            {/*                    {sortedExperience.map(({ metricName, metric }, index) => (*/}
            {/*                        <div key={index} className={`${index % 3 === 2 ? 'mb-4' : ''}`}>*/}
            {/*                            <div className="flex">*/}
            {/*                                <div className="grid grid-cols-2 gap-1.5 items-center justify-center">*/}
            {/*                                    <div><p className="text-xs font-medium">{<FirstLettersComponent text={metricName} />}</p></div>*/}
            {/*                                    <div><span*/}
            {/*                                        className={`inline-flex items-center justify-center w-6 h-6 rounded-full dark:bg-zinc-700 bg-zinc-100`}>*/}
            {/*                    <PerformanceIcons icon={metric?.category || 'average'}/>*/}
            {/*                </span></div>*/}
            {/*                                </div>*/}
            {/*                            </div>*/}
            {/*                            <p className="text-[22px] font-medium mr-2 mt-1 text-red">*/}
            {/*                                {["FID", "CLS", "INP"].includes(metricName) ? (*/}
            {/*                                    <>*/}
            {/*                                        {metric?.percentile}<span className="text-base"> ms</span>*/}
            {/*                                    </>*/}
            {/*                                ) : (*/}
            {/*                                    <>*/}
            {/*                                        {metric?.percentile*/}
            {/*                                            ? `${(metric.percentile / 1000).toFixed(2)}`*/}
            {/*                                            : ''}*/}
            {/*                                        <span className="text-base"> s</span>*/}
            {/*                                    </>*/}
            {/*                                )}*/}
            {/*                            </p>*/}

            {/*                        </div>*/}
            {/*                    ))}*/}


            {/*                </div>*/}

            {/*            </div>*/}
            {/*        )}*/}

            {/*    </Card>*/}
            {/*)}*/}

        </div>
    </>
}

export default React.memo(PageSpeedWidget)