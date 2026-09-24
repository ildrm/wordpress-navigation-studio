# Navigation Studio user guide

## Start

Open **Navigation Studio** in WordPress administration. The dashboard lists both classic menus and block navigations without changing them. Choose **Edit**, or create a new source. Classic sources remain compatible with theme menu locations; block sources remain available to the Site Editor and Navigation block.

## Add and organize items

Search the Content panel by title, select one or more results, and choose **Add selected**. Custom links can be added without leaving the editor. Select a tree row to edit it. Double-click a label, or press Enter on the row, for inline rename.

Drag a row onto the visible before, after, or inside targets. If dragging is inconvenient, choose **Move** and select both a destination and an explicit position. The following shortcuts work while a tree row has focus:

| Shortcut | Result |
| --- | --- |
| Up / Down | Move focus through visible rows |
| Left / Right | Collapse or expand a branch |
| Ctrl/Cmd + Up / Down | Reorder |
| Ctrl/Cmd + Left / Right | Decrease or increase depth |
| Enter | Edit label |
| Delete | Remove selected items |
| Ctrl/Cmd + D | Duplicate branch |
| Ctrl/Cmd + Z | Undo |
| Ctrl/Cmd + Shift + Z | Redo |
| Ctrl/Cmd + K | Open command palette |

Shift-click selects a range; Ctrl/Cmd-click toggles an item. Bulk actions appear at the bottom of the tree. Focus mode temporarily isolates a branch while retaining its hierarchy.

## Properties

The inspector groups common fields first. General and Link control the label and destination. Appearance contains classes and badges. Responsive controls per-viewport visibility and mobile submenu behavior. Visibility can show an item to everyone, logged-in visitors, or logged-out visitors. Mega-menu controls appear only after the feature is enabled for the selected branch. Developer exposes stable and native identifiers.

Custom navigation labels are independent from source titles. The plugin never silently replaces a custom label.

## Save and publish

Edits happen locally and are undoable. **Save draft** stores a private per-user draft. Autosave runs after a short idle period. **Publish changes** first checks whether the native navigation changed, creates a recovery snapshot, then updates the native source. A conflict keeps local work safe and asks the editor to reconcile rather than overwriting another user.

If the network is unavailable, the current draft is retained in the browser session and is reconciled on the next successful save. Closing a dirty editor triggers a browser warning.

## Import, export, revisions, and templates

Export produces versioned JSON with portable node identities rather than depending on database IDs. Import validates size, encoding, schema, URLs, node count, hierarchy, and source type before creation. Existing navigation is not overwritten by the import workflow.

Templates are copies, not hidden linked references. Revisions are immutable recovery points created around publishing and restoration.

## Accessibility and RTL

The editor exposes tree roles, selection and expansion state, visible focus, live status messages, logical focus movement, and non-drag controls. Layout uses logical CSS properties and follows the WordPress locale direction. Reduced-motion preferences disable nonessential transitions.
