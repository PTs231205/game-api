# InfinityAPI Client Panel

A lightweight, high-performance Client Panel built with Core PHP and Tailwind CSS (CDN).

## Features
- **Dashboard**: Real-time GGR, Wallet Balance, Active Sessions.
- **Multi-language**: English, Hindi, Arabic (RTL Support).
- **Security**: IP Whitelisting, API Token Management.
- **Performance**: Redis Caching for fast data retrieval.
- **Tools**: Encrypted Request Tester, Integration Guide.

## Setup

1.  **Configuration**:
    -   Edit `config/config.php` with your Database and Redis credentials.
    -   Ensure your web server (Nginx/Apache) points to the root directory `visionmall.fun`.

2.  **Directory Structure**:
    -   `index.php`: Main Entry Point.
    -   `config/`: Configuration files.
    -   `includes/`: Core Logic.
    -   `views/`: UI Templates.

## Requirements
-   PHP 8.0+
-   Redis
-   MySQL/MariaDB
-   No Node.js required (Tailwind is loaded via CDN).
