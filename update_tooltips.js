const fs = require('fs');
const filePath = 'resources/js/components/links/LinkWidgetControls.vue';
let content = fs.readFileSync(filePath, 'utf8');

// Replace static titles
content = content.replace(/<button\b([^>]*?)aria-label="([^"]*)"\s+title="\2"([^>]*?)>([\s\S]*?)<\/button>/g, (match, before, label, after, inner) => {
    let allAttrs = before + after;
    let clsMatch = allAttrs.match(/:class="([^"]*)"/);
    if (clsMatch) {
        let clsContent = clsMatch[1];
        if (clsContent.includes('toolButtonClass')) {
            allAttrs = allAttrs.replace(clsMatch[0], `:class="[...${clsContent}, 'hs-tooltip-toggle']"`);
        } else {
             allAttrs = allAttrs.replace(clsMatch[0], `:class="[${clsContent}, 'hs-tooltip-toggle']"`);
        }
    } else {
        allAttrs += ' class="hs-tooltip-toggle"';
    }
    
    return `<div class="hs-tooltip inline-block">
                    <button aria-label="${label}"${allAttrs}>${inner}
                        <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap" role="tooltip">
                            ${label}
                        </span>
                    </button>
                </div>`;
});

fs.writeFileSync(filePath, content);
console.log('Static tooltips updated');
