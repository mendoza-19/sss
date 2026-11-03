async function getSVGforURL(url) {
    try {
        const response = await fetch(url);
        const svgText = await response.text();
        const container = document.createElement('div');
        container.innerHTML = svgText;
        const svg = container.querySelector('svg');
        return svg;
    } catch (error) {
        console.error(error);
        return document.createElement('svg');
    }
}