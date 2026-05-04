# NC LLC Agents Inc - Website Setup Guide

## Recent Updates (April 2026)

### Homepage Improvements
The homepage has been streamlined to focus on core messaging:
- ✅ Removed pricing/plans section (pricing is private)
- ✅ Removed services details from homepage (moved to dedicated Services page)
- ✅ Removed contact form from homepage (moved to dedicated Contact page)
- ✅ Added Knowledge Base/Blog section showing recent posts
- ✅ Updated CTA buttons to link to proper pages

### New Page Templates Created

#### 1. Contact Page Template (`page-contact.php`)
**Features:**
- Professional contact page layout
- SureForms integration ready
- Fallback temporary form included
- Contact information cards
- "Why Choose Us" section

**Setup Instructions:**
1. Go to WordPress Admin → Pages → Add New
2. Title: "Contact Us" or "Contact"
3. In the right sidebar under "Page Attributes", select Template: **Contact Page**
4. Set the slug to `contact` (important for navigation links)
5. Publish the page

**SureForms Integration:**
1. Install and activate the SureForms plugin
2. Create your contact form in SureForms with these fields:
   - Name (required)
   - Email (required)
   - Phone Number (optional)
   - Business Name (optional)
   - Business Type (dropdown: LLC, Corporation, Nonprofit, Other)
   - Message (required)
3. Copy the SureForms shortcode (e.g., `[sureforms id="123"]`)
4. Edit `wp-content/themes/ncllc-pro/page-contact.php`
5. Find line with `REPLACE_WITH_YOUR_FORM_ID`
6. Replace it with your actual form ID number
7. Save the file

#### 2. Services Page Template (`page-services.php`)
**Features:**
- Comprehensive services overview
- "How It Works" process section
- Benefits of registered agent service
- "Who We Serve" section
- Service-specific FAQs
- CTA to contact page

**Setup Instructions:**
1. Go to WordPress Admin → Pages → Add New
2. Title: "Services" or "Our Services"
3. In the right sidebar under "Page Attributes", select Template: **Services Page**
4. Set the slug to `services` (important for navigation links)
5. Publish the page

### Navigation Menu Setup

Update your main navigation menu to include the new pages:

1. Go to WordPress Admin → Appearance → Menus
2. Add these pages to your menu:
   - Home (links to `/`)
   - Services (links to `/services`)
   - Knowledge Base or Blog (links to `/blog` or your posts page)
   - Contact (links to `/contact`)
3. Save the menu

### Blog/Knowledge Base Setup

The homepage now displays recent blog posts. To set this up:

1. **Create a Blog Page:**
   - Go to Pages → Add New
   - Title: "Knowledge Base" or "Blog" or "Insights"
   - Leave content empty (it will show posts automatically)
   - Publish

2. **Configure Reading Settings:**
   - Go to Settings → Reading
   - Set "Your homepage displays" to "A static page"
   - Homepage: Select your homepage
   - Posts page: Select your blog page
   - Save Changes

3. **Create Sample Blog Posts:**
   - Go to Posts → Add New
   - Create posts about:
     - "What is a Registered Agent?"
     - "Do I Need a Registered Agent in North Carolina?"
     - "Benefits of Using a Professional Registered Agent"
     - "How to Change Your Registered Agent in NC"
     - "LLC vs Corporation: Which is Right for Your NC Business?"

### Homepage Structure (Updated)

Current sections on the homepage:
1. **Hero Section** - Main headline with CTA buttons
2. **Features Section** - 6 key features of your service
3. **Stats Section** - Quick stats (Fast, 24/7, 100%, Secure)
4. **Testimonials Section** - Client testimonials
5. **FAQ Section** - Common questions about registered agent services
6. **Knowledge Base Section** - Recent blog posts (NEW)
7. **CTA Section** - Final call-to-action to contact page

### Button Links (Updated)

All buttons now link to proper pages:
- "Get Started Today" → `/contact`
- "Our Services" → `/services`
- "View All Articles" → Your blog page
- "Contact Us Today" → `/contact`

### Customization Options

#### Header Logo
1. Go to Appearance → Customize → Header Settings
2. Upload your logo
3. Adjust logo height (default: 50px)
4. Adjust header padding (default: 1rem)

#### Colors
Edit `wp-content/themes/ncllc-pro/style.css` to change colors:
```css
:root {
    --primary: #2563eb;    /* Main blue */
    --secondary: #7c3aed;  /* Purple accent */
    --accent: #f59e0b;     /* Orange accent */
}
```

### Floating Action Buttons

The theme includes two floating action buttons in the footer:
- **Text Us** - Opens SMS (update phone number in footer.php)
- **Live Chat** - Placeholder for chat integration

To update the phone number:
1. Edit `wp-content/themes/ncllc-pro/footer.php`
2. Find `sms:+1234567890`
3. Replace with your actual phone number

### Performance Features

The theme includes:
- ✅ Removed WordPress bloat (emoji scripts, etc.)
- ✅ Security headers
- ✅ Lazy loading for images
- ✅ Optimized CSS and JavaScript
- ✅ Mobile-first responsive design
- ✅ Fast page load times

### SEO Optimization

Recommended plugins:
- **Yoast SEO** or **Rank Math** - For SEO optimization
- **SureForms** - For contact forms
- **WP Rocket** - For caching (optional)

### Testing Checklist

After setup, test these items:
- [ ] Homepage loads correctly
- [ ] All navigation links work
- [ ] Contact page displays (with or without SureForms)
- [ ] Services page displays correctly
- [ ] Blog posts show on homepage
- [ ] Mobile menu works
- [ ] FAQ accordions expand/collapse
- [ ] Scroll animations trigger
- [ ] Forms submit correctly
- [ ] Floating action buttons work

### Troubleshooting

**Issue: Contact form not showing**
- Solution: Install SureForms plugin and update the form ID in page-contact.php

**Issue: Blog posts not showing on homepage**
- Solution: Create at least one published post and ensure Reading Settings are configured

**Issue: Navigation links don't work**
- Solution: Check that page slugs match the links (`/contact`, `/services`)

**Issue: Logo too large/small**
- Solution: Go to Appearance → Customize → Header Settings and adjust logo height

**Issue: Animations not working**
- Solution: Clear browser cache and ensure JavaScript is enabled

### Support

For theme customization help:
1. Check the README.md file
2. Review the INSTALLATION.md guide
3. Inspect the code comments in template files

### Next Steps

1. ✅ Create Contact page with Contact Page template
2. ✅ Create Services page with Services Page template
3. ✅ Install and configure SureForms
4. ✅ Update navigation menu
5. ✅ Set up blog/posts page
6. ✅ Create initial blog posts
7. ✅ Test all functionality
8. ✅ Customize colors and branding
9. ✅ Add your actual content
10. ✅ Launch!

---

**Last Updated:** April 23, 2026
**Theme Version:** 1.2.0
**WordPress Version:** 6.5+