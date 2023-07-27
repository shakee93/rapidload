import React, {useState} from "react";
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger} from "components/ui/tooltip";
import {ArrowTopRightOnSquareIcon} from "@heroicons/react/24/outline";
import {CircleDashed} from "lucide-react";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue
} from "components/ui/select";
import prettyBytes from "pretty-bytes";
import prettyMilliseconds from "pretty-ms";
import {CellContext} from "@tanstack/react-table";
import {truncateMiddleOfURL} from "lib/utils";
import AuditColumnUrl from "app/page-optimizer/components/audit/files/columns/url";
import AuditColumnDropdown from "app/page-optimizer/components/audit/files/columns/dropdown";
import AuditNodeColumn from "app/page-optimizer/components/audit/files/columns/node";


interface AuditColumnProps {
    audit: Audit
    heading: AuditHeadings,
    cell: CellContext<AuditFile, any>,
}

const AuditColumns = ({ audit, heading, cell } : AuditColumnProps) => {

    let value = cell.getValue()

    if (heading.valueType === 'url') {
        return <AuditColumnUrl audit={audit} cell={cell}/>
    }

    if (heading.control_type === 'dropdown') {
        return <AuditColumnDropdown heading={heading}/>
    }

    if (heading.valueType === 'bytes') {
        return <span>{prettyBytes(value as number)}</span>
    }

    if (['ms', 'timespanMs'].includes(heading.valueType as string)) {
        return <span>{prettyMilliseconds(cell.getValue() as number)}</span>
    }

    if (heading.valueType === 'node') {
        return <AuditNodeColumn cell={cell}/>
    }

    if (heading.valueType === 'numeric' && typeof value === 'number') {
        return <span>{(value as number)}</span>
    }

    if (heading.valueType === 'numeric' && typeof value === 'object') {
        return <span>{(value.value as number)}</span>
    }

    console.log('col', value);
    return <span>{value}</span>;

}

export default AuditColumns