import {
    ArrowPathIcon,
    ArrowTopRightOnSquareIcon,
    ComputerDesktopIcon,
    DevicePhoneMobileIcon, XMarkIcon
} from "@heroicons/react/24/outline";
import ThemeSwitcher from "components/ui/theme-switcher";
import {useState} from "react";
import {useOptimizerContext} from "../../../context/root";
import TooltipText from "components/ui/tooltip-text";


const Header = ({ url = null}: { url: string|null}) => {

    const [isDesktop, setIsDesktop] = useState(true);
    const { setShowOptimizer, options } = useOptimizerContext()

    const desktopButtonClick = (isDesktop: boolean) => {
        setIsDesktop(isDesktop);
    };

    return (

        <header className='w-full px-6 py-3 flex justify-between border-b border-gray-border'>
            <div className='flex gap-12 items-center'>
                <div>
                    <img className='w-36' src={ options?.page_optimizer_base ? (options?.page_optimizer_base + `/logo.svg`) : '/logo.svg'} alt='RapidLoad - #1 to unlock breakneck page speed'/>
                </div>
                <div className='flex flex-column items-center gap-4'>
                    <div className='flex dark:bg-zinc-700 bg-[#eff1f5] rounded-2xl cursor-pointer'>
                        <div onClick={() => desktopButtonClick(true)} className={`text-sm flex flex-column gap-2 px-5 py-3 dark:bg-zinc-800 font-medium rounded-2xl border ${isDesktop? 'bg-white border-gray-300':'border-[#eff1f5] '}`}>
                            <ComputerDesktopIcon  className="h-5 w-5 font-medium dark:text-zinc-500 text-[#7f54b3]" /> Desktop
                        </div>
                        <div onClick={() => desktopButtonClick(false)} className={`text-sm flex flex-column gap-2 px-5 py-3 dark:bg-zinc-800 font-medium rounded-2xl  border ${isDesktop? 'border-[#eff1f5]':'bg-white border-gray-300'}`}>
                            <DevicePhoneMobileIcon  className="h-5 w-5 font-medium dark:text-zinc-500 text-[#7f54b3]" /> Mobile
                        </div>
                    </div>

                </div>
            </div>


            <div className='flex gap-8 items-center'>
                <TooltipText onClick={() => { setShowOptimizer(false) }} text='Close Optimizer'>
                    <XMarkIcon className="h-6 w-6 dark:text-zinc-300 text-zinc-600" />
                </TooltipText>

            </div>
        </header>

    )
}

export default Header