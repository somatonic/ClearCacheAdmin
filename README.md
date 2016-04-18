# Clear Cache Admin

Simple ProcessWire helper module to clear various caches in ProcessWire directly from the menu. Since there's 3 different caches in ProcessWire core we need a convenient way to clear them, without going to the various places to clear them. Some, like the WireCache ($cache) can't even be deleted from the admin directly.

This module adds a new menu item under "Setup" with a submenu that has different infos and option to clear the individual caches.

Additionally it has its own admin page that's a collection of the various caches available in ProcessWire in one place. See some infos and clear them. WireCache (using $cache) caches in the DB and there no way to clear it from the admin. Now you can delete the entries individually or all in one go.

Supports following cache types:

- Template "Page" Cache (disk file cache)
- MarkupCache module (file cache)
- WireCache (DB)
- Other files and directories found in assets/cache/ path

With this module, it's also possible for non superusers to clear caches. Just use the "clear-cache-admin" permission that will be created automaticly by this module when installed.

## Requires

ProcessWire 2.6+
