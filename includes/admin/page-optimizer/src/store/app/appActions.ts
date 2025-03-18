import { ThunkAction, ThunkDispatch } from 'redux-thunk';
import { AnyAction } from 'redux';
import {
    AppAction,
    CHANGE_GEAR,
    CHANGE_REPORT_TYPE, FETCH_POSTS,
    FETCH_REPORT_FAILURE,
    FETCH_REPORT_REQUEST,
    FETCH_REPORT_SUCCESS,
    FETCH_SETTING_FAILURE,
    FETCH_SETTING_REQUEST,
    FETCH_SETTING_SUCCESS, GET_CACHE_USAGE, GET_CDN_USAGE,
    GET_CSS_STATUS_SUCCESS, GET_IMAGE_USAGE, HOME_PAGE_PERFORMANCE, LICENSE_INFORMATION,
    RootState,
    UPDATE_FILE_ACTION, UPDATE_OPTIMIZE_TABLE,
    UPDATE_SETTINGS,
    UPDATE_TEST_MODE,
    SET_DIAGNOSTIC_RESULTS,
    SET_DIAGNOSTIC_PROGRESS,
    UPDATE_GENERAL_SETTINGS
} from "./appTypes";
import ApiService from "../../services/api";
import Audit from "app/page-optimizer/components/audit/Audit";

const transformAudit = (audit: Audit, metrics: Metric[]) => {

    audit.metrics = metrics.filter(m => m?.refs?.relevantAudits?.includes(audit.id))

    if (audit.files && (audit.files.type === 'opportunity' || audit.files.type === 'table')) {

        if (audit?.files?.items?.length > 0) {

            audit.files.grouped_items = audit.files.items.reduce((result: GroupedAuditResource[], item) => {

                let key = 'unknown'

                if (item.url && typeof item.url !== 'string') {
                    const { url, file_type } = item.url;

                    if (file_type.value) {
                        key = file_type.value
                    }
                }

                const existingGroup = result.find(group => group.type === key);

                if (existingGroup) {
                    existingGroup.items.push(item);
                } else {
                    result.push({ type: key, items: [item] });
                }

                return result;
            }, []);



        }

    }

    return audit
}

const transformReport = (data: any) => {

    const metrics = data.data?.page_speed?.metrics.map((metric: Metric) => ({
        ...metric,
        potentialGain: metric.refs ? (metric.refs?.weight - (metric.refs?.weight / 100) * metric.score) : 0
    }))

    const audits: Audit[] = data.data.page_speed.audits
        .sort((a: Audit, b: Audit) => a.score - b.score)
        .map((a: Audit) => transformAudit(a, metrics))

    const sortAuditsWithActions = (a: Audit, b: Audit) => {
        const aFirstCondition = a.settings.filter(s => s.inputs[0].value).length > 0;
        const bFirstCondition = b.settings.filter(s => s.inputs[0].value).length > 0;

        if (aFirstCondition && !bFirstCondition) return -1;
        if (!aFirstCondition && bFirstCondition) return 1;

        const aSecondCondition = a.settings.length > 0;
        const bSecondCondition = b.settings.length > 0;

        if (aSecondCondition && !bSecondCondition) return -1;
        if (!aSecondCondition && bSecondCondition) return 1;

        const aThirdCondition = (a.files?.items?.length || 0) > 0;
        const bThirdCondition = (b.files?.items?.length || 0) > 0;

        if (aThirdCondition && !bThirdCondition) return -1;
        if (!aThirdCondition && bThirdCondition) return 1;

        return 0;
    }

    const _data = {
        data: {
            performance: data.data.page_speed.performance ? parseFloat(data.data?.page_speed?.performance.toFixed(0)) : 0,

            ...data.data.page_speed,
            grouped: {
                passed_audits: audits.filter(audit => audit.type === 'passed_audit').sort(
                    sortAuditsWithActions
                ),
                opportunities: audits.filter(audit => audit.type === 'opportunity'),
                diagnostics: audits.filter(audit => audit.type === "diagnostics")
                    .sort((a, b) => (a.scoreDisplayMode === 'informative' ? 1 : -1)),
            },
            metrics: metrics,
        },

        success: data.success,
        // settings: initiateSettings(audits),
        revisions: data.data.revisions.map(({ created_at, timestamp, data, id }: any) => {
            return {
                id,
                created_at,
                timestamp,
                data: {
                    performance: data.performance
                }
            }
        }),
        individual_file_actions: data.data['individual-file-actions'],
        state: data.state
    };


    delete _data.data.audits
    return _data
}

const transformSettings = (data: any) => {


    if (!data.success) {
        return data
    }

    const settings = data?.data?.performance || [];

    return {
        general: {
            performance_gear: data?.data?.general?.performance_gear,
            test_mode: data?.data?.general?.test_mode === "1"
        },
        actions: data?.data?.actions,
        data: settings.map((s: AuditSetting) => ({
            ...s,
            inputs: s.inputs.map(input => ({
                ...input,
                ...(
                    input.control_type === 'checkbox' &&
                    {
                        value: input.value === '1' || input.value === true
                    }
                ),
                ...(input.inputs && {
                    inputs: input.inputs.map(i => {
                        return {
                            ...i,
                            value: i.control_type === 'checkbox' ? i.value === '1' || i.value === true : i.value
                        }
                    })
                }),
            }))
        }))
    }
}


export const getCSSStatus = (options: WordPressOptions, url: string, types: string[]): ThunkAction<Promise<any>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState) => {

        try {
            const cssJobStatusResult = await api.getCSSJobStatus(url, types);
            dispatch({
                type: GET_CSS_STATUS_SUCCESS,
                payload: cssJobStatusResult.data
            })
            return cssJobStatusResult?.data;

        } catch (error) {
           // console.error('Error fetching CSS job status:', error);
        }


    }
}
// : ThunkAction<void, RootState, unknown, AnyAction> =>

export const getTestModeStatus = (options: WordPressOptions, url: string, mode?: string): ThunkAction<Promise<{ success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ success: boolean, error?: string }> => {

        try {
            const fetchTestModeData = await api.getTestMode(url, mode || '');
            dispatch({
                type: UPDATE_TEST_MODE,
                payload: fetchTestModeData?.data
            })
            return { success: true };
        } catch (error: any) {
           // console.error('Error on Test Mode:', error);
            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { success: false, error: errorMessage };
        }


    }
}

export const getSummary = (options: WordPressOptions, action: string): ThunkAction<Promise<{ success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ success: boolean, error?: string }> => {

        try {
            const getUsage = await api.getSummary(action);
            const actionType = action === 'get_rapidload_cdn_usage' ? GET_CDN_USAGE : action === 'get_rapidload_image_usage' ? GET_IMAGE_USAGE : GET_CACHE_USAGE;
            dispatch({
                type: actionType,
                payload: getUsage?.data
            });

            return { success: true };
        } catch (error: any) {
           // console.error('Error on Test Mode:', error);
            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { success: false, error: errorMessage };
        }

    }
}


export const getTitanOptimizationData = (options: WordPressOptions, startFrom: number, limit: number): ThunkAction<Promise<{ hasMoreData?: boolean, success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ hasMoreData?: boolean, success: boolean, error?: string }> => {

        try {
            const fetchOptimizationData = await api.getOptimizationData(startFrom, limit);
            dispatch({
                type: UPDATE_OPTIMIZE_TABLE,
                payload: fetchOptimizationData?.data
            })
            const hasMoreData = fetchOptimizationData?.data && fetchOptimizationData.data.length > 0;

            // console.log(fetchOptimizationData )
            return { success: true, hasMoreData: hasMoreData };
        } catch (error: any) {
          //  console.error('Error on fetchOptimizationData:', error);
            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { success: false, error: errorMessage };
        }


    }
}

export const searchData = (options: WordPressOptions, action: string, searchFor: string, postType?: string): ThunkAction<Promise<{ data: any, success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ data: any, success: boolean, error?: string }> => {

        try {
            const searchForData = await api.searchData(action, searchFor, postType);

            return searchForData;
        } catch (error: any) {

            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { data: null, success: false, error: errorMessage };
        }


    };
};


export const deleteOptimizedData = (options: WordPressOptions, url: string): ThunkAction<Promise<{ url: any, success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ url: any, success: boolean, error?: string }> => {

        try {
            const deleteData = await api.deleteOptimizedData(url);

            return deleteData;
        } catch (error: any) {

            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { url: null, success: false, error: errorMessage };
        }


    };
};


export const saveGeneralSettings = (options: WordPressOptions, data: any): ThunkAction<Promise<{ success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ success: boolean, error?: string }> => {

        try {
            const saveGeneralSettings = await api.saveGeneralSettings(data);
            return { success: true };
        } catch (error: any) {
           // console.error('Error on saving General Settings:', error);
            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { success: false, error: errorMessage };
        }


    };
};

export const updateLicense = (options: WordPressOptions, data?: any): ThunkAction<Promise<{ success: boolean, error?: string, data?: any }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ success: boolean, error?: string, data?: any }> => {

        try {
            const connectLicense = await api.updateLicense(data);
            if (connectLicense.success) {
                dispatch({
                    type: LICENSE_INFORMATION,
                    payload: connectLicense.data,
                });
                return { success: true, data: connectLicense.data };
            } else {
                return { success: false, error: connectLicense.data || "License update failed" };
            }
        } catch (error: any) {
           // console.error('Error on connecting:', error);
            // Fallback error message handling
            const errorMessage = error instanceof Error ? error.message : 'An unknown error occurred';
            return { success: false, error: errorMessage };
        }
    };
};

export const getHomePagePerformance = (options: WordPressOptions, data?: any): ThunkAction<Promise<{ success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ success: boolean, error?: string }> => {

        try {
            const getPerformance = await api.homePagePerformance();
            dispatch({
                type: HOME_PAGE_PERFORMANCE,
                payload: getPerformance.data,
            });
            return { success: true };
        } catch (error: any) {
          //  console.error('Error on fetching:', error);
            // Fallback error message handling
            const errorMessage = error instanceof Error ? error.message : 'An unknown error occurred';
            return { success: false, error: errorMessage };
        }
    };
};

export const getAiPrediction = (options: WordPressOptions, url: string, score: number, audits: any, metrics: any): ThunkAction<Promise<{ success: boolean, error?: string, data?: any }>, RootState, unknown, AnyAction> => {
    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ success: boolean, error?: string, data?: any }> => {
        try {

            const aiPredictionResult = await api.getAiPrediction(url, score, {
                opportunities: audits.opportunities.map((o: any) => ({
                    name: o.name,
                    score: o.score,
                    metrics: o.metrics.map((m: any) => m.refs.acronym),
                    settings: o.settings.map((s: any) => s.name),
                })),
                diagnostics: audits.diagnostics.map((d: any) => ({
                    name: d.name,
                    score: d.score,
                    metrics: d.metrics.map((m: any) => m.refs.acronym),
                })),
            }, metrics.map((m: any) => ({
                metric: m.refs.acronym,
                potentialGain: m.potentialGain,
                score: m.score,
                weight: m.weight,
                displayValue: m.displayValue,
            })));

            return {
                success: true,
                data: aiPredictionResult?.data
            };
        } catch (error: any) {
           // console.error('Error on AI Prediction:', error);
            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { success: false, error: errorMessage };
        }
    }
}

export const fetchPosts = (options: WordPressOptions): ThunkAction<void, RootState, unknown, AnyAction> => {
    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AnyAction>) => {
        try {
            const fetchPostsPages = await api.fetchPosts();
            dispatch({
                type: FETCH_POSTS,
                payload: fetchPostsPages?.data,
            });
        } catch (error: any) {
            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
           // console.error(errorMessage);

        }
    };
};

export const fetchReport = (options: WordPressOptions, url: string, reload = false,  fetchBoth?: boolean, abortController?: AbortController, noRapidLoad?: boolean): ThunkAction<void, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState) => {
        try {
            const currentState = getState(); // Access the current state
            //const activeReport = currentState.app.activeReport;
           // const activeReportData = currentState.app.report[activeReport]

           // If `fetchBoth` is true, fetch for both `mobile` and `desktop`
           const reportTypes: ReportType[] = fetchBoth ? ['mobile', 'desktop'] : [currentState.app.activeReport];

           
            // TODO: don't let people bam on keyboard while waiting to laod the page speed
            // if(activeReportData.loading && activeReportData.data ) {
            //     console.log('don\'t bam the mouse! we are loading your page speed details 😉');
            //     return;
            // }
            for (const reportType of reportTypes) {

                const activeReportData = currentState.app.report[reportType];

            if (activeReportData.loading) {
                return;
            }

            if (activeReportData.data && !reload ) {
                return;
            }

           // console.log("coming upto this point", reportType)

            dispatch({ type: FETCH_REPORT_REQUEST, activeReport: reportType  });

            const response = await api.fetchPageSpeed(
                url,
                reportType,
                reload,
                abortController,
                noRapidLoad
            );

            dispatch({
                type: FETCH_REPORT_SUCCESS, payload: {
                    activeReport: reportType ,
                    data: transformReport(response)
                }
            });
        }


        } catch (error) {
            if (error instanceof Error) {
                dispatch({ type: FETCH_REPORT_FAILURE, error: error.message });
            } else {
                dispatch({ type: FETCH_REPORT_FAILURE, error: 'Unknown error occurred' });
            }
        }
    };
};

export const fetchSettings = (options: WordPressOptions, url: string, reload = false, inprogress = false): ThunkAction<void, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState) => {
        try {
            const currentState = getState(); // Access the current state
            const activeReport = currentState.app.activeReport;
            const activeSettingsData = currentState.app.settings.performance[activeReport]

            // TODO: don't let people bam on keyboard while waiting to load the page speed
            // if(activeReportData.loading && activeReportData.data ) {
            //     console.log('don\'t bam the mouse! we are loading your page speed details 😉');
            //     return;
            // }

            if (activeSettingsData.loading) {
                return;
            }

            if (activeSettingsData?.state?.length > 0 && !reload) {
                return;
            }

            dispatch({ type: FETCH_SETTING_REQUEST, activeReport });

            const response = await api.fetchSettings(
                url,
                activeReport,
                reload,
            );

            dispatch({
                type: FETCH_SETTING_SUCCESS, payload: {
                    activeReport,
                    data: transformSettings(response),
                }
            });


        } catch (error) {
            if (error instanceof Error) {
                dispatch({ type: FETCH_SETTING_FAILURE, error: error.message });
            } else {
                dispatch({ type: FETCH_SETTING_FAILURE, error: 'Unknown error occurred' });
            }
        }
    };
};

export const updateSettings = (
    audit: Audit,
    setting: AuditSetting,
    key: string, // key of the input
    payload: any, // changed value

): ThunkAction<void, RootState, unknown, AnyAction> => {

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState) => {
        const currentState = getState(); // Access the current state
        const deviceType = currentState?.app?.activeReport;

        // @ts-ignore
        const newOptions: AuditSetting[] = currentState?.app?.settings.performance[deviceType]?.state?.map((s: AuditSetting) => {
            if (s.name !== setting.name) {
                return s; // Early return if the setting name doesn't match
            }

            const inputKey = key.split('.')

            return {
                ...s,
                inputs: s.inputs.map(input =>
                    inputKey.length > 1 ?
                        (input?.inputs && input.key === inputKey[0]) ? {
                            ...input,
                            inputs: input?.inputs.map((i: AuditSettingInput) => i.key === inputKey[1] ? {
                                ...i,
                                value: payload
                            } : i)
                        } : input :
                        input.key === key ? { ...input, value: payload } : input
                )
            }
        }) || [];

        dispatch({
            type: UPDATE_SETTINGS, payload: {
                settings: newOptions,
            }
        });
    }
}

export const changeGear = (
    mode: BasePerformanceGear | PerformanceGear,
): ThunkAction<Promise<string[] | undefined>, RootState, unknown, AnyAction> => {
    //'Remove Unused CSS', 
    const starter = ['Remove Unused CSS', 'Minify CSS', 'Minify Javascript', 'Page Cache', 'Self Host Google Fonts'];
    const accelerate = [...starter, 'RapidLoad CDN', 'Serve next-gen Images', 'Lazy Load Iframes', 'Lazy Load Images', 'Exclude LCP image from Lazy Load', 'Add Width and Height Attributes', 'Defer Javascript'];
    const turboMax = [...accelerate.filter(item => item !== 'Remove Unused CSS'), 'Delay Javascript', 'Critical CSS', 'Serve next-gen Images (AVIF, WEBP)'];

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState) => {
        const currentState = getState(); // Access the current state
        const deviceType = currentState?.app?.activeReport;
        const settings = currentState?.app?.settings.performance[deviceType]?.state;
        const activeGear = settings?.find(s => s.category === 'gear')?.inputs[0].value

        // don't update if the mode is sam
        if (activeGear === mode) {
            return;
        }

        const modes: {
            [key in BasePerformanceGear]: string[]
        } = { starter, accelerate, turboMax };

        //console.log(modes[mode])
        // excluding perf gear from updates.
        const newOptions: AuditSetting[] = settings
            ?.map((s: AuditSetting) => ({
                ...s,
                inputs: s.inputs.map((input, index) => ({
                    ...input,
                    value: index === 0 ? (
                        s.category === 'gear' ? mode :
                            // update values only if it is not custom.
                            (mode === 'custom' ? input.value : modes[mode]?.includes(s.name))
                        // return input value for all the other sub-options
                    ) : input.value
                }))
            })) || [];

        // Check if mode is a valid key in modes
        const options = mode in modes ? modes[mode as keyof typeof modes] : undefined;

        dispatch({
            type: CHANGE_GEAR, payload: {
                settings: newOptions,
                mode
            }
        });

        return Promise.resolve(options);
    }
}

export const changeReport = (
    type: ReportType
): ThunkAction<void, RootState, unknown, AnyAction> => {
    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState) => {
        dispatch({
            type: CHANGE_REPORT_TYPE,
            reportType: type
        })
    }
}

export const updateFileAction = (
    audit: Audit,
    file: string,
    value: any,
    prev: any
): ThunkAction<void, RootState, unknown, AnyAction> => {
    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState) => {

        dispatch({
            type: UPDATE_FILE_ACTION, payload: {
                audit: audit,
                file: file,
                value: value,
                prev: prev,
            }
        })
    }
}

// export const setDiagnosticResults = (results: DiagnosticResults) => (
//     dispatch: ThunkDispatch<RootState, unknown, AppAction>,
//     getState: () => RootState
// ) => {
//     dispatch({
//         type: SET_DIAGNOSTIC_RESULTS,
//         payload: results,
//     });
// }


export const updateDiagnosticResults = (options: WordPressOptions, url: string, data?: any): ThunkAction<Promise<{ success: boolean, error?: string }>, RootState, unknown, AnyAction> => {

    const api = new ApiService(options);

    return async (dispatch: ThunkDispatch<RootState, unknown, AppAction>, getState): Promise<{ success: boolean, error?: string }> => {

        const currentState = getState(); // Access the current state
        const activeReport = currentState?.app?.activeReport;

        try {
            const updateDiagnosticResults = await api.updateDiagnosticResults(url, activeReport, data);
            const diagnosticData = data || updateDiagnosticResults.data.diagnose_data[activeReport].data;

            dispatch({
                type: SET_DIAGNOSTIC_RESULTS,
                payload: diagnosticData ,
            });
            
            return { success: true };

        } catch (error: any) {
            //console.error('Error on updating Diagnostic Results:', error);
            let errorMessage: string;
            if (error instanceof Error) {
                errorMessage = error.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            } else {
                errorMessage = 'An unknown error occurred';
            }
            return { success: false, error: errorMessage };
        }


    };
};

export const setDiagnosticProgress = (
    progress: Partial<DiagnosticProgress>
 ): ThunkAction<void, RootState, unknown, AnyAction> => {
     return (dispatch, getState) => {
         const currentProgress = getState().app.diagnosticProgress;
         
         dispatch({
             type: SET_DIAGNOSTIC_PROGRESS, 
             payload: {
                 ...currentProgress,
                 ...progress
             },
         });
     }
 }

export const updateGeneralSettings = (
    settings: GeneralSettings
): ThunkAction<void, RootState, unknown, AnyAction> => {
    return (dispatch) => {
        dispatch({
            type: UPDATE_GENERAL_SETTINGS,
            payload: settings
        });
    }
}