import {Dispatch, SetStateAction, useState} from "react";

import Header from "app/page-optimizer/components/Header";
import PageSpeedScore from "app/page-optimizer/components/performance-widgets/PageSpeedScore";
import {ArrowLeftOnRectangleIcon, ArrowRightOnRectangleIcon} from "@heroicons/react/24/outline";
import Card from "components/ui/card";
import {useSelector} from "react-redux";
import {RootState} from "../../store/reducers";
import {useOptimizerContext} from "../../context/root";
import {cn} from "../../lib/utils";
import Audit from "app/page-optimizer/components/audit/Audit";
import Footer from "app/page-optimizer/components/Footer";
import Loading from "components/loading";

export default function PageOptimizer() {
    const [activeTab, setActiveTab] = useState<AuditTypes>("opportunities");
    const [togglePerformance, setTogglePerformance] = useState(false);
    const {data, loading} = useSelector((state: RootState) => state.app);
    const {options} = useOptimizerContext()

    const tabs: Tab[] = [
        {
            key: "opportunities",
            name: "Opportunities",
            color: 'border-red-400',
            activeColor: 'bg-red-400',
        },
        {
            key: "diagnostics",
            name: "Diagnostics",
            color: 'border-yellow-400 ',
            activeColor: 'bg-yellow-400 '
        },
        {
            key: "passed_audits",
            name: "Passed Audits",
            color: 'border-green-600',
            activeColor: 'bg-green-600'
        },
    ];

    const renderTabs = () => {


        return tabs.map((tab) => {
            const isActive = activeTab === tab.key ? "font-medium border-b border-b-purple-750" : "text-zinc-500";
            return (
                <div
                    onClick={() => setActiveTab(tab.key)}
                    className={cn(`flex items-center gap-2 px-4 py-3 text-sm font-medium`, isActive)}
                    key={tab.key}
                >
                    {tab.name}
                    {(data?.data && data.data.audits?.length > 0) && (
                        <div className={
                            cn(
                                'flex text-xxs items-center justify-center rounded-full w-6 h-6 border-2',
                                tab.color,
                                (activeTab === tab.key) && tab.activeColor,
                            )}>
                            <span className={cn(
                                activeTab === tab.key && 'text-white'
                            )}>
                                {data?.data.grouped[`${tab.key}`].length}
                            </span>
                        </div>
                    )}

                </div>
            );
        });
    };


    return (
        <div
            className="fixed z-[100000] w-screen h-screen top-0 left-0 flex min-h-screen flex-col text-base items-center dark:text-white text-[#212427] dark:bg-zinc-900 bg-[#F7F9FA]">
            <div className='overflow-auto w-full'>
                <Header url={options.optimizer_url}/>
                {!loading ? (
                    <section className="container grid grid-cols-12 gap-8 pt-4">
                        {togglePerformance && (
                            <aside className="col-span-3">
                                <h2 className="text-lg ml-5">Speed Insights</h2>
                                <div className="widgets pt-4 flex">
                                    {/*<PageSpeedScore pagespeed={pagespeed[0]}/>*/}
                                </div>
                            </aside>
                        )}
                        <article className={`${togglePerformance ? 'col-span-9' : 'col-span-12'}`}>
                            <h2 className="text-lg ml-5 flex gap-2 font-normal items-center">
                        <span className='cursor-pointer' onClick={() => {
                            setTogglePerformance(prev => !prev)
                        }}>
                            {(togglePerformance) ? <ArrowLeftOnRectangleIcon className="h-4 w-4 text-gray-500"/> :
                                <ArrowRightOnRectangleIcon className="h-4 w-4 text-gray-500"/>}
                        </span>
                                Fix Performance Issues</h2>
                            <div className="tabs pt-4 flex">
                                <Card padding='p-0 px-6' cls='flex cursor-pointer select-none'>
                                    {renderTabs()}
                                </Card>
                            </div>
                            <div className="audits pt-4 flex">

                                {(data?.data && data?.data.audits?.length > 0) && (
                                    <div className='grid grid-cols-12 gap-6 w-full relative mb-24'>
                                        <div className='col-span-12 ml-8 flex flex-col gap-4'>
                                            {data?.data.grouped[`${activeTab}`]
                                                ?.sort((a, b) => a.score - b.score)
                                                .map((audit, index) => (
                                                    <Audit priority={index == 0} key={audit.id} audit={audit}/>
                                                ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </article>
                    </section>
                ) : (
                    <Loading/>
                )}
            </div>
            <Footer url={options.optimizer_url} />
        </div>
    );
}
