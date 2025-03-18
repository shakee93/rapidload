import React, { useState, useEffect } from 'react';
import Card from "components/ui/card";
import { ArrowRightIcon, CheckBadgeIcon, InboxIcon, InformationCircleIcon } from "@heroicons/react/24/outline";
import { AIBotIcon, BotIcon } from "app/dashboard/components/icons/icon-svg";
import { ArrowUpCircleIcon } from "@heroicons/react/24/solid";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "components/ui/dialog";
import { MessagesSquare, Lock } from "lucide-react";
import { AnimatedLogo } from "components/animated-logo";
import { cn } from 'lib/utils';
import useCommonDispatch from 'hooks/useCommonDispatch';
import TooltipText from "components/ui/tooltip-text";
import Mode from 'app/page-optimizer/components/Mode';

const AIBot = ({ className }: { className: string }) => {
    const [open, setOpen] = useState(false);
    const { licenseConnected } = useCommonDispatch()
    const [conversations, setConversations] = useState(() => {
        const saved = localStorage.getItem('chat-conversations');
        return saved ? JSON.parse(saved) : [];
    });

    const handleConversationSelect = (conv: any) => {
        // Add conversation ID to URL hash
        window.location.hash = `#/rapidload-ai?conv=${conv.id}`;
        setOpen(false);
    };

    const questions = [
        'CSS Delivery?',
        'JS Delivery?',
        'Image Delivery?',
        'CDN Delivery?',
    ];

    return (
        <div className={cn("w-full flex flex-col gap-4", className)}>
            <Card data-tour='AIBot' className="border flex flex-col gap-4">
                <div className="flex flex-col items-center p-6 gap-2 relative">
                    <button
                        onClick={() => setOpen(true)}
                        className="flex gap-1 m-6 dark:bg-brand-800 dark:border-brand-600 dark:border dark:hover:bg-brand-600/40 my-0 right-0 absolute cursor-pointer border text-brand-950 py-1.5 px-2 rounded-lg text-xs font-medium hover:bg-gray-100">
                        <InboxIcon className="h-4 w-4 text-brand-950 dark:text-brand-300" />
                    </button>

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogContent className="sm:max-w-[425px] p-6">
                            <DialogHeader>
                                <DialogTitle>Chat History</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-2 max-h-[400px] overflow-y-auto">
                                {conversations.length === 0 ? (
                                    <div className="text-center text-gray-500 py-4">
                                        No chat history yet
                                    </div>
                                ) : (
                                    conversations.map((conv: any) => (
                                        <div
                                            key={conv.id}
                                            onClick={() => handleConversationSelect(conv)}
                                            className="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-100 cursor-pointer dark:bg-brand-800/40 dark:text-brand-300 dark:hover:bg-brand-800/50"
                                        >
                                            <MessagesSquare className="h-4 w-4 text-brand-950 dark:text-brand-300" />
                                            <span className="text-sm truncate">{conv.title}</span>
                                        </div>
                                    ))
                                )}
                            </div>
                        </DialogContent>
                    </Dialog>

                    <div className="flex flex-col items-center gap-2 py-[83px]">
                        {/* <AIBotIcon/> */}
                        <AnimatedLogo className="!opacity-100" size="lg" isPlaying={false} />
                        <div className="dark:text-brand-300 text-center flex flex-col">
                            <h2 className="text-base font-semibold">Chat with Rapidload AI</h2>
                            <p className="text-sm text-brand-500 dark:text-brand-300/80">Understands your page speed and website issues to help you improve speed</p>
                        </div>
                    </div>
                </div>
                <div className="w-full max-w-md mx-auto p-6 pt-0">
                    {/* <h2 className="text-sm font-semibold mb-2">Suggested Questions</h2>
                    <div className="grid grid-cols-2 gap-4 mb-4">
                        {questions.map((question, index) => (
                            <button
                                key={index}
                                className="text-left bg-brand-100/30 border items-center rounded-3xl hover:bg-brand-200 text-xs flex justify-between p-2 font-medium"
                            >
                                {question}
                                <ArrowRightIcon className="h-4 w-4 text-brand-950 "/>
                            </button>
                        ))}
                    </div> */}
                    <div className="relative">
                        <input
                            type="text"
                            disabled={!licenseConnected}
                            onClick={() => (window.location.hash = '#/rapidload-ai')}
                            placeholder="Ask about anything..."
                            className="w-full p-2 border border-brand-200 dark:bg-brand-600/40 dark:text-brand-300 dark:border-brand-700 rounded-full focus:outline-none focus:border-black bg-brand-100 pr-10 pl-4"
                        />
                        {
                            !licenseConnected ? (
                                <div className='absolute right-4 top-1/2 transform -translate-y-1/2 dark:text-brand-300'>
                                <TooltipText
                                            text={<><span className='text-purple-750 font-medium'>PRO</span> feature</>}>
                                        <Lock className='w-4 text-brand-400 dark:text-brand-300'/>
                                    </TooltipText>
                                </div>
                                
                            ):(
                                <ArrowUpCircleIcon
                                    className="cursor-pointer h-10 w-10 text-brand-950 absolute right-0 top-1/2 transform -translate-y-1/2 dark:text-brand-300" />
                            )
                        }
                    </div>


                </div>
            </Card>
        </div>
    );
}

export default AIBot;