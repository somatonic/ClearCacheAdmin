# Clear Cache Admin

Simple ProcessWire helper module to clear various caches in ProcessWire directly from the menu. Since there's 3 different caches in ProcessWire core we need a convenient way to clear them, without going to the various places to clear them. Some, like the WireCache ($cache) can't even be deleted from the admin directly.

This module adds a new menu item under "Setup" with a submenu that has different infos and option to clear the individual caches.

Additionally you can also use the modules screen to individually clean the caches with further options for the WireCaches.

This way it can also be used by non superusers, just use the "clear-cache-admin" permission that will be created automaticly by this module when installed.

## Requires

ProcessWire 2.7+