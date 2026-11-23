// gridLayoutUtils.js
// Converts a dashboard body (rows of cells) into grid-layout-plus items.
// Each cell may carry a saved position in cell.layout = {x, y, w, h}; cells without one are
// placed below the saved ones, following the editor's row order.

export const GRID_COLUMNS = 12;
export const GRID_ROW_HEIGHT = 40; // px per grid row
export const MIN_W = 2;
export const MIN_H = 4;
export const DEFAULT_H = 14; // ~690px with 10px margins, enough for a 640x480 chart plus title/description

function isValidLayout(layout) {
    return layout
        && ['x', 'y', 'w', 'h'].every(k => Number.isInteger(Number(layout[k])) && layout[k] !== '' && layout[k] !== null);
}

function clampLayout(layout) {
    const w = Math.min(Math.max(Number(layout.w), MIN_W), GRID_COLUMNS);
    return {
        x: Math.min(Math.max(Number(layout.x), 0), GRID_COLUMNS - w),
        y: Math.max(Number(layout.y), 0),
        w,
        h: Math.max(Number(layout.h), MIN_H),
    };
}

/**
 * @param {Array<Array<Object>>} rows        dashboard body
 * @param {(cell: Object) => boolean} isRenderable  which cells get a grid item
 * @param {boolean} ignoreSaved              true to recompute every position from the row order
 * @returns {Array<{i: string, x: number, y: number, w: number, h: number}>} items keyed "row-col"
 */
export function buildGridLayout(rows, isRenderable, ignoreSaved = false) {
    const items = [];
    const unplaced = []; // rows of [key, ...] still needing a position

    (rows || []).forEach((row, r) => {
        const pending = [];
        (row || []).forEach((cell, c) => {
            if (!isRenderable(cell)) return;
            const key = `${r}-${c}`;
            if (!ignoreSaved && isValidLayout(cell.layout)) {
                items.push({ i: key, ...clampLayout(cell.layout) });
            } else {
                pending.push(key);
            }
        });
        if (pending.length) unplaced.push(pending);
    });

    // Start auto-placed charts below everything that already has a position
    let y = items.reduce((max, it) => Math.max(max, it.y + it.h), 0);

    unplaced.forEach(keys => {
        const w = keys.length === 1 ? GRID_COLUMNS : keys.length === 2 ? GRID_COLUMNS / 2 : GRID_COLUMNS / 3;
        let x = 0;
        keys.forEach(key => {
            if (x + w > GRID_COLUMNS) {
                x = 0;
                y += DEFAULT_H;
            }
            items.push({ i: key, x, y, w, h: DEFAULT_H });
            x += w;
        });
        y += DEFAULT_H;
    });

    return items;
}
