import { useEffect, useState } from 'react';

/** Tunda perubahan nilai — dipakai kotak pencarian tabel. */
export function useDebounce<T>(nilai: T, jeda = 300): T {
    const [tertunda, setTertunda] = useState(nilai);

    useEffect(() => {
        const timer = setTimeout(() => setTertunda(nilai), jeda);

        return () => clearTimeout(timer);
    }, [nilai, jeda]);

    return tertunda;
}
