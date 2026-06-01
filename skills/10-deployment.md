# Skill 10: Deployment

## Overview
Production deployment checklist for Laravel backend and Nuxt frontend, including SSL, CDN, queue workers, and monitoring.

---

## Step 1: Production Server Requirements

### Server Specs
- **CPU:** 2+ cores
- **RAM:** 4GB+ recommended
- **Storage:** 50GB+ (depends on video/image storage)
- **OS:** Ubuntu 22.04 LTS or similar
- **PHP:** 8.3+
- **Node.js:** 20+ LTS
- **MySQL:** 8.0+
- **Redis:** 7.0+

### Software Stack
- Nginx (reverse proxy)
- PHP-FPM
- Supervisor (queue workers)
- Certbot (SSL)
- Composer (PHP dependencies)
- PM2 or systemd (Node process management)

---

## Step 2: Laravel Backend Deployment

### Install Dependencies

```bash
cd /var/www/atelier/backend

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### Environment Setup

```bash
# Copy .env
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed roles
php artisan db:seed --class=RolesSeeder

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Nginx Configuration

```nginx
# /etc/nginx/sites-available/atelier-api

server {
    listen 80;
    server_name api.atelier.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.atelier.com;

    ssl_certificate /etc/letsencrypt/live/api.atelier.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.atelier.com/privkey.pem;

    root /var/www/atelier/backend/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase upload limit for images/videos
    client_max_body_size 100M;
}
```

### Queue Workers (Supervisor)

```ini
# /etc/supervisor/conf.d/atelier-worker.conf

[program:atelier-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/atelier/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/atelier/backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start atelier-worker:*
```

---

## Step 3: Nuxt Frontend Deployment

### Build for Production

```bash
cd /var/www/atelier/frontend

# Install dependencies
npm ci

# Build
npm run build

# Start with PM2
pm2 start .output/server/index.mjs --name atelier-frontend
pm2 save
pm2 startup
```

### Nginx Configuration (Frontend)

```nginx
# /etc/nginx/sites-available/atelier

server {
    listen 80;
    server_name atelier.com www.atelier.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name atelier.com www.atelier.com;

    ssl_certificate /etc/letsencrypt/live/atelier.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/atelier.com/privkey.pem;

    root /var/www/atelier/frontend;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Static assets caching
    location /_nuxt/ {
        proxy_pass http://127.0.0.1:3000;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## Step 4: S3 / Cloudflare R2 Setup

### AWS S3 Bucket Policy

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::atelier-media/*"
    }
  ]
}
```

### Laravel S3 Configuration

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=atelier-media
AWS_URL=https://atelier-media.s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Cloudflare R2 (Alternative)

```env
AWS_ACCESS_KEY_ID=your-r2-access-key
AWS_SECRET_ACCESS_KEY=your-r2-secret-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=atelier-media
AWS_URL=https://your-account.r2.cloudflarestorage.com
AWS_ENDPOINT=https://your-account.r2.cloudflarestorage.com
```

---

## Step 5: SSL with Certbot

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificates
sudo certbot --nginx -d atelier.com -d www.atelier.com
sudo certbot --nginx -d api.atelier.com

# Auto-renewal (usually set up automatically)
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## Step 6: Domain Configuration

### DNS Records

```
Type    Name            Value                   TTL
A       atelier.com     123.456.789.0           300
A       api.atelier.com 123.456.789.0           300
CNAME   www.atelier.com atelier.com             300
```

---

## Step 7: Monitoring

### Laravel Telescope (Development)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

### Sentry (Production Error Tracking)

```bash
composer require sentry/sentry-laravel

# .env
SENTRY_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=1.0
```

### Uptime Monitoring

Use external services:
- **UptimeRobot** (free tier available)
- **Better Stack** (formerly BetterUptime)
- **Pingdom**

---

## Step 8: Backup Strategy

### Database Backup Script

```bash
#!/bin/bash
# /var/www/atelier/scripts/backup.sh

BACKUP_DIR="/var/www/atelier/backups"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
DB_NAME="atelier"

# Backup database
mysqldump -u root -p"$DB_PASSWORD" $DB_NAME | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup uploaded files
tar -czf "$BACKUP_DIR/media_$DATE.tar.gz" /var/www/atelier/backend/storage/app/public

# Keep last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

# Upload to S3
aws s3 sync $BACKUP_DIR s3://atelier-backups/ --delete
```

### Cron Job

```bash
# Daily backup at 2 AM
0 2 * * * /var/www/atelier/scripts/backup.sh
```

---

## Step 9: Environment Variables Checklist

### Backend (.env)

```env
APP_NAME=Atelier
APP_ENV=production
APP_DEBUG=false
APP_URL=https://atelier.com

FRONTEND_URL=https://atelier.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atelier
DB_USERNAME=atelier_user
DB_PASSWORD=secure-password

SANCTUM_STATEFUL_DOMAINS=atelier.com,www.atelier.com
SESSION_DOMAIN=.atelier.com

# S3
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_BUCKET=atelier-media

# Stripe
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_CONNECT_CLIENT_ID=ca_xxx

# Mail
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@atelier.com
MAIL_FROM_NAME=Atelier

# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

# Sentry
SENTRY_DSN=https://xxx@sentry.io/xxx
```

### Frontend (.env)

```env
NUXT_PUBLIC_API_URL=https://api.atelier.com
NUXT_PUBLIC_APP_URL=https://atelier.com
NUXT_STRIPE_KEY=pk_live_xxx
```

---

## Step 10: Post-Deployment Checklist

### Security
- [ ] APP_DEBUG=false
- [ ] Strong database password
- [ ] SSL certificates installed
- [ ] CORS configured for production domains
- [ ] Stripe webhook secret set
- [ ] Environment variables secured

### Performance
- [ ] Config cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Composer optimized (`--optimize-autoloader`)
- [ ] Nuxt built for production
- [ ] Static assets cached

### Functionality
- [ ] User registration works
- [ ] Login/logout works
- [ ] Grisaille tool works
- [ ] Image upload works
- [ ] Video upload works
- [ ] Payment processing works
- [ ] Email notifications work
- [ ] Queue workers running

### Monitoring
- [ ] Error tracking configured (Sentry)
- [ ] Uptime monitoring active
- [ ] Database backups scheduled
- [ ] Log rotation configured

---

## Deployment Platforms (Alternatives)

### Laravel Forge
- Managed Laravel hosting
- Automatic SSL
- Queue worker management
- Database backups

### Vercel (Frontend)
- Automatic deployments from Git
- Edge functions
- Built-in analytics

### Docker

```dockerfile
# Dockerfile (backend)
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader

CMD ["php-fpm"]
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: ./backend
    volumes:
      - ./:/var/www
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./nginx.conf:/etc/nginx/conf.d/default.conf

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: atelier
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - mysql-data:/var/lib/mysql

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"

volumes:
  mysql-data:
```

---

## Verification Checklist

- [ ] Site accessible via HTTPS
- [ ] All API endpoints working
- [ ] File uploads working (S3/R2)
- [ ] Payments processing in live mode
- [ ] Queue workers processing jobs
- [ ] Emails being sent
- [ ] Backups running
- [ ] Error tracking active
- [ ] SSL auto-renewal configured

---

## Congratulations!

You've built **Atelier** — a professional SaaS platform for painters with:

- Authentication & role-based access
- Painter public profiles & galleries
- Painting management with multi-image upload
- Video shop with streaming
- Grisaille value analysis tool
- Stripe Connect marketplace payments
- Admin dashboard
- Full deployment setup

**Next:** Launch and iterate based on user feedback!
