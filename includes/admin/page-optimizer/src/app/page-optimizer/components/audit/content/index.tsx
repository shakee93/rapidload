import React, {useEffect} from "react";
import Description from "app/page-optimizer/components/audit/Description";
import Settings from "app/page-optimizer/components/audit/Settings";
import {JsonView} from "react-json-view-lite";

interface AuditContentProps {
    audit: Audit;
}

import {
    RowData
} from "@tanstack/react-table";
import {transformFileType} from "lib/utils";
import FileGroup from "app/page-optimizer/components/audit/content/FileGroup";
import Treeview from "components/tree-view";


declare module '@tanstack/react-table' {
    interface TableMeta<TData extends RowData> {
        tableId: string
        type: string
    }
}



const AuditContent = ({audit}: AuditContentProps) => {


    if (audit.files?.type && !["table", "opportunity", "list", "criticalrequestchain"].includes(audit.files.type)) {
        return <JsonView data={audit} shouldInitiallyExpand={(level) => false}/>;
    }


    let remainingSettings = audit
        .settings
        // @ts-ignore
        .filter(s => ! audit.files?.grouped_items?.map(group => transformFileType(audit, group.type))
            .includes(s.category) )

    return (
        <div className="border-t w-full pt-4">
            <div className="pb-4 text-brand-700 dark:text-brand-300">
                <div className="px-4 ml-2">
                   {/*<Help audit={audit}/>*/}
                    <Description content={audit.description}/>
                </div>
            </div>

            {(audit.settings.length > 0 && remainingSettings.length > 0) && (
                <div className='flex flex-col border-t py-4 px-4 gap-3'>
                    <div className='font-medium text-sm ml-2'>
                        {audit.settings.length !== remainingSettings.length ? 'Additional Settings' : 'Recommended Settings'}
                    </div>
                    <Settings audit={audit} auditSettings={remainingSettings}/>
                </div>
            )}


            {(audit.files.type === 'criticalrequestchain') &&
                <div className='border-t'>
                    <Treeview data={audit.files?.chains[Object.keys(audit.files.chains)[0]]}/>
                </div>
            }

            {((audit.files?.type === "opportunity" || audit.files?.type === "table")) &&
                audit.files?.grouped_items?.map((group, index) =>
                    <FileGroup
                        key={index}
                        index={index}
                        audit={audit}
                        group={group}
                    />
                )
            }

            {(audit.files?.type === "list" ) && (
                <>
                    {audit.files?.items.map((list, index) =>
                        (list?.type === 'table' && list.items?.length > 0) && (
                            <FileGroup
                                key={index}
                                index={index}
                                audit={audit}
                                group={list}
                                type='list'
                            />
                        )
                    )}
                </>
            )}
        </div>
    );
};

export default AuditContent;
