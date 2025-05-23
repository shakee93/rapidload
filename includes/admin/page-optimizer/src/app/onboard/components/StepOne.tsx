import React, { useState, useEffect } from 'react';
import { cn } from "lib/utils";
import { useAppContext } from "../../../context/app";
import { BoltIcon, ExclamationTriangleIcon , InformationCircleIcon } from "@heroicons/react/24/solid";
import TooltipText from "components/ui/tooltip-text";
import { changeReport, fetchReport, getAiPrediction } from "../../../store/app/appActions";
import { ArrowLongRightIcon } from "@heroicons/react/24/outline";
import { Loader, Monitor, RefreshCw } from "lucide-react";
import useCommonDispatch from "hooks/useCommonDispatch";
import { useSelector, useDispatch } from "react-redux";
import { optimizerData } from "../../../store/app/appSelector";
import PerformanceProgressBar from "components/performance-progress-bar";
import usePerformanceColors from "hooks/usePerformanceColors";
import { AIButtonIcon } from "app/onboard/components/icons/icon-svg";
import { Skeleton } from "components/ui/skeleton";
import { setCommonRootState } from '../../../store/common/commonActions';
import ErrorFetch from 'components/ErrorFetch';
import AppButton from "components/ui/app-button";
import { AnimatedLogo } from 'components/animated-logo';
import { FETCH_REPORT_FAILURE, AppAction, RootState } from '../../../store/app/appTypes';
import { ThunkDispatch } from 'redux-thunk';

interface StepOneProps {
    onNext: () => void;
}

interface AIPredictionResult {
    success: boolean;
    data?: any;
    error?: string;
}

const StepOne: React.FC<StepOneProps> = ({ onNext }) => {
    const { options } = useAppContext()
    const { dispatch } = useCommonDispatch()
    const appDispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const { activeReport, data, error, loading } = useSelector(optimizerData);
    const [predictedLoading, setPredictedLoading] = useState(true)
    const [aiPredictionResult, setAiPredictionResult] = useState<AIPredictionResult | null>(null)
    const [aiPrediction, setAiPrediction] = useState<any>(null)
    const [performanceScore, setPerformanceScore] = useState(95)
    const [aiPredictionError, setAiPredictionError] = useState<string | null>(null)
    const [countdown, setCountdown] = useState(5);
    const [showOptimizedScore, setShowOptimizedScore] = useState(false);
    const [text, setText] = useState('Rapidload AI');
    const [animate, setAnimate] = useState(true); 

    useEffect(() => {
        if(predictedLoading && !error && !loading){
        const interval = setInterval(() => {
          setAnimate(false); 
          setTimeout(() => {
            setText((prevText) => (prevText === 'Rapidload AI' ? 'Analyzing' : 'Rapidload AI'));
            setAnimate(true);
          }, 30); 
        }, 3000);
    
        return () => clearInterval(interval);
    }
      }, [predictedLoading, error, loading]);


    useEffect(() => {
        if (!loading && data) {
            if (data.performance === 0) {
                appDispatch({ type: FETCH_REPORT_FAILURE, error: 'Unable to analyze performance. Please try again.' });
                return;
            }
            // Short delay before showing optimized score
            setTimeout(() => {
                setShowOptimizedScore(true);
            }, 500);
        }
    }, [loading, data]);

    useEffect(() => {
        if (error) {
            const timer = setInterval(() => {
                setCountdown((prev) => {
                    if (prev <= 1) {
                        clearInterval(timer);
                        onNext();
                        return 0;
                    }
                    return prev - 1;
                });
            }, 1000);
    
            return () => clearInterval(timer);
        }
    }, [error, onNext]);

    useEffect(() => {
        data && dispatch(getAiPrediction(options, options.optimizer_url, data.performance, data.grouped, data.metrics))
            .then(response => {

                setAiPredictionResult(response)
                setAiPrediction(response.data)
                setPredictedLoading(false);
            })
            .catch(error => {
                setAiPredictionError('Error in AI Prediction: ' + error)
                setPredictedLoading(false);
            });
    }, [data])

    useEffect(() => {
        if (aiPredictionResult && aiPredictionResult.success) {
            setPerformanceScore(aiPredictionResult.data.predictedScore.predictedPerformanceScore)
            dispatch(setCommonRootState('aiPredictionResult', aiPredictionResult.data))
        }
    }, [aiPredictionResult])

    return (
        <div className='w-full flex flex-col gap-4'>
            <div className="flex flex-col gap-8 p-16 items-center rounded-3xl">
                <div className='px-2'>
                    <img className='w-22'
                        src={options?.page_optimizer_base ? (options?.page_optimizer_base + `/rapidload-logo.svg`) : '/rapidload-logo.svg'}
                        alt='RapidLoad - #1 to unlock breakneck page speed' />
                </div>
                <div className='flex flex-col gap-2 text-center'>
                    <h1 className='text-4xl font-bold'>Welcome to RapidLoad</h1>
                    <span className='font-medium text-base text-zinc-600 dark:text-brand-300'>
                        We have analyzed your entire site and this is the current results.
                    </span>
                </div>


                <div className='border rounded-xl p-2 flex items-center gap-4'>
                    <div className='p-2 bg-brand-200/60 rounded-lg dark:bg-brand-800'>
                        <InformationCircleIcon className='w-10 h-10 text-purple-700/90 dark:text-brand-300' />
                    </div>
                    <span className='font-medium text-xs text-zinc-600 dark:text-brand-300'>
                        Your website ({options?.optimizer_url}) uses caching. We bypass it to <br /> reveal the unoptimized performance, showing RapidLoad's full <br /> impact.
                    </span>
                </div>
                

            {error ? (
                <>
                    <div className='border-2 border-red-500 rounded-xl flex items-start gap-4 p-4'>
                        <div className='p-2 bg-red-200/30 rounded-lg'>
                            <ExclamationTriangleIcon className='w-10 h-10 text-red-500' />
                        </div>
                        <span className='font-medium text-zinc-600 dark:text-brand-300'>
                            <ErrorFetch className={"max-w-[420px] text-sm gap-0  mb-0"} error={error} icon={false}></ErrorFetch>
                        </span>
                    </div>
                    
                    <div>Redirecting in {countdown} seconds...</div>
                    <div className="flex gap-8 items-center">
                        {/* <button
                            className="items-center bg-brand-200 text-brand-950 hover:shadow-[inset_0_0_0_2px_rgba(0,0,0,1)] font-medium py-2 px-4 rounded-lg hover:bg-transparent transition-all gap-2"
                                onClick={() => {
                                    window.open('https://rapidload.zendesk.com/hc/en-us/requests/new', '_blank');
                            }}
                        >
                            Contact Support
                        </button> */}
                    
                        <button
                            className="items-center flex gap-2 hover:bg-gradient-to-br hover:from-[rgba(94,92,92,0.55)]  hover:to-brand-900/90 bg-brand-900/90  text-white font-medium py-2 px-4 rounded-lg transition-all gap-2"
                                onClick={() => {
                                    dispatch(fetchReport(options, options.optimizer_url, true))
                                }}
                            >
                                <RefreshCw className="w-4 text-brand-0" />
                                Refresh to Retry
                        </button>

                        
                    </div>
                </>
                ):(
                <>
                    <div className='bg-brand-0 border rounded-3xl p-2 dark:bg-brand-800'>
                        <div
                            className="flex items-center justify-center text-md gap-2 overflow-hidden relative">
                            {/* <div className="flex justify-center px-4 py-2 max-w-xl mx-auto w-full relative"> */}
                            <div className={cn(
                                "flex justify-center px-4 py-2 max-w-xl mx-auto w-full relative transition-all duration-500",
                                !showOptimizedScore && "justify-center min-w-[450px]",
                                showOptimizedScore && "justify-between"
                            )}>
                            {/* Before Results */}

                            {/* <div className="flex flex-col items-center gap-4 px-6 rounded-2xl w-[230px]"> */}
                            <div className={cn(
                                "flex flex-col items-center gap-4 px-6 rounded-2xl w-[230px] transition-all duration-500",
                                showOptimizedScore ? "slide-out-from-center" : "" // Center when alone
                                )}>
                                <div className="">
                                    {error ?
                                        <Skeleton className="w-44 h-44 rounded-full" />
                                        :
                                        <PerformanceProgressBar
                                            className={cn('max-h-44')}
                                             performance={data ? data?.performance : 90}
                                            loading={loading}
                                        />
                                    }

                                </div>
                                <div className="text-sm font-semibold border rounded-3xl p-2 py-1 dark:border-brand-600 dark:border">Your Current Score
                                </div>
                            </div>


                            {/* Optimized Score */}
                            {/* <div className="flex flex-col items-center gap-4 px-6 rounded-2xl w-[230px]"> */}
                            {showOptimizedScore && (
                                <div className={cn(
                                    "flex flex-col items-center gap-4 px-6 rounded-2xl w-[230px] transition-all duration-500",
                                    "slide-in-from-center"
                                )}>

                                <div className="">
                                    {predictedLoading || error || loading ? (


                                        <div
                                            className="bg-gradient-to-r from-purple-100 via-purple-60 to-purple-100/10 dark:bg-gradient-to-r dark:from-brand-900/90 dark:via-brand-900/90 dark:to-brand-900/90 relative flex items-center justify-center w-44 h-44 rounded-full"
                                        >
                                            <div
                                                className="flex flex-col items-center justify-center text-center text-brand-950 dark:text-brand-300">
                                                <AnimatedLogo size="xl" isPlaying={predictedLoading} />
                                                <div className='text-[10px] bg-[#7F54B3] px-1.5 py-[2px] rounded-[5px] mt-4 tracking-widest text-white uppercase'>
                                                <p className={`typing-text ${animate ? 'animate' : ''}`}>
                                                    {text}
                                                </p>
                                                </div>
                                            
                                            </div>
                                            
                                        </div>

                                    ) : (
                                        <PerformanceProgressBar
                                            className={cn('max-h-44')}
                                            scoreClassName={"text-brand-950"}
                                            performance={performanceScore}
                                        />
                                        
                                        
                                    )}

                                </div>
                                <div
                                    className="items-center gap-1 text-sm font-semibold bg-brand-200/60 rounded-3xl p-2 py-1 flex dark:bg-brand-800 dark:border-brand-600 dark:border">
                                    {predictedLoading || error || loading ?
                                        (
                                            <><Loader className='w-4 animate-spin' /> Hold on tight</>
                                        ) : aiPredictionError && aiPredictionError?.length > 0 ? (
                                            <>
                                            <TooltipText text={aiPredictionError}>
                                                <ExclamationTriangleIcon className='w-4 text-red-500 cursor-pointer' /> Error in AI Prediction</TooltipText>
                                            </>
                                        ) : (
                                            <><AnimatedLogo className="!opacity-100" size="sm" isPlaying={false} /> AI Predicted Score</>
                                        )
                                    }
                                </div>
                            </div>
                            )}
                            </div>
                            
                        </div>
                    </div>

                    <button
                                className={cn('flex items-center bg-gradient-to-r from-brand-900/90 to-brand-950 text-white font-medium py-2 px-4 rounded-lg hover:bg-gray-700 transition-all gap-2 hover:bg-gradient-to-br hover:from-[rgba(94,92,92,0.55)]  hover:to-brand-900/90 bg-brand-900/90 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 dark:text-brand-300',
                                predictedLoading  && 'pointer-events-none cursor-default opacity-30')}
                                onClick={onNext}
                            >
                                Let's improve this score
                                <ArrowLongRightIcon className="w-6 h-6" />
                            </button>
                    </>
                    )
                }

                    
               
                
                

            </div>
        </div>
    );
};

export default StepOne;
