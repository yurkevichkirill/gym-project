import type {SelectedPage} from "@/shared/types";
import {usePathname} from "next/navigation";
import Link from "next/link";

type Props = {
    page: string;
    selectedPage: SelectedPage,
    setSelectedPage: (value: SelectedPage) => void,
}

const NavLink = ({
    page,
    selectedPage,
    setSelectedPage,
}: Props) => {
    const path = usePathname();
    const lowerCasePage = page.toLowerCase().replace(/ /g, "") as SelectedPage;

    const isHome = path === "/";

    return isHome ?
            <a
                className={`${selectedPage === lowerCasePage ? "text-primary-500" : ""}
                transition duration-500 hover:text-primary-300
            `}
                href={`#${lowerCasePage}`}
                onClick={() => setSelectedPage(lowerCasePage)}
            >
                {page}
            </a>
            :
            <Link href={`/#${lowerCasePage}`}>
                {page}
            </Link>
};

export default NavLink;