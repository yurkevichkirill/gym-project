import {Suspense} from "react";
import ActivateClientForm from "./ActivateClientForm";

const ActivatePage = () => {
    return (
        <main className="mx-auto flex min-h-[70vh] max-w-2xl items-center px-6 py-16">
            <Suspense fallback={<p className="w-full text-center">Loading activation form...</p>}>
                <ActivateClientForm />
            </Suspense>
        </main>
    );
};

export default ActivatePage;
