import PerformanceIcons from "app/page-optimizer/components/performance-widgets/PerformanceIcons";
import Card from "components/ui/card";
import React from "react";
import {JsonView} from "react-json-view-lite";
import TooltipText from "components/ui/tooltip-text";
import usePerformanceColors from "hooks/usePerformanceColors";
import PerformanceProgressBar from "components/performance-progress-bar";
import {cn} from "lib/utils";
import {Info} from "lucide-react";
import {useAppContext} from "../../../../context/app";


interface MetricsProps {
    metrics: Metric[]
    performance: number
}

const Metrics = ({ metrics = [], performance } : MetricsProps) => {

    const { setActiveMetric, activeMetric } = useAppContext()

    const enter = (metric: Metric) => {
        setActiveMetric(metric)
    }

    const leave = () => {
        setActiveMetric(null)
    }

    return (
        <div>
            <div className="flex flex-col w-full">
                {metrics
                    .sort((a,b) => b.potentialGain - a.potentialGain)
                    .map((metric, index) => (
                    <div key={index}
                         onMouseEnter={e => enter(metric)}
                         onMouseLeave={e => leave()}
                         className='hover:bg-brand-50 transition-colors group flex flex-row justify-between items-center border-t px-6 py-2.5'>
                        <div className='flex flex-col justify-between'>
                            <div className='flex items-center gap-1.5 flex-row text-sm font-medium'>
                                <div className='inline-flex flex-col'>
                                    <span>
                                        {metric.title}
                                    </span>
                                    <span className='text-xxs text-brand-500 font-light'>

                                        {metric.potentialGain > 0 ?
                                            `Enhance this for ${ metric.potentialGain.toFixed(0) } point boost.` :
                                            `Looks great, well done!`}

                                    </span>
                                </div>

                                {/*<Info className='w-4 text-brand-400'/>*/}
                            </div>
                            <div
                                style={{
                                    color:  usePerformanceColors(metric.score)[1]
                                }}
                                className='text-lg font-medium text-brand-500'>
                                {metric.displayValue}
                            </div>
                        </div>
                        <div className={cn(
                            'opacity-70 group-hover:opacity-100 transition-opacity'
                        )}>
                            <PerformanceProgressBar
                                background={false}
                                stroke={10}
                                scoreClassName='text-[12px]'
                                className='h-9'
                                performance={metric.score}/>
                        </div>
                    </div>
                ))}
            </div>
            {/*<JsonView data={metrics}/>*/}
        </div>
    )
}

export default React.memo(Metrics)