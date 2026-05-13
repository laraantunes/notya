# Notya 🌌

Notya is a personal, secure, and highly organized note-taking application built with **PHP** and **HTMX**. It features a stunning **neon purple/pink aesthetic** and organizes notes in a hierarchical structure mirrored directly in your filesystem.

![Notya Preview](https://via.placeholder.com/1200x600?text=Notya+Neon+Editor+Preview) <!-- Replace with real image after deployment -->

## ✨ Features

- **📂 Deep Hierarchy**: Organize notes into Notebooks and unlimited levels of child notes.
- **🖱️ Drag & Drop**: Effortlessly move notes between notebooks or other notes to reorganize your thoughts.
- **📝 Markdown Editor**: Powered by **EasyMDE** for a seamless WYSIWYG markdown experience with auto-save.
- **🔍 Instant Search**: Accessible via `Ctrl+K` / `Cmd+K`, searching through titles and content.
- **📎 Media Handling**: Attach images and files directly to notes, stored alongside your markdown files.
- **🔒 Secure by Design**:
  - Encrypted password protection.
  - `.htaccess` rules to prevent direct access to sensitive data.
  - Local-first storage in your filesystem.
- **⚡ HTMX Powered**: Fast, dynamic interactions without full page reloads.

## 🚀 Getting Started

### Prerequisites

- PHP 7.4+
- Apache (with `mod_rewrite` enabled)
- XAMPP / WAMP / LAMP environment

### Installation

1. Clone the repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/notya.git
   ```
2. Ensure the `data/` directory is writable by the web server:
   ```bash
   chmod -R 755 data/
   ```
3. Open the application in your browser:
   ```
   http://localhost/notya
   ```
4. Follow the **First-Time Setup** to create your administrator account.

## 🛠️ Tech Stack

- **Backend**: PHP
- **Frontend**: HTMX, Vanilla CSS (Neon Design System)
- **Editor**: EasyMDE
- **Icons**: FontAwesome 6

## 📁 Project Structure

- `data/`: Stores all user information, notebooks, and notes (Ignored by Git).
- `templates/`: HTML fragments for HTMX-powered views.
- `js/`: Application logic and keyboard shortcut handlers.
- `css/`: The Notya Neon design system.
- `api.php`: Central HTMX endpoint for CRUD operations.

## 📜 License

Distributed under the MIT License. See `LICENSE` for more information.

---
*Built with love for organized minds.*
