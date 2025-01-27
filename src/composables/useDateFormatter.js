export function useDateFormatter() {
    function formatDate(isoDate) {
        const date = new Date(isoDate);
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const formattedDate = date.toLocaleDateString('de-DE');
        
        return `${formattedDate} ${hours}:${minutes}`;
    }

    return { formatDate };
}
