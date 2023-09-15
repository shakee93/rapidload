import {useCallback, useEffect, useState} from "react";

const usePerformanceColors = (performance: number) => {

    const [performanceIcon, setPerformanceIcon] = useState('fail');
    const [progressbarColor, setProgressbarColor] = useState('#ECECED');
    const [progressbarBg, setProgressbarBg] = useState('#ECECED');

    const progressBarColorCode = useCallback( () => {
        const bgOpacity = 0.05

        if (performance < 50) {
            setProgressbarColor('#ff4e43');
            setProgressbarBg(`rgb(255, 51, 51, ${bgOpacity} )`);
            setPerformanceIcon('fail')
        } else if (performance < 90) {
            setProgressbarColor('#FFAA33');
            setProgressbarBg(`rgb(255, 170, 51, ${bgOpacity})`);
            setPerformanceIcon('average')
        } else if (performance < 101) {
            setProgressbarColor('#09B42F');
            setProgressbarBg(`rgb(9, 180, 4, ${bgOpacity})`);
            setPerformanceIcon('pass')
        }
    }, [performance]);

    useEffect(() => {
        progressBarColorCode()
    }, [])

    return [performanceIcon, progressbarColor, progressbarBg]
}

export default usePerformanceColors