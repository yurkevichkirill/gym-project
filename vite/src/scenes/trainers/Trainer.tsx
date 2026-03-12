type Props = {
    id: number,
    name: string,
    trainingTypeName: string,
    pricePerHour: number,
}

const Trainer = ({ id, name, trainingTypeName, pricePerHour }: Props) => {
    return <div>
        <p>{ id }</p>
        <p>{ name }</p>
        <p>{ trainingTypeName }</p>
        <p>{ pricePerHour }</p>
    </div>
}

export default Trainer;