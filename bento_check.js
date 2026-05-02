const puppeteer = require('puppeteer');
(async () => {
    const browser = await puppeteer.launch({ args: ['--no-sandbox'] });
    const page = await browser.newPage();
    await page.goto('https://www.karde.me/nanaanaaa', { waitUntil: 'networkidle0' });
    const stats = await page.evaluate(() => {
        // Find grid items. Usually they are absolute positioned or use CSS Grid.
        // Let's find any div that is around 180-200px wide
        const divs = Array.from(document.querySelectorAll('div'));
        const widgets = divs.filter(d => {
            const rect = d.getBoundingClientRect();
            return rect.width > 150 && rect.width < 220 && rect.height > 150 && rect.height < 220;
        });
        if (widgets.length === 0) return "No widgets found";
        const w = widgets[0];
        const style = window.getComputedStyle(w);
        const rect = w.getBoundingClientRect();
        
        // Let's also find the container to calculate gap
        const container = w.parentElement;
        const containerRect = container.getBoundingClientRect();
        
        return {
            width: rect.width,
            height: rect.height,
            borderRadius: style.borderRadius,
            containerWidth: containerRect.width,
            gap: window.getComputedStyle(container).gap,
            padding: window.getComputedStyle(container).padding
        };
    });
    console.log(JSON.stringify(stats, null, 2));
    await browser.close();
})();
