export type CommonState = {
    optimizerRoot: ShadowRoot | null
    mode: RapidLoadOptimizerModes,
    modeData?: RapidLoadOptimizerModeData
    isTourOpen: boolean
    onboardCompleted: boolean
    aiPredictionResult: any | null,
    mobile : {
        activeTab: AuditTypes;
        activeMetric?: Metric | null;
        hoveredMetric?: Metric | null;
        openAudits: string[];
        openCategory?: SettingsCategory | '';
        storePassedAudits?:string[];
        inProgress: boolean;
        settingsMode: PerformanceGear | null;
        testModeStatus: boolean;
        testModeLoading: boolean;
        auditsReturn: boolean;
        uucssError: boolean;
        headerUrl: string;
        diagnosticLoading: boolean;
        HomePerformance_dummy: HomePerformance;
        
    }
    desktop : {
        activeTab: AuditTypes;
        activeMetric?: Metric | null;
        hoveredMetric?: Metric | null;
        openAudits: string[];
        openCategory?: SettingsCategory | '';
        storePassedAudits?:string[];
        inProgress: boolean;
        settingsMode: PerformanceGear | null;
        testModeStatus: boolean;
        testModeLoading: boolean;
        auditsReturn: boolean;
        uucssError: boolean;
        headerUrl: string;
        diagnosticLoading: boolean;
        HomePerformance_dummy: HomePerformance;
    }

    rapidload_license_data_dummy: License ;

};

export const SET_STATE = 'SET_STATE';
export const SET_ROOT_STATE = 'SET_ROOT_STATE';

interface SetStateAction {
    type: typeof SET_STATE;
    payload: {
        activeReport: ReportType,
        key: string,
        value: any
    }
}

interface SetRootStateAction {
    type: typeof SET_ROOT_STATE;
    payload: {
        key: string,
        value: any
    }
}

export type CommonAction = SetStateAction | SetRootStateAction