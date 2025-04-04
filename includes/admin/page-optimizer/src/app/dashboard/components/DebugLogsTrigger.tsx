import React from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger
} from "components/ui/dialog";
import AppButton from "components/ui/app-button"
import { PlusIcon } from "@heroicons/react/24/outline";
import TooltipText from "components/ui/tooltip-text";
import {BugAntIcon} from "@heroicons/react/20/solid";
import DebugLogs from "app/dashboard/components/debugLogs";

interface MyDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

const DebugLogsTrigger: React.FC<MyDialogProps> = ({ open, onOpenChange }) => {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogTrigger asChild>
                <div data-debug-logs-trigger>
                    <TooltipText text={`Debug Logs`}>
                        {/* <BugAntIcon className='w-5 text-brand-400'/> */}
                        <span className='underline'>View Logs</span>
                    </TooltipText>
                </div>
            </DialogTrigger>
            
            <DialogContent className="sm:max-w-[650px]">
                <DialogTitle></DialogTitle>
                <DialogHeader className='border-b px-6 py-4 mt-1'>
                    <DialogTitle>Debug Logs</DialogTitle>
                </DialogHeader>
                <div className="py-2">
                    <DebugLogs onClose={onOpenChange}/>
                </div>
                <DialogDescription>
                    {/* Additional description if needed */}
                </DialogDescription>
            </DialogContent>
        </Dialog>
    );
};

export default DebugLogsTrigger; 