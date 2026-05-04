# NCLLC Professional Theme - Installation Guide

## Quick Start (For DDEV Local Development)

Since you're using DDEV, the theme is already in place! Just follow these steps:

### Step 1: Access WordPress Admin
1. Visit: http://ncllc.ddev.site/wp-admin
2. Log in with your WordPress credentials

### Step 2: Activate the Theme
1. Go to **Appearance** → **Themes**
2. Find "NCLLC Professional" theme
3. Click **Activate**

### Step 3: View Your New Site
1. Visit: http://ncllc.ddev.site/
2. Enjoy your stunning new website! 🎉

## What You Get

### ✨ Modern Homepage
- **Hero Section**: Eye-catching gradient background with call-to-action buttons
- **Features Grid**: 6 feature cards with hover animations
- **Stats Counter**: Animated statistics that count up on scroll
- **Services Section**: Showcase your offerings
- **Testimonials**: Client reviews with styled cards
- **CTA Section**: Final call-to-action with contact options

### 🎨 Design Features
- Modern gradient color scheme (blue/purple)
- Smooth scroll animations
- Hover effects on cards and buttons
- Responsive design (mobile, tablet, desktop)
- Custom scrollbar
- Scroll-to-top button
- Fixed header with blur effect

### 🚀 Performance
- Optimized CSS and JavaScript
- Lazy loading ready
- Debounced scroll events
- Removed WordPress bloat
- Security headers enabled
- Fast page load times

### 📱 Responsive
- Mobile-first design
- Breakpoints for all devices
- Touch-friendly navigation
- Optimized images

## Customization Options

### Change Colors
Edit `wp-content/themes/ncllc-pro/style.css` lines 11-20:
```css
:root {
    --primary: #2563eb;      /* Main blue color */
    --primary-dark: #1e40af; /* Darker blue */
    --secondary: #7c3aed;    /* Purple accent */
    --accent: #f59e0b;       /* Orange/yellow */
}
```

### Edit Homepage Content
Edit `wp-content/themes/ncllc-pro/front-page.php` or `index.php`

### Customize Navigation
1. Go to **Appearance** → **Menus**
2. Create a new menu
3. Assign it to "Primary Menu" location

### Add Logo
1. Go to **Appearance** → **Customize**
2. Click **Site Identity**
3. Upload your logo

### Change Hero Text
Edit lines in `front-page.php`:
- Line 14: Hero title
- Line 15: Hero subtitle

## File Structure

```
ncllc-pro/
├── style.css           # Main stylesheet (300 lines of modern CSS)
├── functions.php       # Theme functions (256 lines)
├── index.php          # Default homepage template
├── front-page.php     # Enhanced homepage (155 lines)
├── header.php         # Site header with navigation
├── footer.php         # Site footer
├── page.php           # Page template
├── single.php         # Blog post template
├── 404.php            # Custom 404 error page
├── searchform.php     # Search form
├── screenshot.png     # Theme preview image
├── README.md          # Documentation
├── INSTALLATION.md    # This file
└── js/
    └── main.js        # Interactive features (310 lines)
```

## Features Included

### JavaScript Interactions
- ✅ Smooth scrolling to anchors
- ✅ Header scroll effects
- ✅ Scroll animations for elements
- ✅ Mobile menu toggle
- ✅ Counter animations for stats
- ✅ Parallax hero effect
- ✅ Button ripple effects
- ✅ Scroll-to-top button
- ✅ Form validation
- ✅ Lazy loading support

### CSS Features
- ✅ Modern design system with CSS variables
- ✅ Flexbox and Grid layouts
- ✅ Smooth transitions and animations
- ✅ Custom scrollbar styling
- ✅ Responsive breakpoints
- ✅ Print styles
- ✅ Accessibility focus states

### WordPress Features
- ✅ Custom logo support
- ✅ Post thumbnails
- ✅ Navigation menus
- ✅ Widget areas
- ✅ Custom image sizes
- ✅ Gutenberg support
- ✅ SEO optimized
- ✅ Security hardened

## Troubleshooting

### Theme Not Showing?
1. Make sure you're in the correct directory
2. Check file permissions
3. Clear WordPress cache
4. Refresh the themes page

### Database Connection Error?
This is normal if DDEV isn't running. Start it with:
```bash
ddev start
```

### Styles Not Loading?
1. Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
2. Clear browser cache
3. Check if theme is activated

### JavaScript Not Working?
1. Check browser console for errors (F12)
2. Make sure jQuery is loaded
3. Clear cache and reload

## Next Steps

1. **Add Content**: Create pages and posts
2. **Customize**: Adjust colors, fonts, and content
3. **Add Plugins**: Install contact forms, SEO plugins, etc.
4. **Test**: Check on different devices and browsers
5. **Launch**: When ready, deploy to production

## Support

For questions or issues:
- Check README.md for detailed documentation
- Review the code comments in each file
- Test on http://ncllc.ddev.site/

## Credits

Built with modern web technologies:
- WordPress 6.x
- PHP 8.x
- CSS3 with custom properties
- Vanilla JavaScript (ES6+)
- Google Fonts (Inter, Poppins)

---

**Enjoy your new website!** 🚀

Built with ❤️ for NCLLC