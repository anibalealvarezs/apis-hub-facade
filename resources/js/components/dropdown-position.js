export function measurePanel(panel) {
    const style = panel.style;
    const prev = {
        display: style.display,
        position: style.position,
        top: style.top,
        left: style.left,
        visibility: style.visibility,
        zIndex: style.zIndex,
    };
    style.display = 'block';
    style.position = 'fixed';
    style.top = '-9999px';
    style.left = '-9999px';
    style.visibility = 'hidden';
    style.zIndex = '-1';
    const height = panel.offsetHeight;
    Object.assign(style, prev);
    return height || 320;
}

export function computeDropUp(trigger, panel) {
    if (!trigger || !panel) return false;
    const panelHeight = measurePanel(panel);
    const rect = trigger.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    return spaceBelow < panelHeight + 8 && rect.top > panelHeight + 8;
}
