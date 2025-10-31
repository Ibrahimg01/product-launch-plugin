# 🚀 Product Launch Coach — v1.3.0
**by [@Ibrahimg01](https://github.com/Ibrahimg01)**  

A powerful AI-assisted WordPress plugin that guides creators, coaches, and entrepreneurs through every stage of a digital product launch — from idea clarity to post-launch growth.  
Built with modular architecture, AJAX-secured operations, and dynamic UI powered by JavaScript and CSS components.

---

## ✨ Features

- 🧩 **AI-Powered Guidance:** Integrates OpenAI-based assistance to improve product copy, messaging, and content.
- 🧠 **Multi-Phase Launch System:** Includes dedicated templates for each phase — Market Clarity, Offer Creation, Service Setup, Funnel Building, Email Sequences, Organic Posts, Facebook Ads, and Launch.
- ⚙️ **Modular Architecture:** Cleanly separated folders for assets, templates, and includes.
- 🔒 **Security Layer:** Built-in AJAX nonce and rate-limiting protection through `PL_Ajax_Guard`.
- 🧮 **Progress Tracking:** Stores user phase progress using WordPress user meta.
- 🧱 **Custom UI Components:** Uses modern JavaScript modules for selective overrides, modal previews, and dynamic content generation.

---

## 📂 Folder Structure

product-launch-plugin/
├── assets/
│   ├── css/
│   │   ├── product-launch.css
│   │   ├── pl-selective-modal.css
│   │   └── pl-indicators.css
│   └── js/
│       ├── product-launch.js
│       ├── pl-selective-override.js
│       ├── pl-indicators.js
│       └── plc-memory-bridge.js
├── includes/
│   ├── admin/
│   │   └── openai-getter.php
│   ├── database-migration.php
│   ├── enhanced-ai-memory.php
│   └── security/
│       └── ajax-guard.php
├── templates/
│   ├── build-funnel.php
│   ├── create-offer.php
│   ├── create-service.php
│   ├── dashboard.php
│   ├── email-sequences.php
│   ├── facebook-ads.php
│   ├── launch.php
│   ├── market-clarity.php
│   └── organic-posts.php
├── product_launch_main.php
├── README-INSTALL-v2.3.51.md
├── CHANGELOG_APPLIED.md
└── BUILD_INFO.txt


---

## 🧩 Core Files Overview

| File | Description |
|------|--------------|
| `product_launch_main.php` | Main plugin bootstrap — initializes constants, hooks, admin pages, and assets. |
| `includes/enhanced-ai-memory.php` | Handles persistent AI memory and context management. |
| `includes/database-migration.php` | Ensures schema updates and database integrity across plugin versions. |
| `includes/security/ajax-guard.php` | Nonce validation and rate-limit system for all AJAX calls. |
| `includes/admin/openai-getter.php` | Retrieves OpenAI credentials for AJAX handlers. |
| `assets/js/product-launch.js` | Main JS controller; manages modals, content injection, and event bindings. |
| `templates/*.php` | Each phase template file defines UI for one step of the launch process. |

---

## ⚙️ Installation

1. Download or clone the repository:
   ```bash
   git clone https://github.com/Ibrahimg01/product-launch-plugin.git


Zip the folder as product-launch-coach.zip.

Upload and install through WordPress → Plugins → Add New → Upload Plugin.

Activate Product Launch Coach.

Access the dashboard under your WordPress Admin sidebar.

🧠 Developer Notes

Built for PHP 7.4+ / WordPress 6.0+

Uses admin_enqueue_scripts for script loading.

All AJAX endpoints use nonce protection via PL_Ajax_Guard::guard().

Includes modular JS classes for selective content overrides and modal previews.

Follows WP coding standards and supports multisite environments.

🧰 Future Enhancements

✅ Deep integration with Tutor LMS for course launches.

✅ AI analytics dashboard with GPT-powered recommendations.

🕓 SaaS integration via WP Ultimo and FluentCRM automation.

🪪 License

This project is proprietary and currently under private development by Ibrahim G.
For partnership or licensing inquiries, please contact directly.
