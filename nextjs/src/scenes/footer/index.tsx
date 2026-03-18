import Logo from "../../assets/Logo.png";
import Image from "next/image";

const Footer = () => {
    return <footer className="bg-primary-100 py-16">
        <div className="justify-content mx-auto w-5/6 gap-16 md:flex">
            <div className="mt-16 basis-1/2 md:mt-0">
                <Image alt='logo' src={Logo} />
                <p className="my-5">
                    Elite iron. Savage trainers. Brutal classes.
                    We forge unbreakable warriors who dominate
                    their physique and shatter genetic limits.
                    Destroy weakness daily.
                </p>
                <p>© Evogym All Rights Reserved.</p>
            </div>
            <div className="mt-16 basis-1/4 md:mt-0">
                <h4 className="font-bold">Links</h4>
                <p className="my-5">
                    <a href="https://github.com/yurkevichkirill">GitHub</a>
                </p>
                <p className="my-5">
                    <a href="https://www.linkedin.com/in/kirill-yurkevich-8837332b7/">LinkedIn</a>
                </p>
                <p>
                    <a href="https://innowise.com/">Innowise</a>
                </p>
            </div>
            <div className="mt-16 basis-1/4 md:mt-0">
                <h4 className="font-bold">Contact Us</h4>
                <p className="my-5">Av. de Concha Espina, 1, Chamartín, 28036 Madrid, Spain</p>
                <p>(228) 666-1488</p>
            </div>
        </div>
    </footer>;
}

export default Footer;
