import React, { useEffect } from 'react';
import { GaugeCircle } from 'lucide-react';
import { Skeleton } from "components/ui/skeleton";
import PerformanceProgressBar from "components/performance-progress-bar";
import { cn } from "lib/utils";
import { CircularProgressbarWithChildren } from "react-circular-progressbar";
import { BoltIcon } from "@heroicons/react/24/solid";
import usePerformanceColors from "hooks/usePerformanceColors";
import tinycolor from 'tinycolor2';
import useCommonDispatch from "hooks/useCommonDispatch";
import { getHomePagePerformance, updateLicense } from "../../../../store/app/appActions";
import { useAppContext } from "../../../../context/app";
import { useSelector } from "react-redux";
import { optimizerData } from "../../../../store/app/appSelector";

interface PerformanceWidgetProps {
    className: string;
}

const PerformanceWidget: React.FC<PerformanceWidgetProps> = ({ className }) => {

    const { homePerformance } = useSelector(optimizerData);
    const performanceScore = homePerformance.last_entry;
    const [performanceIcon, progressbarColor, progressbarBg] = usePerformanceColors(performanceScore);
    const lighterColor = tinycolor(progressbarBg).lighten(10).toString();
    const { dispatch } = useCommonDispatch()
    const { options } = useAppContext();


    useEffect(() => {
        dispatch(getHomePagePerformance(options));

    }, [dispatch]);

    useEffect(() => {
        //console.log(homePerformance)

    }, [homePerformance]);

    return (

        <div className={cn("flex flex-col gap-2 ", className)}>
           
            <div
                className="flex items-center justify-center text-md gap-2 bg-white dark:bg-brand-800 rounded-t-3xl border border-b-0 w-full overflow-hidden relative">
                <div className="flex justify-center p-4 max-w-xl mx-auto w-full relative ">
                    {/* Before Results */}


                    <div className="flex flex-col items-center gap-2 px-10 py-4 rounded-2xl w-[230px]" >
                        <div className="text-lg font-semibold">{homePerformance.last_entry && homePerformance.last_entry > homePerformance.first_entry ? 'Before Results' : 'Current Score'}</div>
                        <div className="">
                            {homePerformance.first_entry < 1 ? (
                                <Skeleton className="w-[140px] h-[140px] rounded-full" />
                            ) : (
                                <PerformanceProgressBar
                                    className={cn('max-h-[140px]')}
                                    background={false}
                                    stroke={6}
                                    performance={homePerformance.first_entry}
                                />
                            )}

                        </div>
                        <div className="text-sm text-gray-600">
                            {homePerformance.first_response_time == "0" ? (
                                <Skeleton className="w-[140px] h-[20px] rounded-lg" />
                            ) : (
                                <div>
                                    Loading time: {homePerformance.first_response_time}
                                </div>
                            )}
                        </div>
                    </div>


                    {homePerformance.last_entry && homePerformance.last_entry > homePerformance.first_entry ? <>
                        {/* Divider with BoltIcon */}
                        <div className={`absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 p-2 rounded-full text-white z-10`}
                            style={{ background: `linear-gradient(to right, ${progressbarColor}, ${lighterColor})` }}
                        >
                            <BoltIcon className="h-6 w-6 text-white" />
                        </div>

                        {/* Optimized Score */}
                        {/*<div className="flex flex-col items-center gap-2 px-10 py-4 rounded-2xl w-[230px]"*/}
                        {/*     style={{ background: progressbarBg}}>*/}
                        <div className="flex flex-col items-center gap-2 px-10 py-4 rounded-2xl w-[230px]">
                            <div className="text-lg font-semibold">Optimized Score</div>
                            <div className="">
                                {homePerformance.last_entry < 1 ? (
                                    <Skeleton className="w-[140px] h-[140px] rounded-full" />
                                ) : (
                                    <PerformanceProgressBar
                                        className={cn('max-h-[140px]')}
                                        scoreClassName={"text-brand-950"}
                                        background={false}
                                        stroke={6}
                                        performance={homePerformance.last_entry}
                                    />
                                )}
                            </div>
                            <div className="text-sm text-gray-600">
                                {homePerformance.last_response_time == "0" ? (
                                    <Skeleton className="w-[140px] h-[20px] rounded-lg" />
                                ) : (
                                    <div>
                                        Loading time: {homePerformance.last_response_time}
                                    </div>
                                )}
                            </div>
                        </div>
                    </> : <></>}




                </div>
            </div>


        </div>
    );
};

export default React.memo(PerformanceWidget);