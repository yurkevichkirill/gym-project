import React from "react";
import { SelectedPage } from "./types";

type Props = {
    children: React.ReactNode;
    setSelectedPage: (value: SelectedPage) => void;
    targetPage?: SelectedPage;
}

const ActionButton = ({ children, setSelectedPage, targetPage = SelectedPage.Memberships }: Props) => {
    return (
        <a
            className="rounded-md bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white"
            onClick={() => setSelectedPage(targetPage)}
            href={`#${targetPage}`}
        >
            { children }
        </a>
    )
}

export default ActionButton