CREATE DATABASE IF NOT EXISTS job_aggregator;
USE job_aggregator;

-- =====================================================
-- 1. JOB CATEGORIES
-- =====================================================

CREATE TABLE job_categories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,

    keywords TEXT NULL,
    icon VARCHAR(100) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. JOB SOURCES
-- =====================================================

CREATE TABLE job_sources (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    base_url TEXT NOT NULL,

    scraper_file VARCHAR(255) NULL,

    is_active TINYINT(1) DEFAULT 1,

    last_scraped DATETIME NULL,

    scrape_frequency_minutes INT DEFAULT 60,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. JOBS
-- =====================================================

CREATE TABLE jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    source_id BIGINT NULL,
    category_id BIGINT NULL,

    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NULL,

    company_name VARCHAR(255) NULL,
    company_logo TEXT NULL,

    location VARCHAR(255) NULL,
    country VARCHAR(100) DEFAULT 'Uganda',

    job_type ENUM(
        'Full-Time',
        'Part-Time',
        'Contract',
        'Internship',
        'Temporary',
        'Remote',
        'Volunteer',
        'Hybrid'
    ) DEFAULT 'Full-Time',

    experience_level VARCHAR(100) NULL,

    salary_min DECIMAL(12,2) NULL,
    salary_max DECIMAL(12,2) NULL,
    salary_currency VARCHAR(10) DEFAULT 'UGX',

    description LONGTEXT NULL,
    requirements LONGTEXT NULL,
    responsibilities LONGTEXT NULL,

    apply_url TEXT NOT NULL,
    original_url TEXT NOT NULL,

    reference_code VARCHAR(100) NULL,

    posted_date DATETIME NULL,
    deadline_date DATETIME NULL,

    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,

    is_duplicate TINYINT(1) DEFAULT 0,

    hash_value VARCHAR(255) UNIQUE,

    views INT DEFAULT 0,
    clicks INT DEFAULT 0,

    raw_html LONGTEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    INDEX(title),
    INDEX(company_name),
    INDEX(location),
    INDEX(deadline_date),
    INDEX(category_id),
    INDEX(source_id),

    CONSTRAINT fk_jobs_category
        FOREIGN KEY (category_id)
        REFERENCES job_categories(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_jobs_source
        FOREIGN KEY (source_id)
        REFERENCES job_sources(id)
        ON DELETE SET NULL
);

-- =====================================================
-- 4. SUBSCRIBERS
-- =====================================================

CREATE TABLE subscribers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(255) UNIQUE NOT NULL,

    full_name VARCHAR(255) NULL,

    phone VARCHAR(50) NULL,

    country VARCHAR(100) DEFAULT 'Uganda',

    preferred_frequency ENUM(
        'Instant',
        'Daily',
        'Weekly'
    ) DEFAULT 'Daily',

    is_active TINYINT(1) DEFAULT 1,

    email_verified TINYINT(1) DEFAULT 0,

    verification_token VARCHAR(255) NULL,

    unsubscribe_token VARCHAR(255) NULL,

    last_email_sent DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 5. SUBSCRIBER CATEGORIES
-- =====================================================

CREATE TABLE subscriber_categories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    subscriber_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(subscriber_id, category_id),

    CONSTRAINT fk_subscriber_categories_subscriber
        FOREIGN KEY (subscriber_id)
        REFERENCES subscribers(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_subscriber_categories_category
        FOREIGN KEY (category_id)
        REFERENCES job_categories(id)
        ON DELETE CASCADE
);

-- =====================================================
-- 6. EMAIL LOGS
-- =====================================================

CREATE TABLE email_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    subscriber_id BIGINT NOT NULL,

    subject VARCHAR(255) NOT NULL,

    body LONGTEXT NULL,

    status ENUM(
        'Pending',
        'Sent',
        'Failed'
    ) DEFAULT 'Pending',

    error_message TEXT NULL,

    sent_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_email_logs_subscriber
        FOREIGN KEY (subscriber_id)
        REFERENCES subscribers(id)
        ON DELETE CASCADE
);

-- =====================================================
-- 7. SENT JOBS
-- =====================================================

CREATE TABLE sent_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    subscriber_id BIGINT NOT NULL,

    job_id BIGINT NOT NULL,

    emailed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(subscriber_id, job_id),

    CONSTRAINT fk_sent_jobs_subscriber
        FOREIGN KEY (subscriber_id)
        REFERENCES subscribers(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_sent_jobs_job
        FOREIGN KEY (job_id)
        REFERENCES jobs(id)
        ON DELETE CASCADE
);

-- =====================================================
-- 8. SCRAPER LOGS
-- =====================================================

CREATE TABLE scraper_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    source_id BIGINT NOT NULL,

    jobs_found INT DEFAULT 0,

    jobs_inserted INT DEFAULT 0,

    jobs_updated INT DEFAULT 0,

    errors_count INT DEFAULT 0,

    log_message LONGTEXT NULL,

    started_at DATETIME NULL,

    finished_at DATETIME NULL,

    status ENUM(
        'Running',
        'Success',
        'Failed'
    ) DEFAULT 'Running',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_scraper_logs_source
        FOREIGN KEY (source_id)
        REFERENCES job_sources(id)
        ON DELETE CASCADE
);

-- =====================================================
-- 9. USERS (ADMIN PANEL)
-- =====================================================

CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(255) NOT NULL,

    email VARCHAR(255) UNIQUE NOT NULL,

    password VARCHAR(255) NOT NULL,

    role ENUM(
        'Admin',
        'Editor',
        'Moderator'
    ) DEFAULT 'Editor',

    last_login DATETIME NULL,

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 10. SETTINGS
-- =====================================================

CREATE TABLE settings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(255) UNIQUE NOT NULL,

    setting_value LONGTEXT NULL,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 11. FAILED JOBS
-- =====================================================

CREATE TABLE failed_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    source_id BIGINT NULL,

    url TEXT NOT NULL,

    error_message TEXT NULL,

    html_snapshot LONGTEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_failed_jobs_source
        FOREIGN KEY (source_id)
        REFERENCES job_sources(id)
        ON DELETE SET NULL
);

-- =====================================================
-- INSERT DEFAULT CATEGORIES
-- =====================================================

INSERT INTO job_categories (name, slug, keywords) VALUES
('IT & Software', 'it-software', 'developer,software,network,it,programmer,engineer'),
('Healthcare', 'healthcare', 'doctor,nurse,hospital,medical,clinic'),
('Engineering', 'engineering', 'engineer,civil,electrical,mechanical'),
('Education', 'education', 'teacher,lecturer,tutor,education'),
('Agriculture', 'agriculture', 'farm,agriculture,livestock,crop'),
('Finance', 'finance', 'accountant,auditor,finance,banking'),
('NGO', 'ngo', 'ngo,unicef,unhcr,charity,development'),
('Government', 'government', 'government,ministry,district,authority'),
('Security', 'security', 'security,police,guard'),
('Hospitality', 'hospitality', 'hotel,restaurant,hospitality,chef');

-- =====================================================
-- INSERT DEFAULT SOURCE
-- =====================================================

INSERT INTO job_sources (
    name,
    base_url,
    scraper_file
) VALUES (
    'JobAdverts Uganda',
    'https://jobadverts.ug/jobs/',
    'jobadverts_scraper.php'
);

-- =====================================================
-- INSERT DEFAULT ADMIN USER
-- Password: admin123
-- =====================================================

INSERT INTO users (
    full_name,
    email,
    password,
    role
) VALUES (
    'System Administrator',
    'admin@jobaggregator.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin'
);

-- =====================================================
-- INSERT DEFAULT SETTINGS
-- =====================================================

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Uganda Job Aggregator'),
('default_country', 'Uganda'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('email_from', 'noreply@jobaggregator.com'),
('scrape_interval_minutes', '60');