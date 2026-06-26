import type { NextRequest } from "next/server";
import { NextResponse } from "next/server";

const AUTH_COOKIE_NAMES = ["access_token", "refresh_token"] as const;

export function proxy(request: NextRequest) {
    const hasAuthCookie = AUTH_COOKIE_NAMES.some((name) => request.cookies.has(name));

    if (hasAuthCookie) {
        return NextResponse.next();
    }

    const loginUrl = request.nextUrl.clone();
    loginUrl.pathname = "/";
    loginUrl.search = "";
    loginUrl.searchParams.set("login", "required");

    return NextResponse.redirect(loginUrl);
}

export const config = {
    matcher: [
        "/me/:path*",
        "/trainer/:path*",
        "/admin/:path*",
    ],
};
