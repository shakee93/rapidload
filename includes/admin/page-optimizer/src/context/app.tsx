import {
    createContext,
    Dispatch,
    ReactNode,
    RefObject,
    SetStateAction,
    useContext,
    useEffect,
    useRef,
    useState
} from 'react';
import useCommonDispatch from "hooks/useCommonDispatch";
import {setCommonRootState} from "../store/common/commonActions";

interface OptimizerContextProps {
    openAudits: string[],
    setOpenAudits: Dispatch<SetStateAction<string[]>>;
    showOptimizer: boolean;
    setShowOptimizer: Dispatch<SetStateAction<boolean>>;
    options: WordPressOptions,
    uucssGlobal: uucssGlobal,
    version: string,
    mode: RapidLoadOptimizerModes
    modeData?: RapidLoadOptimizerModeData
    manipulatingStyles: boolean
    savingData: boolean;
    setSavingData: Dispatch<SetStateAction<boolean>>;
    global: boolean
    togglePerformance: boolean;
    setTogglePerformance: Dispatch<SetStateAction<boolean>>;
    optimizerContainer: RefObject<HTMLElement>
    invalidatingCache: boolean
    setInvalidatingCache: Dispatch<SetStateAction<boolean>>;
}

export const AppContext = createContext<OptimizerContextProps | null>(null)


export const AppProvider = ({ children, initShowOptimizerValue, global, mode, modeData } : {
    children: ReactNode
    mode: RapidLoadOptimizerModes
    modeData?: RapidLoadOptimizerModeData
    initShowOptimizerValue?: boolean,
    global: boolean,
}) => {
    const isAdminBar = document.getElementById('wpadminbar');

    const [showOptimizer, setShowOptimizer] = useState<boolean>(false);
    const [manipulatingStyles, setManipulatingStyles] = useState<boolean>(false);
    const [mounted, setMounted] = useState<boolean>(false);
    const [sheetsHidden, setSheetsHidden]= useState(false)
    const [openAudits, setOpenAudits] = useState<string[]>([]);

    const [options, setOptions] = useState((
        {
            optimizer_url: 'https://rapidload.io/',
            ajax_url: "http://rapidload.local/wp-admin/admin-ajax.php",
            page_optimizer_base: '',
            page_optimizer_package_base: '',
            plugin_url: '',
            nonce: '',
            timezone: 'UTC',
            actions: [],
            load_optimizer: false,
            rapidload_version: '2.2.0',
            rest_url: 'https://rapidload.local/wp-json/rapidload/v1',
            rapidload_titan_gear: '',
            db_to_be_updated: '',
            rapidload_license_data: '',
            ...(
                window.rapidload_optimizer ? window.rapidload_optimizer : {}
            ),
        }
    ))

    const [uucssGlobal, setUucssGlobal] = useState((
        {
            active_modules: {
                general: {
                    options: {
                        uucss_excluded_links: [],
                        rapidload_minify_html: false,
                        uucss_query_string: false,
                        preload_internal_links: false,
                        uucss_enable_debug: false,
                        uucss_jobs_per_queue: 0,
                        uucss_queue_interval: 0,
                        uucss_disable_add_to_queue: false,
                        uucss_disable_add_to_re_queue: false
                    }
                }
            },
            ...(
                window.uucss_global ? window.uucss_global : {}
            )
        }
    ))
    const [type, setType] = useState<ReportType>('desktop');
    const [savingData, setSavingData] = useState<boolean>(false)
    const [togglePerformance, setTogglePerformance] = useState(true);
    const optimizerContainer = useRef<HTMLElement|null>(null);
    const [invalidatingCache, setInvalidatingCache] = useState<boolean>(false)
    const { dispatch } = useCommonDispatch()

    useEffect(() => {
        const updateData = (event: RapidLoadSetOptimizerEvent) => {
            const { detail } = event

            if (typeof detail?.status !== undefined) {
                setShowOptimizer(detail?.status || false);
            }
        };

        window.addEventListener('rapidLoad:set-optimizer', updateData);
        
        setMounted(true)

        if (mode) {
            dispatch(setCommonRootState('mode', mode));
        }

        if (modeData) {
            dispatch(setCommonRootState('modeData', modeData));
        }

        const event =
            new CustomEvent('rapidLoad:optimizer-mounted');

        window.dispatchEvent(event);

        return () => {
            window.removeEventListener('rapidLoad:set-optimizer', updateData);
        };
    }, [])

    useEffect(() => {

        if (showOptimizer) {
            document.documentElement.classList.add('rapidload-optimizer-open')
        } else {
            document.documentElement.classList.remove('rapidload-optimizer-open')
        }
        
    }, [showOptimizer])

    const _setShowOptimizer = (value: SetStateAction<boolean>) => {

        if (!value) {
            // giving a breath to enabled stylesheets to paint
            requestAnimationFrame(() => {
                setShowOptimizer(value)
            });
        } else {
            setShowOptimizer(value)
        }
    }

    return (
        <AppContext.Provider value={{
            showOptimizer,
            setShowOptimizer : _setShowOptimizer,
            options,
            uucssGlobal,
            openAudits,
            setOpenAudits,
            version: __OPTIMIZER_VERSION__,
            mode,
            modeData,
            manipulatingStyles,
            global,
            togglePerformance,
            setTogglePerformance,
            savingData,
            setSavingData,
            optimizerContainer,
            invalidatingCache,
            setInvalidatingCache,
        }}>
            {children}
        </AppContext.Provider>
    );
};

export const useAppContext = () => {
    const context = useContext(AppContext);

    if (context === null) {
        throw new Error('useAppContext must be used within an AppProvider');
    }

    return context;
}