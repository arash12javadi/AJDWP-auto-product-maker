
# AJDWP Auto Product Maker

**Version:** 250702  
**Author:** Arash Javadi  
**Description:**  
The AJDWP Auto Product Maker is a WordPress plugin that automates the creation of WooCommerce product listings by scraping data from external websites and optimising it using AI.  
It supports multilingual and dynamic websites, works with static HTML, JavaScript-heavy frameworks (React, Angular), and server-rendered pages, and provides an AJAX-driven, user-friendly admin interface for managing and customising imported products.

---

## 📜 Table of Contents
- [Features](#features)
- [How It Works](#how-it-works)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Setup & Configuration](#setup--configuration)
- [Usage](#usage)
- [Scraping Modes](#scraping-modes)
- [AI Optimisation](#ai-optimisation)
- [Multilingual Support](#multilingual-support)
- [Security & Best Practices](#security--best-practices)
- [Plugin Structure](#plugin-structure)
- [Known Limitations](#known-limitations)
- [Roadmap & Future Improvements](#roadmap--future-improvements)
- [License](#license)
- [Credits](#credits)

---

## ✨ Features
✅ Automatically scrape product data (title, description, price, images) from any external website.  
✅ Works with:
  - Static HTML websites
  - Dynamic JavaScript-heavy websites (React, Angular, Vue)
  - Server-rendered websites (PHP, Python, etc.)  
✅ Scraping powered by:
  - PHP DOM parser (`simple_html_dom.php`)
  - Headless browser automation (`Playwright` via Node.js)  
✅ AJAX-driven admin interface — no page reloads required.  
✅ Bulk import and bulk update actions for multiple products.  
✅ AI-powered optimisation of titles & descriptions for SEO using OpenAI.  
✅ Multilingual scraping & translation.  
✅ Template system for saving and reusing CSS selectors.  
✅ User-friendly feedback & progress indicators.  
✅ Secure & extensible WordPress standards-based architecture.  

---

## ⚙️ How It Works
1️⃣ User enters product URLs in the admin panel.  
2️⃣ Plugin scrapes each URL and extracts product data.  
3️⃣ Data is refined & optimised by AI (optional).  
4️⃣ User reviews & edits products in a dynamic table.  
5️⃣ Products are published to WooCommerce.  
6️⃣ Data is stored in custom database tables for management & re-use.

---

## 🖥️ System Requirements
- WordPress 6.0+
- WooCommerce 6.0+
- PHP 7.4+
- Node.js 16+ (for Playwright)
- Composer (for PHP dependencies)

---

## 📥 Installation
1. Upload the plugin folder (`AJDWP-auto-product-maker-Plugin/`) to your WordPress `/wp-content/plugins/` directory.  
2. Or install via the WordPress Admin → Plugins → Add New → Upload Plugin.  
3. Activate the plugin from the Plugins menu.  
4. Install Node.js & run:
   ```bash
   npm install
   ```
5. Install PHP dependencies:
   ```bash
   composer install
   ```
6. Make sure your server can run `shell_exec` or equivalent (to execute Playwright).

---

## 🔧 Setup & Configuration
- Go to **WooCommerce → Auto Product Maker** in WordPress Admin.
- Configure AI settings by providing your OpenAI API key & optional prompt templates.
- Create or edit scraping templates with CSS selectors for specific sites.
- Set default scraping mode (auto, static, dynamic) if desired.

---

## 🪄 Usage
### Add Products
- Navigate to Tab 1: *Product Scrape Form*.
- Enter one or more product URLs (separated by newlines).
- Choose scraping mode: Auto / Static / Dynamic.
- Start scraping & review results in the table.
- Edit fields as needed.
- Click *Publish to WooCommerce* to create products.

### Manage Products
- Navigate to Tab 2: *Product Manager*.
- View already scraped or imported products.
- Update, delete, or rescrape price.

### Templates
- Navigate to Tab 3: *Templates*.
- Add/edit templates with CSS selectors for recurring sites.
- Templates save time by automatically mapping fields.

### AI Settings
- Navigate to Tab 4: *AI Settings*.
- Enter your OpenAI key & define prompts for:
  - Title
  - Short description
  - Long description
- Adjust creativity & length (temperature & tokens).

---

## 🔀 Scraping Modes
- **Auto**: Chooses best method based on site analysis.
- **Static**: Fastest; for simple HTML pages.
- **Dynamic**: Uses Playwright to render JavaScript-heavy pages.

---

## 🤖 AI Optimisation
- Title & descriptions are passed to OpenAI for SEO-friendly rewriting.
- Users can define prompt templates for more control.
- AI ensures keyword-rich, readable, and unique content.

---

## 🌏 Multilingual Support
- Detects and translates multilingual content into English.
- Uses AI/NLP for translation and localisation.
- Ensures output is culturally relevant and SEO-optimised.

---

## 🔐 Security & Best Practices
- All admin actions require `manage_woocommerce` capability.
- AJAX requests should include nonces (confirm in code & test).
- Input from `$_POST`/`$_GET` is sanitised & validated.
- Adheres to WordPress coding standards & plugin guidelines.
- Disclaimer reminds users to respect target site Terms of Service & GDPR.

---

## 🗂️ Plugin Structure
```
ajdwp-auto-product-maker.php      → Main plugin file
/includes/
  helpers.php                     → Utility functions
  product-creator.php            → Creates WooCommerce products
  scraper.php                     → Scraping logic
  simple_html_dom.php             → HTML parser library
/admin/
  settings-page.php               → Admin panel
  tab1-product-scrape-form-ajax.php
  tab2-product-manager-ajax.php
  tab3-add-edit-template-ajax.php
  tab4-ai-settings-init.php
/assets/
  js/ css/                        → Admin JS & CSS
/templates/
  db-init.sql, admin views       → Templates
/uninstall.php                   → Clean uninstall logic
```

---

## ⚠️ Known Limitations
- Requires server access to run Node.js & Playwright.
- Cannot bypass strict anti-bot or CAPTCHA-protected websites.
- Multilingual translations depend on AI; some nuances may be lost.
- Overuse may hit OpenAI or target site rate limits.

---

## 🚀 Roadmap & Future Improvements
- Add fallback scraping mechanisms (proxy rotation, retries).
- Integrate more NLP providers beyond OpenAI.
- Add scheduler for periodic scraping.
- Improve accessibility & localisation of admin UI.
- Add unit tests & QA suite.

---

## 📄 License
This plugin is distributed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 🙌 Credits
- [WordPress](https://wordpress.org/)
- [WooCommerce](https://woocommerce.com/)
- [Playwright](https://playwright.dev/) (Microsoft)
- [simple_html_dom.php](http://simplehtmldom.sourceforge.net/)
- [OpenAI](https://openai.com/)

Plugin developed by **Arash Javadi** as part of MSc Computing Project at Northumbria University.
