import React, {Suspense, useEffect, useState} from "react";
import ReactDOM from "react-dom";
import PageOptimizer from "app/page-optimizer";
import dashboard from "app/dashboard";
import SpeedPopover from "app/speed-popover";
import {useAppContext} from "../context/app";
import {ThunkDispatch} from "redux-thunk";
import {useDispatch, useSelector} from "react-redux";
import {AppAction, RootState} from "../store/app/appTypes";
import {fetchPosts, fetchReport, fetchSettings, getTestModeStatus} from "../store/app/appActions";
import {Toaster} from "components/ui/toaster";
import {AnimatePresence, m} from "framer-motion";
import {useRootContext} from "../context/root";
import Header from "app/page-optimizer/components/Header";
import {cn} from "lib/utils";
import {setCommonState} from "../store/common/commonActions";
import useCommonDispatch from "hooks/useCommonDispatch";
import {toBoolean} from "lib/utils";
import Bugsnag from "@bugsnag/js";
import Dashboard from "app/dashboard";
import TestModeSwitcher from "app/page-optimizer/components/TestModeSwitcher";

const AppTour = React.lazy(() => import( 'components/tour'))
const InitTour = React.lazy(() => import('components/tour/InitTour'))
import {isDev, isAdminPage, getOptimizeUrl} from "lib/utils";
import {Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger} from "components/ui/dialog";
import {PlusIcon} from "@heroicons/react/24/outline";
import {ContentSelector} from "components/ui/content-selector";
import AppButton from "components/ui/app-button";
import GeneralSettingsTrigger from "app/dashboard/components/GeneralSettingsTrigger";
import ThemeSwitcher from "components/ui/theme-switcher";
import TooltipText from "components/ui/tooltip-text";

const App = ({popup, _showOptimizer = false}: {
    popup?: HTMLElement | null,
    _showOptimizer?: boolean
}) => {

    const [popupNode, setPopupNode] = useState<HTMLElement | null>(null);
    const {showOptimizer, version, setShowOptimizer, mode, options} = useAppContext()
    const [shadowRoot, setShadowRoot] = useState<ShadowRoot | null>(null);
    const [mounted, setMounted] = useState(false)
    const dispatch: ThunkDispatch<RootState, unknown, AppAction> = useDispatch();
    const {activeReport} = useSelector((state: RootState) => state.app);
    const {isDark } = useRootContext()
    const initialTestMode = window.rapidload_optimizer ? toBoolean(window.rapidload_optimizer.test_mode) : false;
    const [open, setOpen] = useState(false);
    const { headerUrl } = useCommonDispatch();
    const { changeTheme } = useRootContext()

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

        Bugsnag.leaveBreadcrumb('Titan Loaded')

    }, []);

    useEffect(() => {

        if (showOptimizer) {
            Bugsnag.leaveBreadcrumb('Titan Opened');
        } else {
            Bugsnag.leaveBreadcrumb('Titan Closed');
        }

    }, [showOptimizer])


    useEffect(() => {
        // load initial data
        const optimizeUrl = getOptimizeUrl();
        dispatch(setCommonState('headerUrl', optimizeUrl));
        dispatch(fetchSettings(options, headerUrl ? headerUrl : options.optimizer_url, false));
        dispatch(fetchReport(options, headerUrl ? headerUrl :options.optimizer_url, false));
        dispatch(setCommonState('testModeStatus', initialTestMode));
        dispatch(fetchPosts(options));
    }, [dispatch, activeReport]);



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
    ]);

    // Effect to listen for hash changes
    useEffect(() => {
        const handleHashChange = () => {
            const hash = window.location.hash.replace("#", "");
            const validRoute = routes.some(route => route.id === hash);
            if (validRoute) {
                setActiveRoute(hash);
            } else {
                setActiveRoute("/");
            }
        };

        window.addEventListener("hashchange", handleHashChange);
        return () => {
            window.removeEventListener("hashchange", handleHashChange);
        };
    }, [routes]);

    useEffect(() => {
        if (isAdminPage || isDev) {
            window.location.hash = activeRoute;
        }
    }, [activeRoute]);



    return (
        <AnimatePresence>
            {(mounted && showOptimizer) &&
                <div className='dark:text-brand-300 text-brand-800 bg-brand-200/60 dark:bg-brand-900'>
                    <Suspense>
                        <AppTour isDark={isDark}>
                            <InitTour mode={mode}/>
                        </AppTour>
                    </Suspense>


                    <div className=' z-[1000000] dark:bg-brand-930 justify-center flex container'>
                        <header className=' z-[110000] container px-2 py-2 flex gap-3 mt-4 justify-between dark:bg-brand-930/80  bg-brand-0 rounded-3xl'>
                            <div className='flex items-center'>
                                <div className='relative px-4'>
                                    <img className='w-36'
                                         src={options?.page_optimizer_base ? (options?.page_optimizer_base + `/logo.svg`) : '/logo.svg'}
                                         alt='RapidLoad - #1 to unlock breakneck page speed'/>
                                    {version && (
                                        <span
                                            className='absolute text-xxs w-[200px] left-[90px] top-[1px] dark:text-brand-500 text-brand-400'>v{version}</span>
                                    )}
                                </div>

                                {/*<div className='flex'>*/}
                                {/*    <div data-tour='app-switch'*/}
                                {/*         className='select-none relative flex dark:bg-brand-800 py-0.5 pl-[2px] pr-[4px] rounded-2xl cursor-pointer bg-brand-200/80'>*/}


                                {/*        {routes.map((route, i) => (*/}
                                {/*            <>*/}

                                {/*                <button key={i}*/}
                                {/*                        onClick={e => setActiveRoute(route.id)}*/}
                                {/*                        className={cn(*/}
                                {/*                            'px-6 flex-1 h-10 text-sm tracking-wider ',*/}
                                {/*                            activeRoute === route.id && 'dark:bg-brand-700 bg-brand-0 rounded-xl'*/}
                                {/*                        )}>*/}
                                {/*                    {route.title}*/}
                                {/*                </button>*/}
                                {/*            </>*/}
                                {/*        ))}*/}
                                {/*    </div>*/}
                                {/*</div>*/}
                                <div className='flex'>
                                    <div
                                        data-tour='app-switch'
                                        className='select-none relative flex dark:bg-brand-800 py-0.5 pl-[2px] pr-[8px] rounded-2xl cursor-pointer bg-brand-200/80 overflow-hidden'
                                    >
                                        <div
                                            className={`absolute top-1 bottom-1 w-[50%] left-1 bg-white dark:bg-brand-700 rounded-xl transition-all duration-300 ease-in-out transform ${
                                                activeRoute === routes[1].id ? "translate-x-[93%]" : "translate-x-0 "
                                            }`}
                                        ></div>

                                        {routes.map((route, i) => (
                                            <button
                                                key={i}
                                                onClick={() => setActiveRoute(route.id)}
                                                className={cn(
                                                    'px-6 flex-1 h-10 text-sm tracking-wider z-10',
                                                    activeRoute === route.id ? 'text-black dark:text-white' : 'text-gray-500'
                                                )}
                                            >
                                                {route.title}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>


                            <div className="flex gap-6 items-center pr-6">
                                <TestModeSwitcher/>

                                <div className="flex items-center gap-3">
                                    <GeneralSettingsTrigger open={open} onOpenChange={setOpen}/>
                                    <TooltipText text="Theme">
                                        <div onClick={e => changeTheme()}>
                                            <ThemeSwitcher></ThemeSwitcher>
                                        </div>
                                    </TooltipText>
                                </div>
                            </div>

                        </header>

                    </div>

                    {routes.find(route => route.id === activeRoute)?.component || routes[0].component}

                </div>
            }
        </AnimatePresence>
    );
}

export default App;
