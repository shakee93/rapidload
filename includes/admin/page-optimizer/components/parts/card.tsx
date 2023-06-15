import {FC, ReactNode} from "react";

interface CardProps {
    children: ReactNode;
    cls?: string;
    padding?: string;
}

const Card: FC<CardProps> = ({ children, cls, padding }) => {

    let pad = padding? padding : 'py-4 px-6'
    return (
        <div className={`w-full dark:bg-zinc-700 bg-white dark:border-zinc-600 border w-full rounded-2xl ${pad} ${cls}`}>
            {children}
        </div>
    )
}

export default Card