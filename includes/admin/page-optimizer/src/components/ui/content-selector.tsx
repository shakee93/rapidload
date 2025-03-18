import React, {useEffect, useState, useRef} from 'react';
import { ArrowRightIcon, PlusIcon, ArrowLeftIcon } from "@heroicons/react/24/outline";
import {fetchReport, fetchSettings, searchData} from "../../store/app/appActions";
import useCommonDispatch from "hooks/useCommonDispatch";
import {useAppContext} from "../../context/app";
import {setCommonRootState, setCommonState} from "../../store/common/commonActions";
import TableSkeleton from "components/ui/TableSkeleton";
import {Skeleton} from "components/ui/skeleton";

interface ContentSelectorProps {
    data: any;
    onOpenChange: (open: boolean) => void;
}

const ContentSelector: React.FC<ContentSelectorProps> = ({ data, onOpenChange }) => {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedContent, setSelectedContent] = useState(null);
    const [searchResults, setSearchResults] = useState([]);
    const [noResults, setNoResults] = useState(false);
    const { dispatch } = useCommonDispatch();
    const {options} = useAppContext();
    const [loading, setLoading] = useState(false);
    const abortControllerRef = useRef<AbortController | null>(null);

    // const filteredContent = data.filter(content =>
    //     content.post_type.toLowerCase().includes(searchTerm.toLowerCase())
    // );

    const filteredContent = data
        ? data.filter(content =>
            content.post_type.toLowerCase().includes(searchTerm.toLowerCase())
        )
        : [];

    const filteredSelectedList = selectedContent
        ? data.find(item => item.post_type === selectedContent)?.links || []
        : [];



    const handleSearchChange = (e) => {
        setSearchTerm(e.target.value);
        setNoResults(false);
    };

    const handleContentClick = (postType) => {
        setSelectedContent(postType);
        setSearchResults([]);
    };

    const handleBackClick = () => {
        setSelectedContent(null);
        setSearchTerm('');
        setSearchResults([]);
    };

    const handleItemClick = (item: any) => {
        dispatch(setCommonRootState('headerUrl', item.permalink));
        dispatch(fetchReport(options, item.permalink, true, true));
        dispatch(fetchSettings(options, item.permalink, true));
        window.location.hash = '#/optimize';
        onOpenChange(false);
    };


    useEffect(() => {
        const searchForData = async () => {
            if (searchTerm.length < 3 || !selectedContent) {
                setSearchResults([]);
                return;
            }


            try {
                setLoading(true);
                const response = await dispatch(searchData(options, 'rapidload_fetch_post_search_by_title_or_permalink', searchTerm, selectedContent));

                if (response.success) {
                    setSearchResults(response.data);
                    setNoResults(false);
                } else {
                    setSearchResults([]);
                    if (searchTerm) {
                        setNoResults(true);
                    }
                }
            } catch (error) {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    console.error('Search aborted:', error);
                } else {
                    console.error('Error fetching optimization data:', error);
                }
            } finally {
                    setLoading(false);

            }
        };

        const debounceTimeout = setTimeout(() => {
            searchForData();
        }, 300);

        return () => {
            clearTimeout(debounceTimeout);

        };
    }, [searchTerm, selectedContent, dispatch, options]);


    useEffect(() => {
        if (!data) {
            setLoading(true);
        } else {
            setLoading(false);
        }
    }, [data]);

    return (
        <>
            {/* Header Section */}
            <div className="flex items-center px-6 py-3 ">
                {selectedContent && (
                    <button onClick={handleBackClick} className="mr-3">
                        <ArrowLeftIcon className="h-6 w-6 text-gray-500 dark:text-brand-300" />
                    </button>
                )}
                <div className="text-gray-900 font-medium text-lg dark:text-brand-300">
                    {selectedContent
                        ? `Select ${selectedContent.charAt(0).toUpperCase() + selectedContent.slice(1)}`
                        : 'Select a content to Optimize'}
                </div>
            </div>

            {/* Search Bar */}
            <div className="border-t border-1 px-6 pt-6">
                <input
                    type="text"
                    placeholder={selectedContent ? `Search ${selectedContent}` : 'Search content types'}
                    value={searchTerm}
                    onChange={handleSearchChange}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-gray-400 dark:text-brand-300 dark:bg-brand-950 dark:border-brand-800/80 dark:focus:border-brand-600/40"
                />

                {/* Content list changes dynamically based on `selectedContent` */}
                <ul className="overflow-y-auto mt-4 max-h-72">
                    {loading ?
                        <div className="flex gap-4"><Skeleton className="w-72 h-10 "/><Skeleton className="w-72 h-10 "/></div>
                        : !selectedContent ? (
                            // Main content list: post types
                            filteredContent.map(content => (
                                <li
                                    key={content.post_type}
                                    className="py-4 cursor-pointer hover:bg-gray-50 rounded-xl dark:hover:bg-brand-800/40"
                                    onClick={() => handleContentClick(content.post_type)}
                                >
                                    <div className="flex mx-6 justify-between items-center dark:text-brand-300">
                                        <div className="flex gap-4">
                                            <div
                                                className="text-gray-900 font-medium capitalize dark:text-brand-300">{content.post_type}</div>
                                            <span
                                                className="text-gray-500 text-xs border border-0.5 rounded-lg px-2 py-0.5 bg-brand-0 dark:text-brand-300 dark:bg-brand-800/40">
                                             {content.links.length} {content.post_type}
                                        </span>
                                        </div>

                                        <ArrowRightIcon className="h-6 w-6 text-gray-500 dark:text-brand-300"/>
                                    </div>
                                </li>
                            ))
                        ) : (
                            // Detailed list for selected post type: links
                            searchTerm && searchResults.length > 0 ? (
                                searchResults.map(link => (
                                    <li
                                        key={link.permalink}
                                        className="py-4 cursor-pointer hover:bg-gray-50 rounded-xl dark:hover:bg-brand-800/40"
                                        onClick={() => handleItemClick(link)}
                                    >
                                        <div className="flex mx-6 justify-between items-center dark:text-brand-300">
                                            <div className="text-gray-900 font-medium dark:text-brand-300">{link.title}</div>
                                            <PlusIcon className="h-6 w-6 text-gray-500 dark:text-brand-300"/>
                                        </div>
                                    </li>
                                ))
                            ) : noResults ? (
                                <div className="text-gray-500 text-center py-4">No results found</div>
                            ) : searchTerm.length < 3 && (
                                filteredSelectedList.map(link => (
                                    <li
                                        key={link.permalink}
                                        className="py-4 cursor-pointer hover:bg-gray-50 rounded-xl dark:hover:bg-brand-800/40"
                                        onClick={() => handleItemClick(link)}
                                    >
                                        <div className="flex mx-6 justify-between items-center dark:text-brand-300">
                                            <div className="text-gray-900 font-medium dark:text-brand-300">{link.title}</div>
                                            <PlusIcon className="h-6 w-6 text-gray-500 dark:text-brand-300"/>
                                        </div>
                                    </li>
                                ))
                            )
                        )}
                </ul>
            </div>
        </>
    );
};

export {ContentSelector};
