# NCLLC Professional Theme

A stunning, modern WordPress theme with cutting-edge design, smooth animations, and optimized performance.

## Features

✨ **Modern Design**
- Beautiful gradient hero section
- Smooth scroll animations
- Interactive hover effects
- Responsive design for all devices

🚀 **Performance Optimized**
- Minified assets
- Lazy loading images
- Debounced scroll events
- Optimized database queries
- Removed unnecessary WordPress bloat

🔒 **Security Enhanced**
- Security headers
- XML-RPC disabled
- Sanitized inputs
- XSS protection

💡 **Interactive Features**
- Smooth scrolling navigation
- Animated statistics counter
- Scroll-to-top button
- Mobile-friendly menu
- Parallax effects

## Installation

1. Upload the `ncllc-pro` folder to `/wp-content/themes/`
2. Activate the theme through WordPress admin panel (Appearance > Themes)
3. Visit your site at http://ncllc.ddev.site/

## Theme Structure

```
ncllc-pro/
├── style.css          # Main stylesheet with modern design system
├── functions.php      # Theme functions and optimizations
├── index.php          # Homepage template with hero, features, stats, CTA
├── header.php         # Header with fixed navigation
├── footer.php         # Footer with multiple sections
├── page.php           # Page template
├── single.php         # Single post template
├── 404.php            # Custom 404 error page
├── searchform.php     # Search form template
├── screenshot.png     # Theme screenshot
└── js/
    └── main.js        # Interactive JavaScript features
```

## Customization

### Colors
Edit CSS variables in `style.css`:
```css
:root {
    --primary: #2563eb;
    --secondary: #7c3aed;
    --accent: #f59e0b;
}
```

### Hero Section
Edit content in `index.php` or use WordPress Customizer (Appearance > Customize > Hero Section)

### Navigation Menu
Go to Appearance > Menus and assign menu to "Primary Menu" location

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Credits

- Fonts: Google Fonts (Inter, Poppins)
- Icons: Emoji (native)
- Built with: WordPress, PHP, JavaScript, CSS3

## License

GPL v2 or later

## Support

For support, visit http://ncllc.ddev.site/

---

Built with ❤️ for NCLLC