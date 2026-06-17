AJNanda button color schemes fix

Replace this file in your project:
  /Users/sandip/Projects/ajnanda/js/editor-controls.js

With:
  editor-controls.js

What changed:
- Shared color scheme now fills Button 1, Button 2, Button 3, etc. with the same selected color.
- Per-button preset still clears the shared scheme and applies different colors per button.
- Added these per-button presets:
  - CTA Mix
  - Traffic Lights
  - Modern SaaS

Validation:
  node --check /Users/sandip/Projects/ajnanda/js/editor-controls.js
