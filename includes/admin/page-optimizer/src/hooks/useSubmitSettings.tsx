import React, {useCallback, useEffect} from "react";
import ApiService from "../services/api";
import {CheckCircleIcon, XCircleIcon} from "@heroicons/react/24/solid";
import {fetchReport, fetchSettings} from "../store/app/appActions";
import {useAppContext} from "../context/app";
import {useToast} from "components/ui/use-toast";
import {useDispatch, useSelector} from "react-redux";
import {optimizerData} from "../store/app/appSelector";
import {ThunkDispatch} from "redux-thunk";
import {AppAction, RootState} from "../store/app/appTypes";
import { compareVersions } from 'compare-versions';
import {setCommonState} from "../store/common/commonActions";
import useCommonDispatch from "hooks/useCommonDispatch";

const useSubmitSettings = () => {

    const {
        setShowOptimizer,
        options ,
        modeData,
        savingData,
        setSavingData,
        invalidatingCache,
        setInvalidatingCache,
        global,
    } = useAppContext()



    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const { settingsMode, headerUrl } = useCommonDispatch()

    const {
        fresh,
        touched,
        activeReport,
        data,
        settings,
        activeGear
    } =
        useSelector(optimizerData)

    //omit grouped from data and updateData
    const { grouped, ...restData } = data || {};
    const updatedData = {
        ...restData,
        settingsMode: settingsMode,
    };

    const url = headerUrl? headerUrl : options?.optimizer_url;
    const { toast } = useToast()


    const submitSettings = useCallback(async (analyze = false, global = false) => {

        if (savingData) {
            return;
        }

        const api = new ApiService(options);

        try {


            setSavingData(true);


            const res = await api.updateSettings(
                url,
                activeReport,
                {
                    settings,
                    activeGear
                },
                global,
                analyze,
            );

            toast({
                description: <div className='flex w-full gap-2 text-center'>Your settings have been saved successfully <CheckCircleIcon className='w-5 text-green-600'/></div>,
            })

            if (analyze) {
                setSavingData(false)

                const USER_AGENTS = {
                    mobile: 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.133 Mobile Safari/537.36',
                    desktop: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36'
                };

                try{
                    setInvalidatingCache(true)
                    await api.post('clear_page_cache', {
                        url
                    })

                    const rest = api.rest()

                    if(compareVersions(options?.rapidload_version, '2.2.11') > 0){
                        await api.post(`preload_page`, {
                            url: options.optimizer_url,
                            user_agent: activeReport === 'mobile' ? USER_AGENTS.mobile : USER_AGENTS.desktop,
                            nonce: options.nonce as string,
                            job_id: data?.job_id as string,
                        });
                    }else{
                        await rest.request('/ping', {
                            'url' : options.optimizer_url,
                            'user_agent' : activeReport === 'mobile' ? USER_AGENTS.mobile : USER_AGENTS.desktop,
                            'nonce': options?.nonce as string,
                            'job_id': data?.job_id as string,
                        })
                    }

                    setInvalidatingCache(false)
                }
                catch (e: any) {
                    setInvalidatingCache(false)
                    toast({
                        description: <div className='flex w-full gap-2 text-center'>{e.message} <XCircleIcon className='w-5 text-red-600'/></div>,
                    })
                }

              //  dispatch(fetchReport(options, url, true));
                

            }else if(!analyze){
               
            }

            dispatch(fetchSettings(options, url, true))

        } catch (error: any) {
            toast({
                description: <div className='flex w-full gap-2 text-center'>{error.message} <XCircleIcon className='w-5 text-red-600'/></div>,
            })

        }


        setSavingData(false)
        setInvalidatingCache(false)

    }, [data, activeReport,  savingData, invalidatingCache, settings, activeGear ])

    return {
        submitSettings
    }
}

export default useSubmitSettings