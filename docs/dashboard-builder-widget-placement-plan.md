# Plan: Dashboard Builder Widget Placement & Sticky Canvas Add Button

## 1. Overview & Objective
Enable precise, user-friendly, and stable widget placement inside the Dashboard Builder by:
1. **Repositioning & Docking the "Add Widget" Button:** Placing the main "Add Widget" trigger directly beneath the canvas grid, sticky to the viewport bottom when scrolling through tall layouts.
2. **Native Drag-and-Drop from Sidebar (GridStack Native):** Allowing users to drag widget templates from the sidebar palette directly into specific grid cells with live shadow/ghost placement previews.
3. **Multi-Select Conflict Isolation:** Ensuring full independence between the multi-widget selection/drag features and the external drag-in workflow.

---

## 2. Key Components & Changes

### A. Sticky Canvas "Add Widget" Action Bar
- **File:** `resources/views/filament/app/pages/dashboard-builder.blade.php`
- **Location:** Directly below `#grid-container` in the grid canvas column.
- **Behavior:**
  - When the canvas is short or scrolled to the bottom, it rests cleanly right below the grid.
  - When scrolling up through tall dashboards, it docks to the bottom edge (`sticky bottom-4 z-20`) with a subtle backdrop blur and clean elevation so it is always accessible without obscuring widgets.
  - Clicking it triggers `openAddWidgetModal()` with standard bottom-append placement (`grid_x: null, grid_y: null`).

### B. GridStack Drag-from-Sidebar (Exact Slot Insertion)
- **File:** `resources/views/filament/app/pages/dashboard-builder.blade.php`
  - Update palette cards in the left sidebar with `.grid-stack-drag-in` classes and helper grid attributes (`gs-w="4" gs-h="3"`).
- **File:** `resources/js/dashboards/dashboard-builder.js`
  - **Setup:** Call `GridStack.setupDragIn('.grid-stack-drag-in', { helper: 'clone', appendTo: 'body' })` in `initGrid()`.
  - **Drop Listener:** Hook into `grid.on('dropped', (event, previousNode, newNode) => { ... })`:
    1. Capture drop coordinates: `this.targetGridX = newNode.x; this.targetGridY = newNode.y;`
    2. Open widget creation modal (`this.openAddWidgetModal()`).
    3. Clean up the temporary drop placeholder element so that only the final confirmed widget is added to `this.widgets`.
  - **Confirm Add:** In `confirmAddWidget()`, use `this.targetGridX` / `this.targetGridY` if set, then reset them to `null`.

### C. Multi-Widget Selection Isolation
- **Mechanism:**
  - Multi-drag uses the internal widget drag handle (`.widget-header, .widget-drag-handle, .widget-body-drag`) and filters against `this.selectedWidgetIds`.
  - External palette items (`.grid-stack-drag-in`) do not possess a widget ID, entirely bypassing multi-drag handlers without conflict.
  - The floating multi-select action bar remains at `right-6 top-1/2` with `z-[99999]`, keeping it visually and functionally isolated from the left palette and bottom add button.

### D. CSS Styling & Polish
- **File:** `public/css/dashboard-builder.css`
  - Styling for the sticky canvas add bar (light/dark mode compatibility, hover states, dashed border variant).
  - Styling for `.grid-stack-drag-in` clone/ghost helper while dragging over the canvas.

---

## 3. Verification & Testing Checklist
- [ ] **Sticky Button Position:** Verify "+ Add Widget" sits below `#grid-container` and docks nicely at the screen bottom when scrolling tall dashboards.
- [ ] **Sticky Button Click:** Verify clicking it opens the modal and places the new widget at the bottom.
- [ ] **Sidebar Drag-In:** Drag a widget from the palette into a specific empty slot or between widgets; confirm live ghost preview indicates the target slot.
- [ ] **Coordinate Precision:** Confirm the modal opens with coordinates captured and the resulting widget lands in the intended location.
- [ ] **Multi-Select Compatibility:** Select multiple widgets; ensure dragging a new widget from the sidebar does not disrupt selected items or trigger multi-drag.
