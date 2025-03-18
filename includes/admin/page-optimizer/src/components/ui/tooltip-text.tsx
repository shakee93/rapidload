import {Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from "components/ui/tooltip";
import {Undo2} from "lucide-react";
import {ReactNode, MouseEvent, forwardRef} from "react";
import {cn} from "lib/utils";
import {TooltipPortal} from "@radix-ui/react-tooltip";

interface TooltipTextProps {
    text: string | ReactNode
    children: ReactNode,
    className?: string
    onClick?: () => void,
    asChild?: boolean
    delay?: number
    leftOffset?: string
}

const TooltipText = forwardRef<HTMLButtonElement, TooltipTextProps>(
    ({ text, children, onClick, className, asChild = false, delay = 500, leftOffset }, ref) => {
    return (
        text != null ? (
        <TooltipProvider disableHoverableContent={false} delayDuration={delay}>
            <Tooltip>
                <TooltipTrigger ref={ref} asChild={asChild} onClick={e => onClick && onClick()} className={cn(
                    'flex items-center',
                )}>
                    {children}
                </TooltipTrigger>
                <TooltipContent className={cn(
                    className
                )}>{text}</TooltipContent>
            </Tooltip>
        </TooltipProvider>
    ):(children)
    )
});

TooltipText.displayName = 'TooltipText';

export default TooltipText