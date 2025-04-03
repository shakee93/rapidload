import React, { useEffect, useState } from 'react';
import { Textarea } from 'components/ui/textarea';
import { Button } from 'components/ui/button';
import { Loader, XIcon } from 'lucide-react';
import { useToast } from 'components/ui/use-toast';
import { useAppContext } from '../../../context/app';
import AppButton from 'components/ui/app-button';
import ApiService from '../../../services/api';

interface DebugLogsProps {
    onClose: (open: boolean) => void;
}

const DebugLogs: React.FC<DebugLogsProps> = ({ onClose }) => {
    const [logs, setLogs] = useState<string[]>([]);
    const [loading, setLoading] = useState(false);  
    const { toast } = useToast();
    const { options } = useAppContext();

    useEffect(() => {
        fetchLogs();
    }, []);

    const fetchLogs = async () => {
        try {
            setLoading(true);
            const api = new ApiService(options);
            const response = await api.post('uucss_logs');
            if (response.success && response.data) {
                setLogs(response.data);
            } else {
                toast({
                    description: 'Failed to fetch debug logs',
                    variant: 'destructive',
                });
            }
        } catch (error) {
            console.error('Error fetching logs:', error);
            toast({
                description: 'Failed to fetch debug logs',
                variant: 'destructive',
            });
        } finally {
            setLoading(false);
        }
    };

    const handleClose = () => {
        onClose(false);
    };

    return (
        <div className="flex flex-col gap-4">
            <div className="relative m-4">
                {loading && (
                    <div className="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-brand-900/50 z-10">
                        <Loader className="w-6 h-6 animate-spin dark:text-brand-300" />
                    </div>
                )}
                <Textarea
                    className="min-h-[300px] resize-none"
                    value={logs.join('\n')}
                    readOnly
                />
            </div>

            <div className="border-t flex justify-end mt-4 px-4 pt-4 gap-2">
                    <AppButton onClick={fetchLogs}
                            className='mr-2 text-sm text-gray-500 bg-brand-0 hover:bg-accent border border-input dark:bg-brand-800/40 dark:text-brand-300 dark:hover:bg-brand-800/50'>
                        Refresh
                    </AppButton>
                
                    <AppButton onClick={handleClose}
                            className='mr-2 text-sm text-gray-500 bg-brand-0 hover:bg-accent border border-input dark:bg-brand-800/40 dark:text-brand-300 dark:hover:bg-brand-800/50'>
                        Close
                    </AppButton>

            </div>
        </div>
    );
};

export default DebugLogs;
