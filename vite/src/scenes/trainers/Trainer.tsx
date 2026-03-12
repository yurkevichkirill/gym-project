type Props = {
    id: number,
    name: string,
    trainingTypeName: string,
    pricePerHour: number,
    photoUrl: string,
}

const Trainer = ({ name, photoUrl }: Props) => {
    return <div className="border rounded-lg">
        <img src={ photoUrl } alt="Avatar" className="w-80 h-100"/>
        <h4 className="font-bold">{ name }</h4>
    </div>
}

export default Trainer;