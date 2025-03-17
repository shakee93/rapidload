import React, {useEffect, useMemo} from "react";
import {useAppContext} from "../../../context/app";
import TogglePerformance from "components/toggle-performance";
import {useSelector} from "react-redux";
import {optimizerData} from "../../../store/app/appSelector";
import Audit from "app/page-optimizer/components/audit/Audit";
import useCommonDispatch from "hooks/useCommonDispatch";
import Description from "app/page-optimizer/components/audit/Description";
import {Circle, Dot} from "lucide-react";
import {Separator} from "@radix-ui/react-dropdown-menu";
import {m} from "framer-motion";
import SlideUp from "components/animation/SlideUp";
import PerformanceIcons from "app/page-optimizer/components/performance-widgets/PerformanceIcons";
import usePerformanceColors from "hooks/usePerformanceColors";


const Metrics = () => {
    const {data, loading, error, activeReport} = useSelector(optimizerData);
    const { activeMetric } = useCommonDispatch()
    const [ icon, color ] = usePerformanceColors(activeMetric?.score)

    const {
        togglePerformance,
        options
    } = useAppContext()


    const audits = useMemo(() => {

        let audits = [
            ...data?.grouped?.opportunities || [],
            ...data?.grouped?.diagnostics || [],
        ]

        if (activeMetric?.refs.acronym === 'SI') {
            return  audits.filter(audit => audit.scoreDisplayMode !== 'informative')
        }

        return audits.filter(audit => audit.metrics.find(metric => metric.id === activeMetric?.id));
    }, [data, activeMetric])

    const points = useMemo(() => {

        let unit = activeMetric?.refs.acronym !== 'CLS' ? 'ms' : ''
        return [
            activeMetric?.potentialGain ? <>Potential gain <span className='text-brand-800 dark:text-brand-500 font-medium'>{activeMetric?.potentialGain.toFixed(0)} points</span> </> : null,
            <>Weighs <span className='text-brand-800 dark:text-brand-500 font-medium'>{activeMetric?.refs.weight}%</span> of your page speed score</>,
        ]
    }, [activeMetric, activeReport])

    type Metrics = {
        [key: string]: {
            description: string;
            learn_more: string;
        };
    };


    const metricRanges: Metrics = {
        TBT : {
            description: `Imagine you're reading a storybook and there's a sticker covering some words. TBT, or Total Blocking Time, is like the time you wait for someone to remove that sticker. In websites, it's the time you wait before you can play or interact with the site. The shorter the wait, the better! `,
            learn_more: `https://web.dev/tbt/`
        },
        LCP : {
            description: `Imagine you're waiting for a picture in your storybook to appear. LCP, or Largest Contentful Paint, is like the time it takes for the biggest and most important picture in the story to fully show up. On websites, it's how long you wait to see the main part of the page. The faster it appears, the better the website is at showing you its main content quickly!`,
            learn_more: `https://web.dev/lcp/`
        },
        FCP : {
            description: `Imagine opening a storybook, eager for the first picture. FCP, or First Contentful Paint, is the time it takes for that first image or word to appear. On websites, it's about how swiftly the starting content loads. The faster it appears, the sooner the website's story begins for you!`,
            learn_more: `https://web.dev/fcp/`
        },
        SI : {
            description: `Imagine cookies baking. SI, or Speed Index, measures how fast most cookies are ready. On websites, it's how quickly main content appears. Faster cookies (or content) mean a better experience!`,
            learn_more: `https://web.dev/si/`
        },
        CLS : {
            description: `While building a puzzle, imagine pieces suddenly shifting on their own. Confusing, right? CLS, or Cumulative Layout Shift, is like that for websites. It's when page elements move unexpectedly. The less they shift, the better the experience!`,
            learn_more: `https://web.dev/cls/`
        },
    }


    interface Metric {
        good: number;
        poor: number;
    }

    interface PerformanceMetrics {
        LCP: Metric;
        FID: Metric;
        CLS: Metric;
        FCP: Metric;
        SI: Metric;
        TBT: Metric;
    }

    interface DevicePerformance {
        mobile: PerformanceMetrics;
        desktop: PerformanceMetrics;
    }

    const metricPoorGood: DevicePerformance = {
        mobile: {
            LCP: {
                good: 2500,
                poor: 4000
            },
            FID: {
                good: 100,
                poor: 300
            },
            CLS: {
                good: 0.1,
                poor: 0.25
            },
            FCP: {
                good: 1800,
                poor: 3000
            },
            SI: {
                good: 3400,
                poor: 5800
            },
            TBT: {
                good: 300,
                poor: 600
            }
        },
        desktop: {
            LCP: {
                good: 2000,
                poor: 4000
            },
            FID: {
                good: 100,
                poor: 300
            },
            CLS: {
                good: 0.1,
                poor: 0.25
            },
            FCP: {
                good: 1000,
                poor: 3000
            },
            SI: {
                good: 1300,
                poor: 2500
            },
            TBT: {
                good: 200,
                poor: 600
            }
        }
    }


    if (!activeMetric) {
        return <></>
    }

    return (
        <SlideUp uuid={activeMetric?.id ? activeMetric.id : 'no-key'}>

            <div className='ml-6'>
                {!togglePerformance && <TogglePerformance/>}
            </div>
            <div className='flex flex-col gap-3 lg:mt-16 ml-6'>
                <div className='flex flex-col gap-3 border-b pb-6'>
                    <div className='text-4xl font-medium'> {activeMetric?.title}</div>
                    {/*<div><Description className='pl-0 text-md' content={activeMetric?.description}/></div>*/}
                    <div className='dark:text-brand-300 text-brand-600 text-md '>
                        <span>{metricRanges[activeMetric.refs.acronym].description}</span> <a target='_blank' className='text-purple-750' href={metricRanges[activeMetric?.refs.acronym].learn_more} >Learn more</a>
                    </div>
                    <div>
                        <ul className='flex ml-2 items-center text-sm gap-3 text-brand-500 dark:text-brand-400'>
                            <li className='flex items-center gap-1.5'>
                                <Circle style={{
                                    fill:  usePerformanceColors(activeMetric?.score)[1]
                                }} className='w-3 stroke-none mt-[1px] dark:fill-brand-700'/> <span>
                                {activeMetric?.icon === 'pass' &&
                                    <>Great! You kept it below <span className='text-brand-800 dark:text-brand-500 font-medium'>{
                                        //@ts-ignore
                                        metricPoorGood[activeReport][activeMetric?.refs.acronym].good}{activeMetric.refs.acronym !== 'CLS' && 'ms'}</span>
                                </>}
                                {(activeMetric?.icon === 'fail' || activeMetric?.icon === 'average') &&
                                    <>Should be below <span className='text-brand-800 dark:text-brand-500 font-medium'>{
                                        //@ts-ignore
                                        metricPoorGood[activeReport][activeMetric?.refs.acronym].good}{activeMetric.refs.acronym !== 'CLS' && 'ms'}</span>
                                </>}
                            </span>
                            </li>
                            {points.map((point, index) => (
                                point && (<li key={index} className='group flex gap-1.5 items-center'>
                                    <Circle className='w-3 stroke-none mt-[1px] group-last:fill-brand-300 fill-green-600 :fill-brand-300 dark:fill-green-700'/> <span>{point}</span>
                                </li>)
                            ))}

                        </ul>
                    </div>
                </div>
                {audits.length > 0 ?
                    <div className="flex gap-4 flex-col w-full">
                        <div className='borderx-b px-4 w-full font-medium text-lg'>Related Audits</div>
                        <div className='flex flex-col gap-4'>
                            {audits.map((audit, index) => (
                                <div  key={index} className='relative'>
                                    <Audit
                                        metrics={false}
                                        index={index} audit={audit}/>
                                </div>
                            ))}
                        </div>
                    </div>
                : <m.div
                        initial={{opacity: 0, y: 10}}
                        animate={{opacity: 1, y: 0}}
                        exit={{opacity: 0, y: -20}}
                        className='flex flex-col gap-8 items-center px-8 pt-40 pb-20 w-full'>

                        <div>
                            <img alt='Good Job!' className='w-64' src={ options?.page_optimizer_base ? (options?.page_optimizer_base + `/success.svg`) : '/success.svg'}/>
                        </div>

                        <span className='flex gap-2'>
                                Brilliantly done! It's clear you've mastered this.
                            </span>

                    </m.div>
                }

            </div>
        </SlideUp>
    );
}

export default React.memo(Metrics)