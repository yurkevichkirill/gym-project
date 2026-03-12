import { Routes, Route } from 'react-router-dom'
import MainPage from "./pages/MainPage.tsx";
import TrainersPage from "./pages/TrainersPage.tsx";

function App() {
    return (
        <Routes>
            <Route index element={<MainPage />} />
            <Route path="trainers" element={<TrainersPage />}></Route>
        </Routes>
    );
}

export default App
