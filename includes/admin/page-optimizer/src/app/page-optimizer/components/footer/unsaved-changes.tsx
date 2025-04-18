import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter,
    AlertDialogHeader, AlertDialogTitle,
    AlertDialogTrigger
} from "components/ui/alert-dialog";
import {ReactNode, useEffect} from "react";
import {useSelector} from "react-redux";
import {optimizerData} from "../../../../store/app/appSelector";
import useSubmitSettings from "hooks/useSubmitSettings";
import {XIcon} from "lucide-react";
import { Cancel } from "@radix-ui/react-alert-dialog"
import TooltipText from "components/ui/tooltip-text";
import {setCommonState} from "../../../../store/common/commonActions";
import {ThunkDispatch} from "redux-thunk";
import {AppAction, RootState} from "../../../../store/app/appTypes";
import useCommonDispatch from "hooks/useCommonDispatch";
import {useAppContext} from "../../../../context/app";
import {fetchSettings} from "../../../../store/app/appActions";

interface Props {
    children: ReactNode
    onClick: () => void
    onCancel?: () => void
    title?: string
    description?: string
    action?: string
    cancel?: string
    performanceGear?: boolean
}

const UnsavedChanges = ({children , onClick, title, description, action = 'Save & Exit', onCancel, cancel, performanceGear }: Props) => {

    const { touched, fresh, settings, settingsOriginal } = useSelector(optimizerData)
    const { submitSettings } = useSubmitSettings()

    // console.log(settings[0].inputs[0].value, settingsOriginal[0].inputs[0].value);
    // console.log(touched, fresh)
    if(!performanceGear){
        
        if (!(fresh ? true : touched)) {
            return <div onClick={e => onClick()} >
                {children}
            </div>
        }
    }

    const submit = async () => {

        //await submitSettings(action === 'Save & Analyze');
        if(!performanceGear){
            await submitSettings(false);
        }
        onClick();


    }

    // useEffect(() => {
    //    if(!loading){
    //        dispatch(fetchSettings(options, options.optimizer_url, true))
    //    }
    // }, [loading]);

    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <div>
                    {children}
                </div>
            </AlertDialogTrigger>
            <AlertDialogContent>
               <div>
                   <AlertDialogHeader>
                       <AlertDialogTitle className='flex justify-between items-center'>{title ? title: 'Continue without Saving?'}

                           <TooltipText text='Close the Alert'>
                               <Cancel asChild>
                                   <XIcon className='stroke-1'/>
                               </Cancel>
                           </TooltipText>
                       </AlertDialogTitle>
                       <AlertDialogDescription>
                           <div>
                               {description ? description : 'You have changes that haven\'t been saved. If you are leave now, your edits will be lost.'}
                           </div>
                       </AlertDialogDescription>

                   </AlertDialogHeader>
                   <AlertDialogFooter>
                       <AlertDialogAction onClick={async e => submit()}>{action}</AlertDialogAction>

                       <AlertDialogCancel onClick={e => onCancel && onCancel() }>
                           {cancel ? cancel : 'Discard'}
                       </AlertDialogCancel>
                   </AlertDialogFooter>
               </div>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export default UnsavedChanges