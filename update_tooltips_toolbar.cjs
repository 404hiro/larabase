const fs = require('fs');
const filePath = 'resources/js/components/links/LinkToolbar.vue';
let content = fs.readFileSync(filePath, 'utf8');

// Replace dynamic tooltips (v-for options)
content = content.replace(/<button\s+v-for="option in mobileSizeOptions"([\s\S]*?)<\/button>/, (match) => {
    return `<div v-for="option in mobileSizeOptions" :key="option.key" class="hs-tooltip inline-block">
                <button
                    type="button"
                    :aria-label="option.label"
                    class="flex size-8 items-center justify-center rounded-lg transition-colors hs-tooltip-toggle"
                    :class="
                        isActiveMobileSize(option.size)
                            ? 'bg-white text-gray-950 shadow-sm'
                            : 'text-white/70 hover:bg-white/10 hover:text-white'
                    "
                    @click.stop="emit('resizeMobileWidget', option.size)"
                >
                    <span
                        v-if="activeMobileWidget?.type !== 'section' && option.key !== 'inline'"
                        :class="mobileSizeIconClass(option)"
                    ></span>
                    <component v-else :is="option.icon" class="size-3.5" />
                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap" role="tooltip">
                        {{ option.label }}
                    </span>
                </button>
            </div>`;
});

// Replace static titles for <button>
content = content.replace(/<button\b([^>]*?)aria-label="([^"]*)"\s+title="\2"([^>]*?)>([\s\S]*?)<\/button>/g, (match, before, label, after, inner) => {
    let allAttrs = before + after;
    // append hs-tooltip-toggle to class string
    let clsMatch = allAttrs.match(/class="([^"]*)"/);
    if (clsMatch) {
        allAttrs = allAttrs.replace(clsMatch[0], `class="${clsMatch[1]} hs-tooltip-toggle"`);
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

// Replace static titles for <InertiaLink>
content = content.replace(/<InertiaLink\b([^>]*?)aria-label="([^"]*)"\s+title="\2"([^>]*?)>([\s\S]*?)<\/InertiaLink>/g, (match, before, label, after, inner) => {
    let allAttrs = before + after;
    let clsMatch = allAttrs.match(/class="([^"]*)"/);
    if (clsMatch) {
        allAttrs = allAttrs.replace(clsMatch[0], `class="${clsMatch[1]} hs-tooltip-toggle"`);
    } else {
        allAttrs += ' class="hs-tooltip-toggle"';
    }
    
    return `<div class="hs-tooltip inline-block">
                <InertiaLink aria-label="${label}"${allAttrs}>${inner}
                    <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap" role="tooltip">
                        ${label}
                    </span>
                </InertiaLink>
            </div>`;
});

fs.writeFileSync(filePath, content);
console.log('Toolbar tooltips updated');
