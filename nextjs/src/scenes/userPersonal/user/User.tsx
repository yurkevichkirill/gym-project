type Props = {
    id: number,
    age: number,
    firstName: string,
    lastName: string,
    email: string,
    phone: string,
    createdAt: string,
    balance: string,
}

const User =
    ({
          id,
          age,
          firstName,
          lastName,
          email,
          phone,
          createdAt,
          balance
     }: Props
    ) => {
    return (
        <div>
            <p>{id}</p>
            <p>{age}</p>
            <p>{firstName}</p>
            <p>{lastName}</p>
            <p>{email}</p>
            <p>{phone}</p>
            <p>{createdAt}</p>
            <p>{balance}</p>
        </div>
    );
}

export default User;