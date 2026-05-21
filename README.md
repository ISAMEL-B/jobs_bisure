# Uganda Job Aggregator

A powerful PHP-based Job Aggregator & Email Notification System for scraping, managing, categorizing, and distributing job opportunities in Uganda.

This platform automatically scrapes jobs from multiple websites, stores them in a MySQL database, categorizes them intelligently, and sends personalized job alerts to subscribers based on their selected categories.

---

# Features

## Job Scraping System

* Multi-source job scraping
* Dynamic scraper management
* Duplicate job detection
* Automated cron execution
* Failed job logging
* Scraper activity tracking
* Scraper statistics dashboard
* Scraper enable/disable system

---

## Job Management

* Advanced jobs dashboard
* Search jobs
* Filter jobs by category
* Filter jobs by status
* Featured jobs system
* Activate/deactivate jobs
* Delete jobs
* Bulk job operations
* Pagination support
* Track job views
* Track job clicks
* Remote jobs support
* Company-based organization
* Job source tracking

---

## Email Notification System

* Personalized job alerts
* Subscriber category preferences
* Smart unsent job detection
* Daily/Weekly/Instant frequencies
* Email logs
* Email failure tracking
* Beautiful HTML email templates
* Subscriber email tracking
* Sent jobs tracking
* Email system enable/disable

---

## Subscriber Management

* Add subscribers
* Manage subscribers
* Category subscriptions
* Email verification support
* Unsubscribe token support
* Active/inactive subscribers
* Frequency management

---

## Categories Management

* Create categories
* Edit categories
* Delete categories
* Keywords system
* Icons support
* Category statistics
* Category subscriber counts

---

## Admin Dashboard

* Modern Bootstrap UI
* Sidebar navigation
* Real-time scraper console
* System activity logs
* Dashboard statistics
* Quick action buttons
* Responsive design
* Advanced tables
* Pagination
* Bulk actions

---

# Technologies Used

* PHP 8+
* MySQL
* PDO
* Bootstrap 5
* Bootstrap Icons
* JavaScript
* HTML5
* CSS3
* Cron Jobs
* PHPMailer

---

# Database Structure

Main database tables:

| Table                 | Purpose                  |
| --------------------- | ------------------------ |
| jobs                  | Stores scraped jobs      |
| job_categories        | Stores categories        |
| job_sources           | Stores scraper sources   |
| subscribers           | Stores subscribers       |
| subscriber_categories | Subscriber preferences   |
| sent_jobs             | Tracks emailed jobs      |
| email_logs            | Email activity           |
| scraper_logs          | Scraper logs             |
| failed_jobs           | Failed scraping attempts |
| users                 | Admin users              |
| settings              | System settings          |

---

# Installation

## 1. Clone Project

```bash
git clone https://github.com/yourusername/job-aggregator.git
```

---

## 2. Move Project

Move the project into your web server directory.

Example for XAMPP:

```text
htdocs/job-aggregator
```

---

## 3. Create Database

Create a database named:

```text
job_aggregator
```

---

## 4. Import SQL

Import the provided SQL schema into MySQL.

Example:

```bash
job_aggregator.sql
```

---

## 5. Configure Database

Edit:

```text
/core/database.php
```

Update:

```php
host
database
username
password
```

---

## 6. Configure Email

Edit:

```text
/core/mailer.php
```

Configure:

```php
SMTP Host
SMTP Username
SMTP Password
SMTP Port
```

---

# Main Files

| File                            | Purpose                     |
| ------------------------------- | --------------------------- |
| index.php                       | Main dashboard              |
| cron.php                        | Runs scraper + email system |
| run_scraper.php                 | Runs scrapers only          |
| send_emails.php                 | Sends job emails            |
| manage/manage_jobs.php          | Manage jobs                 |
| manage/manage_categories.php    | Manage categories           |
| manage/manage_subscribers.php   | Manage subscribers          |
| manage/settings.php             | System settings             |
| manage/subscribe.php            | Public subscription page    |
| scrapers/jobadverts_scraper.php | JobAdverts scraper          |
| sidebar/sidebar.php             | Shared sidebar              |

---

# Cron Job Setup

Example cron job:

```bash
*/30 * * * * php /path/to/project/cron.php
```

This will:

* Run all scrapers
* Store jobs
* Detect duplicates
* Send emails
* Save logs

---

# Default Admin Login

```text
Email: admin@jobaggregator.com
Password: admin123
```

Change immediately after installation.

---

# Job Scraping Flow

1. Scraper fetches jobs
2. Jobs parsed from HTML
3. Duplicate detection performed
4. Jobs inserted into database
5. Categories assigned
6. Subscribers matched
7. Emails generated
8. Sent jobs recorded
9. Logs stored

---

# Email Flow

1. Fetch active subscribers
2. Get subscriber categories
3. Fetch unsent jobs
4. Build HTML email
5. Send email
6. Save email logs
7. Mark jobs as sent

---

# Security Features

* PDO prepared statements
* XSS protection with `htmlspecialchars`
* Duplicate prevention
* Token-based unsubscribe system
* Admin roles support
* SQL injection protection

---

# Future Improvements

* AI job categorization
* Resume uploads
* Telegram notifications
* WhatsApp notifications
* API support
* Mobile app
* User accounts
* Saved jobs
* AI job recommendations
* Auto-expiring jobs
* Analytics dashboard

---

# Folder Structure

```text
project-root/
│
├── core/
├── manage/
├── scrapers/
├── sidebar/
├── assets/
├── cron.php
├── run_scraper.php
├── send_emails.php
├── index.php
└── README.md
```

---

# Screenshots Suggestions

You can later add screenshots for:

* Dashboard
* Manage Jobs
* Categories
* Subscribers
* Settings
* Email Templates
* Activity Console

---

# License

This project is open-source and free to modify.

---

# Author

Developed for advanced job aggregation, automation, and email distribution systems in Uganda.
