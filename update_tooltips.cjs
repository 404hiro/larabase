const fs = require('fs');
const filePath = 'resources/js/components/links/LinkWidgetControls.vue';
let content = fs.readFileSync(filePath, 'utf8');

// Replace dynamic tooltips (v-for options and colors)
// colorSwatches
content = content.replace(/<button\s+v-for="color in colorSwatches"([\s\S]*?)<\/button>/, (match, inner) => {
    return `<div v-for="color in colorSwatches" :key="color" class="hs-tooltip inline-block">
                <button
                    :aria-label="\`背景色 \${color}\`"
                    :style="{ backgroundColor: color }"
                    :class="[...colorButtonClass(color), 'hs-tooltip-toggle']"
                    @click.prevent.stop="setBackgroundColor(color)"
                >
                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap" role="tooltip">
                        {{ color }}
                    </span>
                </button>
            </div>`;
});

// sizeOptions
content = content.replace(/<button\s+v-for="option in sizeOptions"([\s\S]*?)<\/button>/, (match) => {
    return `<div
                v-for="option in sizeOptions"
                :key="option.key"
                class="hs-tooltip inline-block"
            >
                <button
                    :aria-label="option.label"
                    @click.prevent.stop="emit('resize', option.size)"
                    :class="[...sizeButtonClass(option.size), 'hs-tooltip-toggle']"
                >
                    <span
                        v-if="widget.type !== 'section' && option.key !== 'inline'"
                        :class="sizeIconClass(option.key, isWidgetSize(option.size))"
                    ></span>
                    <component
                        v-else
                        :is="option.icon"
                        :class="mode === 'desktop' ? 'size-4' : 'size-3.5'"
                    />
                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap" role="tooltip">
                        {{ option.label }}
                    </span>
                </button>
            </div>`;
});

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
