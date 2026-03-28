const Section = ({ title, children }: { title: string, children: React.ReactNode }) => (
    <div className="rounded-2xl shadow-md p-6">
        <h3 className="text-xl font-semibold mb-4">
            {title}
        </h3>
        {children}
    </div>
);

export default Section;