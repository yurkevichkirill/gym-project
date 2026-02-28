import { Routes, Route } from 'react-router-dom'
import MainPage from "./pages/MainPage.tsx";

function App() {
    return (
        <Routes>
            <Route index element={<MainPage />} />
        </Routes>
    )
}

export default App
