import React, {useState} from "react";
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger} from "components/ui/tooltip";
import {isImageAudit, isUrl, truncateMiddleOfURL} from "lib/utils";
import {ArrowTopRightOnSquareIcon} from "@heroicons/react/24/outline";
import {CircleDashed} from "lucide-react";
import {CellContext} from "@tanstack/react-table";
import AuditColumnImage from "app/page-optimizer/components/audit/files/columns/image";
import Code from "components/ui/code";

interface AuditColumnUrlProps {
    audit : Audit,
    cell: CellContext<AuditFile, any>
}

const AuditColumnUrl = ({audit, cell} : AuditColumnUrlProps) => {


    let value = cell.getValue() as string
    
    if (isImageAudit(audit.id)) {
        return <AuditColumnImage cell={cell}/>
    } else if (isUrl(value)) {
        return (
            <a className='flex gap-2' target='_blank'
               href={value}>{truncateMiddleOfURL(value, 50)}
                <ArrowTopRightOnSquareIcon className='w-4'/> </a>
        )
    } else {
        return <Code code={value}></Code>
    }
}

export default AuditColumnUrl