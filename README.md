# CLINICSYNC — Dental Appointment Scheduling & SMS Notification System

[![CI Pipeline](https://github.com/benra/ClinicSync/actions/workflows/ci.yml/badge.svg)](https://github.com/benra/ClinicSync/actions/workflows/ci.yml)
[![Tests Passing](https://img.shields.io/badge/PHPUnit-33%20passed-brightgreen.svg)](https://github.com/benra/ClinicSync)
[![Laravel](https://img.shields.io/badge/Laravel-9.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.0%20%7C%208.1%20%7C%208.2-777BB4?logo=php&logoColor=white)](https://php.net)
[![GitHub Pages Ready](https://img.shields.io/badge/GitHub%20Pages-Ready%20(%2Fdocs)-blue?logo=github)](https://pages.github.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**CLINICSYNC** is an end-to-end, multi-role dental clinic scheduling web platform featuring **automated SMS confirmation and 24-hour reminder broadcasts**, live slot availability calculations, database concurrency locks, and specialized portals for **Patients**, **Receptionist Staff**, and **Dental Practitioners**.

---

## 🌐 Live GitHub Pages Showcase

This repository includes a static preview site located in the `/docs` directory ready for **GitHub Pages**.

- **Live URL format**: `https://<your-github-username>.github.io/<your-repository-name>/`
- **Pages included**:
  - `index.html`: Public Landing Page with SMS overview.
  - `about.html`: System Architecture & research problem statement.
  - `features.html`: Deep dive into Patient Booking, Staff Triage, and SMS Gateway.
  - `contact.html`: Clinic operating hours, emergency line, and inquiry form.
  - `login.html` & `register.html`: Public authentication portals.

### Enabling GitHub Pages in 30 Seconds:
1. Push this repository to GitHub.
2. In your GitHub repository, navigate to **Settings** → **Pages** (under Code and automation).
3. Under **Build and deployment** > **Source**, choose **Deploy from a branch**.
4. Set **Branch** to `main` (or `master`), select folder `/docs`, and click **Save**.
5. Your live showcase website will be published in ~1 minute!

---

## 🚀 Full Dynamic Cloud Deployment (From GitHub)

Because Laravel requires PHP and a database (MySQL/SQLite) for dynamic features like live user authentication, patient dashboards, and scheduled SMS cron jobs, deploy the dynamic backend using any of the included deployment configurations:

### Option 1: 1-Click Deploy on Render (Free Tier Blueprint)
1. Fork or push this repository to GitHub.
2. Go to [Render.com](https://render.com) and create a **New Blueprint Instance**.
3. Connect this GitHub repository. Render reads the included `render.yaml` automatically and configures:
   - Web service running PHP 8.1 with Nginx
   - Zero-config SQLite persistent database
   - Automatic migrations & demo seeders
   - Instant live public HTTPS URL

### Option 2: Docker Compose (Local or VPS)
Run the entire production stack (App + MySQL 8.0) locally or on a cloud VPS with one command:
```bash
docker compose up -d
```
Access the application at `http://localhost:8000`.

### Option 3: Railway / Fly.io / Heroku
The included `Dockerfile`, `Procfile`, and `docker/nginx.conf` are preconfigured for instant container PaaS deployment:
```bash
# Fly.io
fly launch
fly deploy

# Railway
railway up
```

---

## 🔑 Pre-Seeded Demonstration Accounts

All demo accounts use the standard password: **`password123`**

| Role | Name | Email Address | Access & Capabilities |
| :--- | :--- | :--- | :--- |
| **Admin** | Dr. Arthur Bennett | `admin@clinicsync.com` | Full clinic configuration, user management, audit logs |
| **Receptionist / Staff** | Emily Watson | `staff@clinicsync.com` | Appointment triage, confirmation, calendar, patient list |
| **Dentist** | Dr. Aris Thorne, DMD | `dr.thorne@clinicsync.com` | Daily clinic schedule, patient records, completion logs |
| **Dentist** | Dr. Maya Lin, DDS | `dr.lin@clinicsync.com` | Pediatric & cosmetic dental agenda |
| **Patient** | John Doe | `patient@clinicsync.com` | Online slot booking, status tracking, SMS notifications |

---

## 💻 Local Development Setup

### Prerequisites
- PHP 8.0+ with extensions (`pdo`, `pdo_sqlite`, `pdo_mysql`, `mbstring`, `curl`, `bcmath`)
- Composer 2.x
- Git

### Quickstart Commands
```bash
# 1. Clone repository
git clone https://github.com/<your-username>/ClinicSync.git
cd ClinicSync

# 2. Install PHP dependencies
composer install

# 3. Environment configuration
copy .env.example .env
php artisan key:generate

# 4. Run database migrations & seed demo data (using SQLite)
# To use SQLite zero-config:
# Set DB_CONNECTION=sqlite in .env, then:
touch database/database.sqlite
php artisan migrate --seed

# 5. Start development server
php artisan serve
```
Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your web browser.

---

## 🧪 Automated Testing

ClinicSync includes 33 comprehensive unit and feature tests covering authentication, appointment validation, concurrency slot locking, cyber defense middleware, Shelex OTP API integration, SMS gateway dispatching, and role authorization.

To run the test suite:
```bash
php artisan test
```
All 33 tests run in-memory with SQLite and execute in under 5 seconds.

---

## 📱 SMS Gateway & Free Inbound OTP Verification API

ClinicSync provides a modular, pluggable SMS driver configurable in `.env`:

```env
# Simulated mode (Default - logs SMS events to database without external costs)
SMS_GATEWAY_PROVIDER=simulated
SMS_SIMULATION_ENABLED=true

# Twilio SMS API
# SMS_GATEWAY_PROVIDER=twilio
# SMS_TWILIO_SID=your_account_sid
# SMS_TWILIO_AUTH_TOKEN=your_auth_token
# SMS_TWILIO_FROM=+15551234567

# Semaphore SMS API (Philippines)
# SMS_GATEWAY_PROVIDER=semaphore
# SMS_SEMAPHORE_API_KEY=your_api_key
# SMS_SEMAPHORE_SENDER_NAME=CLINICSYNC

# Shelex Free OTP & Inbound Test SMS Verification Service
# https://otp-api.shelex.dev/api/
SMS_FREE_OTP_API_URL=https://otp-api.shelex.dev/api
```

### Shelex Free OTP Testing API:
Staff can test inbound OTP/SMS verification without buying real virtual phone numbers:
1. **Available Countries:** `GET /staff/otp-service/countries` (`https://otp-api.shelex.dev/api/countries`)
2. **Numbers per Country:** `GET /staff/otp-service/list/{country}` (`https://otp-api.shelex.dev/api/list/{{country}}`)
3. **Verify Received Code:** `GET /staff/otp-service/verify/{country}/{phone}?match=CLINICSYNC&ago=300` (`https://otp-api.shelex.dev/api/{{country}}/{{phone}}?match={{substring}}&ago={{ago}}`)

Automated reminders scheduled 24 hours prior to appointments can be dispatched using:
```bash
php artisan appointments:send-reminders
```

---

## 📦 How to Push to GitHub

To push your project to a new GitHub repository:

```bash
# 1. Initialize git (if not already done)
git init -b main

# 2. Add files and make your first commit
git add .
git commit -m "feat: complete deploy-ready ClinicSync website for GitHub and cloud hosts"

# 3. Link to your GitHub repository
git remote add origin https://github.com/<YOUR-USERNAME>/<YOUR-REPO-NAME>.git

# 4. Push code
git push -u origin main
```

---

## 📄 License
This project is open-source software licensed under the [MIT License](LICENSE).
