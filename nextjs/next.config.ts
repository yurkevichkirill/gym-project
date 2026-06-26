import {NextConfig} from "next";

const nextConfig: NextConfig = {
    output: "standalone",
    images: {
        remotePatterns: [
            {
                protocol: "http",
                hostname: "localhost",
                port: "9005",
                pathname: "/evogym-bucket/**",
            },
            {
                protocol: "http",
                hostname: "minio",
                port: "9000",
                pathname: "/evogym-bucket/**",
            },
        ],
        dangerouslyAllowLocalIP: process.env.NODE_ENV === "development",
    },
};

module.exports = nextConfig;
