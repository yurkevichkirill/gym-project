import {User} from "@/types/auth.type";

export default interface ClientType extends User {
    age: number,
    balance: number,
}
