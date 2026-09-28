export function useSession() {
    const element = document.getElementById('app-session');

    return element ? JSON.parse(element.textContent) : null;
}
