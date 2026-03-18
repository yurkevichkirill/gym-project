import {NextConfig} from "next";

const nextConfig: NextConfig = {
    images: {
        remotePatterns: [
            {
                protocol: "http",
                hostname: "nginx",
                port: "",
                pathname: "/uploads/**",
            },
        ],
        dangerouslyAllowLocalIP: process.env.NODE_ENV === "development",
    },
};

module.exports = nextConfig;