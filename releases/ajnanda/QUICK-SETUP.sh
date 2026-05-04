#!/bin/bash
# Quick Setup Script for NC LLC Agents Inc Website
# Run this from your WordPress root directory

echo "🚀 NC LLC Agents Inc - Quick Setup Script"
echo "=========================================="
echo ""

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo "❌ WP-CLI is not installed. Please install it first or create pages manually."
    echo "Visit: https://wp-cli.org/"
    exit 1
fi

echo "✅ WP-CLI detected"
echo ""

# Create Contact Page
echo "📄 Creating Contact page..."
CONTACT_ID=$(wp post create --post_type=page --post_title='Contact Us' --post_status=publish --post_name=contact --page_template=page-contact.php --porcelain)
if [ $? -eq 0 ]; then
    echo "✅ Contact page created (ID: $CONTACT_ID)"
else
    echo "⚠️  Contact page may already exist or there was an error"
fi

# Create Services Page
echo "📄 Creating Services page..."
SERVICES_ID=$(wp post create --post_type=page --post_title='Services' --post_status=publish --post_name=services --page_template=page-services.php --porcelain)
if [ $? -eq 0 ]; then
    echo "✅ Services page created (ID: $SERVICES_ID)"
else
    echo "⚠️  Services page may already exist or there was an error"
fi

# Create Blog/Knowledge Base Page
echo "📄 Creating Knowledge Base page..."
BLOG_ID=$(wp post create --post_type=page --post_title='Knowledge Base' --post_status=publish --post_name=blog --porcelain)
if [ $? -eq 0 ]; then
    echo "✅ Knowledge Base page created (ID: $BLOG_ID)"
else
    echo "⚠️  Knowledge Base page may already exist or there was an error"
fi

# Get homepage ID
HOMEPAGE_ID=$(wp post list --post_type=page --name=home --field=ID --format=csv)
if [ -z "$HOMEPAGE_ID" ]; then
    echo "⚠️  Homepage not found, using default"
else
    echo "✅ Homepage found (ID: $HOMEPAGE_ID)"
fi

# Configure Reading Settings
echo ""
echo "⚙️  Configuring reading settings..."
if [ ! -z "$HOMEPAGE_ID" ] && [ ! -z "$BLOG_ID" ]; then
    wp option update show_on_front 'page'
    wp option update page_on_front "$HOMEPAGE_ID"
    wp option update page_for_posts "$BLOG_ID"
    echo "✅ Reading settings configured"
else
    echo "⚠️  Could not configure reading settings automatically"
    echo "   Please configure manually: Settings → Reading"
fi

# Create sample blog posts
echo ""
echo "📝 Creating sample blog posts..."

wp post create --post_type=post --post_title='What is a Registered Agent?' --post_status=publish --post_content='<p>A registered agent is a person or business entity designated to receive legal documents, tax notices, and official correspondence on behalf of your North Carolina LLC or corporation.</p><p>Every business entity in North Carolina is required by law to maintain a registered agent with a physical address in the state. This ensures that important legal documents can always be delivered to your business.</p><h2>Key Responsibilities</h2><ul><li>Accept service of process</li><li>Receive official state correspondence</li><li>Forward documents to business owners</li><li>Maintain availability during business hours</li></ul>' --porcelain > /dev/null
echo "✅ Created: What is a Registered Agent?"

wp post create --post_type=post --post_title='Do I Need a Registered Agent in North Carolina?' --post_status=publish --post_content='<p>Yes! North Carolina law requires all LLCs and corporations to maintain a registered agent. This is not optional - it is a legal requirement for doing business in the state.</p><h2>Why It Matters</h2><p>Without a registered agent, your business cannot be properly formed or maintained in North Carolina. The NC Secretary of State requires this information as part of your business formation documents.</p><h2>Consequences of Not Having One</h2><ul><li>Cannot form your business</li><li>Risk of administrative dissolution</li><li>Potential fines and penalties</li><li>Loss of good standing status</li></ul>' --porcelain > /dev/null
echo "✅ Created: Do I Need a Registered Agent in North Carolina?"

wp post create --post_type=post --post_title='Benefits of Using a Professional Registered Agent' --post_status=publish --post_content='<p>While you can serve as your own registered agent, there are significant benefits to using a professional service like NC LLC Agents Inc.</p><h2>Privacy Protection</h2><p>Your registered agent address is public record. Using a professional service keeps your home address private and off public databases.</p><h2>Availability Guarantee</h2><p>Registered agents must be available during business hours. We ensure someone is always there to receive important documents.</p><h2>Professional Image</h2><p>A Charlotte business address looks more professional than a residential address, especially for client-facing businesses.</p><h2>Avoid Embarrassment</h2><p>Never risk being served legal papers at home or in front of clients. We handle all service of process discreetly.</p>' --porcelain > /dev/null
echo "✅ Created: Benefits of Using a Professional Registered Agent"

# Create navigation menu
echo ""
echo "🔗 Creating navigation menu..."
MENU_ID=$(wp menu create "Main Menu" --porcelain)
if [ $? -eq 0 ]; then
    echo "✅ Menu created (ID: $MENU_ID)"
    
    # Add pages to menu
    wp menu item add-post $MENU_ID $HOMEPAGE_ID --title="Home" 2>/dev/null
    wp menu item add-post $MENU_ID $SERVICES_ID --title="Services" 2>/dev/null
    wp menu item add-post $MENU_ID $BLOG_ID --title="Knowledge Base" 2>/dev/null
    wp menu item add-post $MENU_ID $CONTACT_ID --title="Contact" 2>/dev/null
    
    # Assign menu to primary location
    wp menu location assign $MENU_ID primary 2>/dev/null
    echo "✅ Menu items added and assigned to primary location"
else
    echo "⚠️  Menu may already exist or there was an error"
fi

echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Visit your website to see the changes"
echo "2. Install SureForms plugin for contact form"
echo "3. Update contact form ID in page-contact.php"
echo "4. Customize colors and branding in style.css"
echo "5. Add your actual business content"
echo ""
echo "📖 For detailed instructions, see:"
echo "   - SETUP-GUIDE.md"
echo "   - README.md"
echo "   - INSTALLATION.md"
echo ""
echo "🌐 Your site: http://ncllc.ddev.site/"
echo ""

# Made with Bob
