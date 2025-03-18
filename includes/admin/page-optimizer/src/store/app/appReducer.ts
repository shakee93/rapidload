import {
    AppAction,
    AppState,
    CHANGE_GEAR,
    CHANGE_REPORT_TYPE,
    FETCH_REPORT_FAILURE,
    FETCH_REPORT_REQUEST,
    FETCH_REPORT_SUCCESS,
    FETCH_SETTING_FAILURE,
    FETCH_SETTING_REQUEST,
    FETCH_SETTING_SUCCESS,
    GET_CSS_STATUS_SUCCESS,
    UPDATE_FILE_ACTION,
    UPDATE_SETTINGS,
    UPDATE_TEST_MODE,
    UPDATE_OPTIMIZE_TABLE,
    FETCH_POSTS,
    GET_CDN_USAGE,
    GET_IMAGE_USAGE,
    GET_CACHE_USAGE, LICENSE_INFORMATION, HOME_PAGE_PERFORMANCE, SET_DIAGNOSTIC_RESULTS, SET_DIAGNOSTIC_PROGRESS, UPDATE_GENERAL_SETTINGS
} from "./appTypes";

const blankReport =  {
    original: null,
    changes: {
        files: []
    },
    data: null,
    error: null,
    loading: false,
    settings: [],
    originalSettings: [],
    revisions: [],
    state: {}
}

const initialState: AppState = {
    activeReport: 'desktop',
    cssStatus: null,
    testMode: null,
    optimizationData: [],
    allPosts: null,
    diagnosticResults: null,
    diagnosticProgress: {
        currentStep: 0,
        isFlushingProgress: 0,
        settingsProgress: 0,
        serverInfoProgress: 0,
        pageSpeedProgress: 0,
        diagnosticsProgress: 0
    },
    report: {
        mobile: blankReport,
        desktop: blankReport,
    },
    settings: {
        performance: {
            mobile: {
                original: [],
                state: [],
                error: null,
                loading: false,
            },
            desktop: {
                original: [],
                state: [],
                error: null,
                loading: false,
            }
        },
        general: {
            test_mode: false,
            performance_gear: null,
            options: {
                uucss_excluded_links: [],
                rapidload_minify_html: false,
                uucss_query_string: false,
                preload_internal_links: false,
                uucss_enable_debug: false,
                uucss_jobs_per_queue: 1,
                uucss_queue_interval: 600,
                uucss_disable_add_to_queue: false,
                uucss_disable_add_to_re_queue: false
            }
        },
        actions: []
    },
    cdnUsage: {
        additional_usage_gb: 0,
        allowed_gb: 0,
        used_gb: 0,
        cdn_url: '',
        origin: '',
        zone_id: '',
    },
    imageUsage: {
        additional_usage_gb: 0,
        allowed_gb: 0,
        used_gb: 0,
        host: '',
    },
    cacheUsage: null,
    license: null,
    homePerformance: {
        first_entry: 0,
        last_entry: 0,
        first_response_time: '0ms',
        last_response_time: '0ms'
    }
};

const appReducer = (state = initialState, action: AppAction): AppState => {

    switch (action.type) {
        case SET_DIAGNOSTIC_PROGRESS:
            return {
                ...state,
                diagnosticProgress: {
                    ...state.diagnosticProgress,
                    ...action.payload,
                }
            };
        case SET_DIAGNOSTIC_RESULTS:
            return {
                ...state,
                diagnosticResults: action.payload
            };
        case HOME_PAGE_PERFORMANCE:
            return {
                ...state,
                homePerformance: action.payload
            };
        case LICENSE_INFORMATION:
            return {
                ...state,
                license: action.payload
            };
        case GET_CDN_USAGE:
            return {
                ...state,
                cdnUsage: action.payload
            };
        case GET_IMAGE_USAGE:
            return {
                ...state, imageUsage: action.payload
            };
        case GET_CACHE_USAGE:
            return {
                ...state, cacheUsage: action.payload
            };
        case FETCH_POSTS:
            return {
                ...state,
                allPosts: action.payload,
            };
        case UPDATE_OPTIMIZE_TABLE:
            return {
                ...state,
                optimizationData: action.payload,
            };
        case GET_CSS_STATUS_SUCCESS:
            return {
                ...state,
                cssStatus: action.payload
            };
        case UPDATE_TEST_MODE:
            return {
                ...state,
                testMode: action.payload,
                settings: {
                    ...state.settings,
                    general: {
                        ...state.settings.general,
                        test_mode : action.payload.status 
                    }
                }
            };
        case FETCH_REPORT_REQUEST:
            return {
                ...state,
                report: {
                    ...state.report,
                    [state.activeReport] : {
                        ...state.report[state.activeReport],
                        loading: true,
                        error: null
                    }
                }
            };

        case FETCH_REPORT_SUCCESS:
            return {
                ...state,
                report: {
                    ...state.report,
                    [action.payload.activeReport] : {
                        ...state.report[action.payload.activeReport],
                        // original: JSON.parse(JSON.stringify(action.payload.data.data)),
                        data: action.payload.data.data,
                        error: null,
                        loading: false,
                        // settings: action.payload.data.settings,
                        // originalSettings: JSON.parse(JSON.stringify(action.payload.data.settings)),
                        revisions: action.payload.data.revisions,
                    }
                }
            };
        case FETCH_REPORT_FAILURE:
            return {
                ...state,
                report: {
                    ...state.report,
                    [state.activeReport] : {
                        error: action.error,
                        loading: false
                    }
                }
            };
        case FETCH_SETTING_REQUEST:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    performance: {
                        ...state.settings.performance,
                        [state.activeReport] : {
                            ...state.settings.performance[state.activeReport],
                            loading: true,
                            error: null
                        }
                    }
                },
            };
        case FETCH_SETTING_SUCCESS:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    general: action.payload.data.general,
                    actions: action.payload.data.actions,
                    performance: {
                        ...state.settings.performance,
                        [action.payload.activeReport] : {
                            ...state.settings.performance[action.payload.activeReport],
                            original: JSON.parse(JSON.stringify(action.payload.data.data)),
                            state: action.payload.data.data,
                            error: null,
                            loading: false,
                        }
                    }
                }
            };
        case FETCH_SETTING_FAILURE:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    performance: {
                        ...state.settings.performance,
                        [state.activeReport] : {
                            error: action.error,
                            loading: false
                        }
                    }
                }
            };
        case UPDATE_SETTINGS:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    performance: {
                        ...state.settings.performance,
                        [state.activeReport] : {
                            ...state.settings.performance[state.activeReport],
                            state: action.payload.settings,
                        },
                    }
                }
            };
        case CHANGE_GEAR:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    general: {
                        ...state.settings.general,
                        performance_gear: action.payload.mode
                    },
                    performance: {
                        ...state.settings.performance,
                        [state.activeReport] : {
                            ...state.settings.performance[state.activeReport],
                            state: action.payload.settings,
                        }
                    }
                }
            };
        case CHANGE_REPORT_TYPE:
            return {
                ...state,
                activeReport: action.reportType
            };
        case UPDATE_FILE_ACTION:

            const { payload } = action;
            const activeReport = state.report[state.activeReport];
            const changes = activeReport.changes.files.filter(f => f.file === payload.file)

            if (changes.length == 0) {
                activeReport.changes.files.push({
                    ...payload,
                    value: payload.prev
                });
            }

            activeReport.changes.files.push(payload);

            if (activeReport.data) {
                activeReport.data.audits = activeReport.data.audits.map((audit) => {


                    if (audit.files && audit.files.items && (audit.files.type === 'table' || audit.files.type === 'opportunity')) {
                        const updateActionValue = (item: AuditTableResource) => {
                            if (item.url && typeof item.url === 'object' && item.action && item.url.url === payload.file) {

                                // reporting changes
                                // if (!changes) {
                                //     activeReport.changes.files.push({
                                //         audit: audit.id,
                                //         file: item.url.url,
                                //         value: item.action.value
                                //     });
                                // } else{
                                //     activeReport.changes.files.push(action.payload)
                                // }


                                return {
                                    ...item,
                                    action: {
                                        ...item.action,
                                        value: payload.value,
                                    },
                                };
                            }
                            return item;
                        };

                        audit.files.items = audit.files.items.map(updateActionValue);

                        if (audit.files.grouped_items) {
                            audit.files.grouped_items = audit.files.grouped_items.map((group) => ({
                                ...group,
                                items: group.items.map(updateActionValue),
                            }));
                        }
                    }

                    return audit;
                });
            }

            return {
                ...state,
                [state.activeReport]: {
                    ...activeReport,
                },
            };
        case UPDATE_GENERAL_SETTINGS:
            return {
                ...state,
                settings: {
                    ...state.settings,
                    general: {
                        ...state.settings.general,
                        options: action.payload
                    }
                }
            };
        default:
            return state;
    }
};

export default appReducer;

