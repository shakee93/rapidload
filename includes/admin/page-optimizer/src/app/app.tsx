import React, { Suspense, useEffect, useState } from "react";
import ReactDOM from "react-dom";
import PageOptimizer from "app/page-optimizer";
import dashboard from "app/dashboard";
import SpeedPopover from "app/speed-popover";
import { useAppContext } from "../context/app";
import { ThunkDispatch } from "redux-thunk";
import { useDispatch, useSelector } from "react-redux";
import { AppAction, RootState } from "../store/app/appTypes";
import { fetchPosts, fetchReport, fetchSettings, getTestModeStatus, updateDiagnosticResults, updateGeneralSettings, updateLicense } from "../store/app/appActions";
import { Toaster } from "components/ui/toaster";
import { AnimatePresence, m, motion } from "framer-motion";
import { useRootContext } from "../context/root";
import Header from "app/page-optimizer/components/Header";
import { cn, hasQueryParam } from "lib/utils";
import { setCommonRootState, setCommonState } from "../store/common/commonActions";
import useCommonDispatch from "hooks/useCommonDispatch";
import { toBoolean, isDev, disableDebugReport, isAdminPage, getOptimizeUrl } from "lib/utils";
import Bugsnag from "@bugsnag/js";
import Dashboard from "app/dashboard";
import Onboard from "app/onboard";
import TestModeSwitcher from "app/page-optimizer/components/TestModeSwitcher";

const AppTour = React.lazy(() => import('components/tour'))
const InitTour = React.lazy(() => import('components/tour/InitTour'))
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from "components/ui/dialog";
import { PlusIcon } from "@heroicons/react/24/outline";
import { ContentSelector } from "components/ui/content-selector";
import AppButton from "components/ui/app-button";
import GeneralSettingsTrigger from "app/dashboard/components/GeneralSettingsTrigger";
import OptimzePagesTrigger from "app/dashboard/components/OptimzePagesTrigger";
import OptimizerTableTrigger from "app/dashboard/components/OptimizerTableTrigger";
import ThemeSwitcher from "components/ui/theme-switcher";
import TooltipText from "components/ui/tooltip-text";
import { optimizerData } from "../store/app/appSelector";
import TestModeNotification from "components/ui/test-mode-notification";
import { Circle, MoreVertical } from "lucide-react";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from "components/ui/dropdown-menu";
import OptimizerPagesTable from "app/dashboard/components/OptimizerPagesTable";
import SlideUp from "components/animation/SlideUp";
import StepTwo from "app/onboard/components/StepTwo";
import HermesAIBot from "app/ai-bot";

const App = ({ popup, _showOptimizer = false }: {
    popup?: HTMLElement | null,
    _showOptimizer?: boolean
}) => {

    const [popupNode, setPopupNode] = useState<HTMLElement | null>(null);
    const { showOptimizer, version, setShowOptimizer, mode, options, uucssGlobal } = useAppContext()
    const [shadowRoot, setShadowRoot] = useState<ShadowRoot | null>(null);
    const [mounted, setMounted] = useState(false)
    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const { activeReport, allPosts } = useSelector((state: RootState) => state.app);
    const { isDark } = useRootContext()
    const initialTestMode = window.rapidload_optimizer ? toBoolean(window.rapidload_optimizer.test_mode) : false;
    const [open, setOpen] = useState({
        generalSettings: false,
        optimizerTable: false,
        optimizePages: false
    });
    const { headerUrl, onboardCompleted, diagnosticLoading } = useCommonDispatch();
    const { changeTheme } = useRootContext()
    const { testMode, license, data, generalSettings } = useSelector(optimizerData);
    const [licenseInfo, setLicenseInfo] = useState<License | null>(() => options.rapidload_license_data || null);

    useEffect(() => {
        dispatch(updateGeneralSettings(uucssGlobal?.active_modules.general.options));
        console.log("update global settings")
    }, []);

    useEffect(() => {


        if (_showOptimizer && isAdminPage || isDev) {
            setShowOptimizer(true)
        }

        document.body.classList.add('rl-page-optimizer-loaded');
        document.body.classList.add('rpo-loaded');

        if (popup) {
            document.body.classList.add('rpo-loaded:with-popup');
        }

        setTimeout(() => {
            setMounted(true)
        }, 50);

        !isDev && !disableDebugReport && Bugsnag.leaveBreadcrumb('Titan Loaded')

    }, []);

    useEffect(() => {
        const hasRapidloadClass = document.body.classList.contains('toplevel_page_rapidload');
        if (isDark && hasRapidloadClass) {
          document.body.style.backgroundColor = "rgb(24, 24, 27)"; 
        } else {
          document.body.style.backgroundColor = ""; 
        }
      }, [isDark]);

    useEffect(() => {

        if (showOptimizer) {
            !isDev && !disableDebugReport && Bugsnag.leaveBreadcrumb('Titan Opened');
        } else {
            !isDev && !disableDebugReport && Bugsnag.leaveBreadcrumb('Titan Closed');
        }

    }, [showOptimizer])

    useEffect(() => {
        const optimizeUrl = getOptimizeUrl();
        //dispatch(setCommonRootState('headerUrl', optimizeUrl));

        // load initial data
        dispatch(fetchReport(options, headerUrl ? headerUrl : options.optimizer_url, false, true));
        if (!uucssGlobal?.on_board_complete && !isDev) {
            return;
        }

        dispatch(fetchSettings(options, headerUrl ? headerUrl : options.optimizer_url, false));
        dispatch(setCommonState('testModeStatus', initialTestMode));
        dispatch(fetchPosts(options));
        dispatch(updateDiagnosticResults(options, headerUrl ? headerUrl : options.optimizer_url));
    }, [dispatch, activeReport]);

    const fetchRapidloadLicense = async () => {
        const response = await dispatch(updateLicense(options));
        
        if (response.success) {
            localStorage.setItem('rapidLoadLicense', JSON.stringify(response.data));
            setLicenseInfo(response.data);
        } else {
            localStorage.removeItem('rapidLoadLicense');
        }
    };

    useEffect(() => {
        if (uucssGlobal?.on_board_complete === '') return;
        if (licenseInfo) {
            dispatch(setCommonRootState('licenseConnected', true));
            
        } else if (!license) {
            fetchRapidloadLicense();
        }
    }, [licenseInfo]);

  
  

    // const hash = window.location.hash.replace("#", "");
    //  const [activeRoute, setActiveRoute] = useState( hash.length > 0 ? hash : '/');
    //  const [routes, setRoutes] = useState( [
    //      {
    //          title: "Dashboard",
    //          id: "/",
    //          component: <Dashboard />
    //      },
    //      {
    //          title: "Optimize",
    //          id: "/optimize",
    //          component: <PageOptimizer/>
    //      }
    //  ])
    //
    //
    //  useEffect(() => {
    //      window.location.hash = '#' + activeRoute
    //  }, [activeRoute])
    //
    //  useEffect(() => {
    //      const validRoute = routes.some(route => route.id === window.location.hash.replace('#', ''))
    //
    //
    //      if (!validRoute) {
    //          setActiveRoute('/')
    //      }
    //
    //  }, [])

    const [activeRoute, setActiveRoute] = useState(window.location.hash.replace("#", "") || "/");
    const [routes, setRoutes] = useState([
        { title: "Dashboard", id: "/", component: <Dashboard /> },
        { title: "Optimize", id: "/optimize", component: <PageOptimizer /> },
        { title: "Onboard", id: "/onboard", component: <Onboard /> },
        { title: "Rapidload AI", id: "/rapidload-ai", component: <HermesAIBot /> },
    ]);

    // Effect to listen for hash changes
    useEffect(() => {
        const handleHashChange = () => {
            const hash = window.location.hash.replace("#", "");
            // Extract the base route without query parameters
            const baseRoute = hash.split('?')[0];
            const validRoute = routes.some(route => route.id === baseRoute);
            if (validRoute) {
                setActiveRoute(hash); // Store the full hash including query params
            } else {
                setActiveRoute("/");
            }
        };

        window.addEventListener("hashchange", handleHashChange);
        // Initial check
        handleHashChange();

        return () => {
            window.removeEventListener("hashchange", handleHashChange);
        };
    }, [routes]);

    // Modify the route finding logic
    const findRouteComponent = (route: string) => {
        // Extract the base route without query parameters
        const baseRoute = route.split('?')[0];
        return routes.find(r => r.id === baseRoute)?.component || routes[0].component;
    };

   
    useEffect(() => {
        const hasNonce = hasQueryParam("nonce");
        const hasOnboard = window.location.hash.includes("onboard");

        if (!(isAdminPage || isDev)) return;

        if (onboardCompleted || isDev) {
            window.location.hash = activeRoute;
            // Clear nonce from URL if present
            if (hasNonce) {
                const url = new URL(window.location.href);
                url.searchParams.delete('nonce');
                window.history.replaceState({}, '', url.toString());
            }
            return;
        }

        if (uucssGlobal?.on_board_complete == '' || hasNonce) {
            window.location.hash = "#/onboard";
            setActiveRoute("/onboard");
            return;
        } else if (uucssGlobal?.on_board_complete == '1' && !hasNonce && hasOnboard) {
            window.location.hash = "#/";
            setActiveRoute("/");
            return;
        }
        window.location.hash = activeRoute;
    }, [activeRoute, onboardCompleted]);


    const optimizerTable = {
        title: "Optimize Pages",
        description: "Check out your Optimized Pages details in here.",
        total_jobs: 1000,
    };

    const handleOpenChange = (key: string, isOpen: boolean) => {
        setOpen(prev => ({
            ...prev,
            [key]: isOpen
        }));
    };

    const [showBanner, setShowBanner] = useState(true);

    // Check local storage on component mount
    useEffect(() => {
        const isBannerClosed = localStorage.getItem('rapidload-new-banner') === 'hidden';
        setShowBanner(!isBannerClosed);
    }, []);

    // Handle banner close
    const handleCloseBanner = () => {
        setShowBanner(false);
        localStorage.setItem('rapidload-new-banner', 'hidden');
    };


    return (
        <AnimatePresence>

            {(mounted && showOptimizer) &&
                <>

                  
                    <div className={`dark:text-brand-300 text-brand-800 dark:bg-brand-900 bg-[#F0F0F1]`}>
                        {/* New Banner Component */}
                        {showBanner && (
                            <div className="bg-gradient-to-r from-[#332247] to-[#441C74] text-white py-3 relative  mt-[-1px]">
                                <div className="container mx-auto px-4 text-center">
                                    <p className="text-sm font-medium">
                                        RapidLoad 3.0 is Here!{' '}
                                        <span className="opacity-90">
                                            Get all the details in the official guide.{' '}
                                        </span>
                                        <a
                                            href="https://docs.rapidload.io/rapidload-3-migration"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="underline hover:text-white/90 font-semibold"
                                        >
                                            Learn more
                                        </a>
                                    </p>
                                    <button
                                        onClick={handleCloseBanner}
                                        className="absolute right-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white p-1"
                                        aria-label="Close banner"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        )}
                        <Suspense>
                            <AppTour isDark={isDark}>
                                <InitTour mode={mode} />
                            </AppTour>
                        </Suspense>
                        {/* this is temp fix this need to be fixed */}
                        
                        {activeRoute !== "/onboard" && (
                            <div className='justify-center flex container'>
                                <header
                                    className={cn('container px-2 py-2 flex gap-3 mt-4 justify-between dark:bg-brand-800 bg-brand-0 rounded-2xl', testMode && 'ring-2 ring-[#f7b250] ring-offset-0')}>
                                    <div className='flex items-center'>
                                        <div className='relative px-2'>
                                            <img className='w-10'
                                                // src={options?.page_optimizer_base ? (options?.page_optimizer_base + `/new-logo.svg`) : '/new-logo.svg'}
                                                src={options?.page_optimizer_base ?
                                                    (options?.page_optimizer_base + `/${isDark ? 'dark_mode_logo.svg' : 'new-logo.svg'}`)
                                                    : `/${isDark ? 'dark_mode_logo.svg' : 'new-logo.svg'}`}
                                                alt='RapidLoad - #1 to unlock breakneck page speed' />
                                        </div>
                                        <div className='flex'>
                                            <div
                                                data-tour='app-switch'
                                                className='select-none relative flex dark:bg-brand-800/40 py-0.5 pl-[2px] pr-[8px] rounded-2xl overflow-hidden'
                                            >
                                                <div
                                                    className={`absolute top-1 bottom-1 left-1 bg-brand-200/60 border dark:bg-brand-950 rounded-xl transition-all duration-300 ease-in-out transform ${activeRoute === routes[1].id ? "translate-x-[115%] w-[45%]" : "translate-x-0 w-[55%]"
                                                        }`}
                                                >

                                                </div>

                                                {routes.map((route, i) => {
                                                    if (route.id === '/onboard' || route.id === '/rapidload-ai') {
                                                        return null;
                                                    }
                                                    return (
                                                        <button
                                                            key={i}
                                                            onClick={() => setActiveRoute(route.id)}
                                                            className={cn(
                                                                'flex h-10 text-sm z-10 font-medium items-center px-3 gap-2 cursor-pointer',
                                                                diagnosticLoading && 'cursor-not-allowed opacity-90 pointer-events-none',
                                                                activeRoute === route.id ? 'text-black dark:text-white  dark:text-brand-300' : 'text-gray-500'
                                                            )}
                                                        >
                                                            <Circle
                                                                className={cn(
                                                                    `w-2 stroke-0 transition-all fill-purple-800 relative inline-flex`,
                                                                    activeRoute === route.id ? 'delay-200' : 'opacity-0'
                                                                )}
                                                            />
                                                            {route.title}
                                                        </button>
                                                    );
                                                })}

                                            </div>
                                        </div>
                                    </div>


                                    <div className="flex gap-6 items-center">
                                        <TestModeSwitcher />

                                        <div className="flex items-center gap-1.5">
                                            <GeneralSettingsTrigger open={open.generalSettings} onOpenChange={(isOpen) => handleOpenChange("generalSettings", isOpen)} />
                                            
                                            <DropdownMenu>
                                                <DropdownMenuTrigger className='w-8 h-12 flex items-center justify-center'>
                                                    <TooltipText className='flex items-center justify-center' asChild={true} text='Add Optimization'>
                                                        <MoreVertical className={cn(
                                                            'h-5 w-5 dark:text-brand-300 text-brand-600 transition-opacity',
                                                        )} />
                                                    </TooltipText>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent style={{
                                                    width: 200
                                                }} align='end' sideOffset={6}
                                                    className='z-[110000] relative min-w-[200px]'>
                                                    <DropdownMenuLabel>Additional Options</DropdownMenuLabel>
                                                    <DropdownMenuItem onClick={() => {
                                                        setTimeout(() => {
                                                            handleOpenChange("optimizePages", true)
                                                        }, 100)
                                                    }}>
                                                        Optimization
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem onClick={() => {
                                                        setTimeout(() => {
                                                            handleOpenChange("optimizerTable", true)
                                                        }, 100)
                                                    }}>
                                                        View Table
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>

                                            <OptimzePagesTrigger
                                                open={open.optimizePages}
                                                onOpenChange={(isOpen: boolean) => handleOpenChange("optimizePages", isOpen)}
                                                data={allPosts} />
                                            <OptimizerTableTrigger
                                                open={open.optimizerTable}
                                                onOpenChange={(isOpen: boolean) => handleOpenChange("optimizerTable", isOpen)}
                                                settings={optimizerTable} />
                                        </div>



                                    </div>




                                </header>

                            </div>
                        )}

                        <SlideUp uuid={activeRoute || routes[0].id}>
                            {findRouteComponent(activeRoute)}
                        </SlideUp>


                        {version && (
                            <div className=' container px-6'>
                                <div className='flex border-t-2 justify-between py-6 items-center'>
                                    <div>
                                        <span
                                            className='text-sm dark:text-brand-500 text-brand-400'>Copyright © {new Date().getFullYear()} RapidLoad v{version}</span>
                                    </div>
                                    <div>
                                        <AppButton
                                            onClick={e => changeTheme()}
                                            className='transition-none h-12 px-3 rounded-2xl border-none bg-transparent'
                                            variant='outline'>
                                            <ThemeSwitcher></ThemeSwitcher>
                                        </AppButton>
                                    </div>
                                </div>

                            </div>

                        )}
                    </div>

                    
                </>
            }


        </AnimatePresence>
    );
}

export default App;
