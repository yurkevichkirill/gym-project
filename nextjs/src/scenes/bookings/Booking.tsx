type Props = {
    id: number,
    trainerId: number,
    bookedAt: string,
    date: string,
    durationMinutes: number,
    startTime: string,
    status: string,
}

const Booking = ({ id, trainerId, bookedAt, date, durationMinutes, startTime, status }: Props) => {
    return (
        <>
            <p>{id}</p>
            <p>{trainerId}</p>
            <p>{bookedAt}</p>
            <p>{date}</p>
            <p>{durationMinutes}</p>
            <p>{startTime}</p>
            <p>{status}</p>
        </>
    );
}

export default Booking;