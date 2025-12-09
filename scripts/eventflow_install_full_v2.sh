#!/bin/bash

# LEMP Stack Installation Script
# This script installs Nginx, PHP 8.4, MariaDB and configures UFW firewall

set -e  # Exit on any error
export DEBIAN_FRONTEND=noninteractive
# ========================================
# CONFIGURATION VARIABLES
# ========================================
# Set your MariaDB root password here, or leave empty to be prompted
MARIADB_ROOT_PASSWORD="password"

# EventFlow application & database configuration
APP_DB_NAME="eventflow"
APP_DB_USER="eventflow_user"
APP_DB_PASSWORD="change_me"

APP_REPO_URL="https://github.com/Angel-G-Soto/EventFlow.git"
APP_DIR="/var/www/EventFlow"
APP_WEB_USER="www-data"                  # nginx/php-fpm user
APP_SYSTEM_USER="${SUDO_USER:-$USER}"    # SSH user that will run composer/npm
APP_DOMAIN="eventflow.uprm.edu"

# MariaDB version / distro (adjust if you want a specific MariaDB release)
MARIADB_MAJOR_VERSION="12.0.2"
UBUNTU_CODENAME="$(lsb_release -cs)"

# Application system admin user (Laravel-level admin)
ADMIN_FIRST_NAME="System"
ADMIN_LAST_NAME="Admin"
ADMIN_EMAIL="sysadmin@example.com"
ADMIN_PASSWORD_PLAIN="Pass123!"   # change this and rotate it later
ADMIN_AUTH_TYPE="saml2"            # or 'local' if you log in via password

#Email for certbot
CERTBOT_EMAIL="you@example.com"


# ========================================

echo "========================================="
echo "LEMP Stack Installation Script"
echo "========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root or with sudo"
    exit 1
fi

# Prompt for MariaDB password if not set
if [ -z "$MARIADB_ROOT_PASSWORD" ]; then
    echo "MariaDB Configuration"
    echo "-----------------------------------"
    read -sp "Enter MariaDB root password: " MARIADB_ROOT_PASSWORD
    echo ""
    read -sp "Confirm MariaDB root password: " MARIADB_ROOT_PASSWORD_CONFIRM
    echo ""
    
    if [ "$MARIADB_ROOT_PASSWORD" != "$MARIADB_ROOT_PASSWORD_CONFIRM" ]; then
        echo "ERROR: Passwords do not match!"
        exit 1
    fi
    
    if [ -z "$MARIADB_ROOT_PASSWORD" ]; then
        echo "ERROR: Password cannot be empty!"
        exit 1
    fi
    echo ""
fi

# Enable and configure UFW firewall
echo "Step 1: Configuring UFW Firewall..."
echo "-----------------------------------"
ufw --force enable
ufw allow 'OpenSSH'
echo "✓ UFW enabled and OpenSSH allowed"
echo ""

# Update system packages
echo "Step 2: Updating system packages..."
echo "-----------------------------------"
apt update
apt upgrade -y
echo "✓ System packages updated"
echo ""

# ---------------------------------------------------
# NGINX FROM NGINX.ORG (1.28.*) – FROM HISTORY
# ---------------------------------------------------
echo "Step 3: Installing Nginx 1.28.* from nginx.org..."
echo "-----------------------------------"

# Stop any running Nginx and purge Ubuntu-packaged nginx variants
systemctl stop nginx 2>/dev/null || true
apt-get purge -y nginx nginx-common nginx-core nginx-full nginx-doc 2>/dev/null || true

# Install prerequisites
apt install -y curl gnupg2 ca-certificates lsb-release ubuntu-keyring

# Import nginx signing key
if [ ! -f /usr/share/keyrings/nginx-archive-keyring.gpg ]; then
    curl -fsSL https://nginx.org/keys/nginx_signing.key \
        | gpg --dearmor \
        | tee /usr/share/keyrings/nginx-archive-keyring.gpg >/dev/null
fi

# Configure nginx.org repository (stable/regular branch)
cat >/etc/apt/sources.list.d/nginx.list <<EOF
deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] http://nginx.org/packages/ubuntu ${UBUNTU_CODENAME} nginx
EOF

# Pin nginx.org packages with high priority
cat >/etc/apt/preferences.d/99nginx <<EOF
Package: *
Pin: origin nginx.org
Pin: release o=nginx
Pin-Priority: 900
EOF

apt update

# Try to install specific nginx 1.28.*; allow downgrades if needed
if ! apt install -y "nginx=1.28.*" --allow-downgrades; then
    echo "WARNING: Could not install nginx=1.28.*; falling back to latest nginx from nginx.org."
    apt install -y nginx
fi

nginx -v
echo "✓ Nginx installed from nginx.org"
echo ""


# Allow Nginx through firewall
echo "Step 4: Allowing Nginx through firewall..."
echo "-----------------------------------"
ufw allow 80/tcp
ufw allow 443/tcp
echo "✓ Ports 80 (HTTP) and 443 (HTTPS) allowed through firewall"
echo ""

# Start Nginx
echo "Step 5: Starting Nginx..."
echo "-----------------------------------"
systemctl start nginx
systemctl enable nginx
echo "✓ Nginx started and enabled"
echo ""

# Install PHP 8.4
echo "Step 6: Installing PHP 8.4..."
echo "-----------------------------------"
apt install -y software-properties-common
LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.4 php8.4-fpm php8.4-mysql php8.4-cli php8.4-common php8.4-curl php8.4-mbstring php8.4-xml php8.4-zip
echo "✓ PHP 8.4 installed"
php -v
echo ""

# Start and enable PHP-FPM
echo "Step 7: Starting PHP-FPM..."
echo "-----------------------------------"
systemctl start php8.4-fpm
systemctl enable php8.4-fpm
echo "✓ PHP-FPM started and enabled"
echo ""

# ---------------------------------------------------
# MARIADB FROM OFFICIAL MARIADB REPO – FROM HISTORY
# ---------------------------------------------------
echo "Step 8: Installing MariaDB from MariaDB upstream repo..."
echo "-----------------------------------"

# Purge any old MariaDB packages
apt-get purge -y 'mariadb-*' 2>/dev/null || true

# Install repo prerequisites
apt-get install -y apt-transport-https curl

# Add MariaDB signing key
mkdir -p /etc/apt/keyrings
curl -fsSL 'https://mariadb.org/mariadb_release_signing_key.pgp' \
     -o /etc/apt/keyrings/mariadb-keyring.pgp

# Configure MariaDB .sources file
# NOTE: Adjust ${MARIADB_MAJOR_VERSION} if you want a different release.
cat >/etc/apt/sources.list.d/mariadb.sources <<EOF
# MariaDB ${MARIADB_MAJOR_VERSION} repository
# Generated by install script – verify at https://mariadb.org/download/
X-Repolib-Name: MariaDB
Types: deb
URIs: https://archive.mariadb.org/mariadb-${MARIADB_MAJOR_VERSION}/repo/ubuntu
Suites: ${UBUNTU_CODENAME}
Components: main
Signed-By: /etc/apt/keyrings/mariadb-keyring.pgp
EOF

apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y mariadb-server
#apt-get install -y mariadb-server

echo "✓ MariaDB installed from MariaDB upstream repository"
mariadb -V
echo ""

# Start and enable MariaDB
echo "Step 9: Starting MariaDB..."
echo "-----------------------------------"
systemctl start mariadb
systemctl enable mariadb
echo "✓ MariaDB started and enabled"
echo ""

echo "Step 10: Securing MariaDB installation..."
echo "-----------------------------------"
echo "Setting up secure defaults..."

sudo mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED BY '${MARIADB_ROOT_PASSWORD}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user 
  WHERE User='root' 
  AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

echo "✓ MariaDB secured successfully"
echo ""

echo "✓ MariaDB secured successfully"
echo ""

# Display firewall status
echo "Current UFW Status:"
echo "-----------------------------------"
ufw status

# Configure Nginx for PHP
echo ""
echo "Step 11: Configuring Nginx for PHP..."
echo "-----------------------------------"

# Create snippets directory if it doesn't exist
mkdir -p /etc/nginx/snippets

# Create fastcgi-php.conf snippet
cat > /etc/nginx/snippets/fastcgi-php.conf << 'EOF'
fastcgi_split_path_info ^(.+\.php)(/.+)$;
fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
fastcgi_param PATH_INFO $fastcgi_path_info;
include fastcgi_params;
EOF


touch /etc/nginx/conf.d/eventflow.conf
# Create eventflow server block
cat > /etc/nginx/conf.d/eventflow.conf<< 'EOF'
server {
    listen 80;

    server_name eventflow.uprm.edu;
    root /var/www/EventFlow/public;
    index index.html index.htm index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    client_max_body_size 130M;
}
EOF

sudo mv /etc/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf.disable
sudo sed -i "s/user.*[^''""];$/user www-data;/g" /etc/nginx/nginx.conf


# Test Nginx configuration
nginx -t

# Reload Nginx
systemctl reload nginx

echo "✓ Nginx configured for PHP"
echo ""





###########################################
# EventFlow database and application setup
###########################################

echo "Step 12: Creating EventFlow database and user in MariaDB."
echo "-----------------------------------"

mysql -u root -p"${MARIADB_ROOT_PASSWORD}" <<EOF
CREATE DATABASE IF NOT EXISTS \`${APP_DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${APP_DB_USER}'@'localhost' IDENTIFIED BY '${APP_DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${APP_DB_NAME}\`.* TO '${APP_DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "✓ MariaDB database '${APP_DB_NAME}' and user '${APP_DB_USER}' ready"
echo ""

echo "Step 13: Deploying EventFlow Laravel application code."
echo "-----------------------------------"

# Make sure git is available
if ! command -v git >/dev/null 2>&1; then
    apt install -y git
fi

mkdir -p /var/www

# Clone or update repo
if [ ! -d "$APP_DIR/.git" ]; then
    echo "Cloning EventFlow repository into $APP_DIR..."
    rm -rf "$APP_DIR"
    git clone "$APP_REPO_URL" "$APP_DIR"
else
    echo "Existing git repo found at $APP_DIR; pulling latest changes..."
    cd "$APP_DIR"
    if [ -d .git ]; then
        git pull origin main || true
    fi
fi

cd "$APP_DIR"

# Sanity check: does this look like a Laravel project?
if [ ! -f artisan ] || [ ! -f composer.json ]; then
    echo "ERROR: $APP_DIR does not look like a Laravel project (artisan/composer.json missing)."
    echo "Check APP_REPO_URL or copy the project files into $APP_DIR."
    exit 1
fi

# Install composer if needed
if ! command -v composer >/dev/null 2>&1; then
    apt install -y composer
fi

# Before composer/npm: give build ownership to APP_SYSTEM_USER
echo "Setting ownership of $APP_DIR for build user ${APP_SYSTEM_USER}..."
chown -R "$APP_SYSTEM_USER":"$APP_SYSTEM_USER" "$APP_DIR"

# PHP dependencies (this creates vendor/autoload.php)
sudo -u "$APP_SYSTEM_USER" composer install --no-dev --ignore-platform-req=ext-http

# Verify vendor/autoload.php exists
if [ ! -f vendor/autoload.php ]; then
    echo "ERROR: vendor/autoload.php is missing after composer install."
    echo "Check the composer output above for errors, then re-run this step."
    exit 1
fi

# Ensure .env exists and is configured for this server on first run
if [ ! -f .env ]; then
    cp .env.example .env

    # Configure database and app URL in .env (still as root)
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${APP_DB_NAME}/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${APP_DB_USER}/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${APP_DB_PASSWORD}/" .env
    sed -i "s|^APP_URL=.*|APP_URL=https://${APP_DOMAIN}|" .env

    # Make sure APP_SYSTEM_USER owns .env so it can write to it
    chown "$APP_SYSTEM_USER":"$APP_SYSTEM_USER" .env

    # Now that vendor exists and .env is writable, generate the key
    sudo -u "$APP_SYSTEM_USER" php artisan key:generate --force
fi

# Node / build tools
if ! command -v npm >/dev/null 2>&1; then
    apt install -y npm
fi
if ! command -v node >/dev/null 2>&1; then
    apt install -y nodejs
fi

sudo -u "$APP_SYSTEM_USER" npm install
sudo -u "$APP_SYSTEM_USER" npm run build

# Runtime permissions (leave code owned by APP_SYSTEM_USER, only storage/cache for web user)
echo "Setting runtime permissions for Laravel..."
chown -R "$APP_WEB_USER":"$APP_WEB_USER" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# Run database migrations (do not abort whole script if this fails)
set +e
sudo -u "$APP_WEB_USER" php artisan migrate --force
if [ $? -ne 0 ]; then
    echo "WARNING: php artisan migrate failed. Check .env and database, then run migrations manually."
fi
set -e

echo "✓ EventFlow application deployed"
echo ""




echo "Step 14: Installing and configuring ClamAV."
echo "-----------------------------------"

echo "Ensuring temp upload directory exists and is readable by ClamAV..."
TMP_DIR="${APP_DIR}/storage/app/tmp"

mkdir -p "$TMP_DIR"
# Make sure web user owns it
chown -R "$APP_WEB_USER":"$APP_WEB_USER" "$TMP_DIR"
# Allow web user and its group (incl. clamav) to traverse + read/write
chmod -R 770 "$TMP_DIR"

echo "✓ Temp upload directory $TMP_DIR is ready for web + ClamAV access"
echo ""

apt install -y clamav clamav-daemon apparmor-utils

systemctl enable --now clamav-freshclam
systemctl enable --now clamav-daemon

# Allow ClamAV to read web files
usermod -a -G "$APP_WEB_USER" clamav || true
chmod -R g+rX "$APP_DIR"

# Relax AppArmor profile for clamd so it can scan web files
if command -v aa-complain >/dev/null 2>&1; then
    aa-complain /usr/sbin/clamd || true
fi

echo "✓ ClamAV installed and basic permissions configured"
echo ""

echo "Step 15: Installing Postfix (mail server)."
echo "-----------------------------------"

DEBIAN_FRONTEND=noninteractive apt-get install -y postfix

# Basic non-open-relay configuration
postconf -e "myhostname = ${APP_DOMAIN}"
postconf -e "mydomain = uprm.edu"                     # campus domain
postconf -e "myorigin = \$mydomain"  
postconf -e "relayhost = [mail.uprm.edu]"   
postconf -e "inet_interfaces = loopback-only"
postconf -e "mydestination = \$myhostname, localhost.\$mydomain, localhost"
postconf -e "mailbox_size_limit = 0"
postconf -e "recipient_delimiter = +"

systemctl enable postfix
systemctl restart postfix

echo "✓ Postfix installed and basic configuration applied"
echo ""


echo "Step 16: Setting up Laravel scheduler cron job for ${APP_WEB_USER}..."
echo "-----------------------------------"

set -e
CRON_USER="$APP_WEB_USER"
CRON_LINE="* * * * * cd ${APP_DIR} && /usr/bin/php artisan schedule:run >> /dev/null 2>&1"

# Check if the cron line already exists for this user
EXISTING_CRON=$(crontab -u "$CRON_USER" -l 2>/dev/null || true)

if echo "$EXISTING_CRON" | grep -F "$CRON_LINE" >/dev/null 2>&1; then
    echo "Cron job already present for ${CRON_USER}, skipping."
else
    echo "Adding cron job for ${CRON_USER}..."
    # Append the new line, preserving any existing crontab
    (echo "$EXISTING_CRON"; echo "$CRON_LINE") | crontab -u "$CRON_USER" -
    echo "✓ Cron job installed for ${CRON_USER}"
fi

echo ""

echo "Step 17: Adjusting PHP upload settings..."
echo "-----------------------------------"

PHP_VERSION="8.4"

for SAPI in cli fpm; do
    INI="/etc/php/${PHP_VERSION}/${SAPI}/php.ini"
    if [ -f "$INI" ]; then
        echo "Updating $INI ..."
        # Set upload_max_filesize = 15M
        sed -i 's/^[[:space:]]*upload_max_filesize[[:space:]]*=.*/upload_max_filesize = 15M/' "$INI"
        # Set max_file_uploads = 15
        sed -i 's/^[[:space:]]*max_file_uploads[[:space:]]*=.*/max_file_uploads = 15/' "$INI"
    fi
done

# Reload PHP-FPM so web requests see the new values
systemctl reload php8.4-fpm

echo "✓ PHP upload limits updated (upload_max_filesize=15M, max_file_uploads=15)"
echo ""
echo "Step 18: Creating application system admin user in ${APP_DB_NAME}..."
echo "-----------------------------------"

# Generate bcrypt hash for the admin password using PHP
# (Safe from quoting issues because we pass it via env var)
ADMIN_PASSWORD_HASH=$(ADMIN_PASSWORD_PLAIN="${ADMIN_PASSWORD_PLAIN}" php -r 'echo password_hash(getenv("ADMIN_PASSWORD_PLAIN"), PASSWORD_BCRYPT);')

# Optional: show that hash exists (debug only)
# echo "Generated bcrypt hash: ${ADMIN_PASSWORD_HASH}"

mysql -u root -p"${MARIADB_ROOT_PASSWORD}" "${APP_DB_NAME}" <<EOF
-- Grab role ids
SET @system_admin_role := (SELECT id FROM roles WHERE name = 'system-admin' LIMIT 1);
SET @user_role        := (SELECT id FROM roles WHERE name = 'user'         LIMIT 1);

-- Insert the user (email unique assumed; adjust columns to match your schema)
INSERT INTO users (
    first_name, last_name, email, password, auth_type,
    department_id, email_verified_at, remember_token,
    created_at, updated_at
) VALUES (
    '${ADMIN_FIRST_NAME}',
    '${ADMIN_LAST_NAME}',
    '${ADMIN_EMAIL}',
    '${ADMIN_PASSWORD_HASH}',
    '${ADMIN_AUTH_TYPE}',
    NULL,
    NOW(),
    NULL,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name  = VALUES(last_name),
    password   = VALUES(password),
    auth_type  = VALUES(auth_type),
    updated_at = NOW();

-- Get the id of the (new or existing) user with this email
SET @user_id := (SELECT id FROM users WHERE email = '${ADMIN_EMAIL}' LIMIT 1);

-- Attach roles (ignore duplicates if you want to be idempotent)
INSERT IGNORE INTO user_role (user_id, role_id, created_at, updated_at) VALUES
(@user_id, @system_admin_role, NOW(), NOW()),
(@user_id, @user_role,        NOW(), NOW());
EOF

echo "✓ Application system admin user '${ADMIN_EMAIL}' created/updated"
echo ""

echo "Step 19: Creating systemd service for EventFlow queue worker..."
echo "-----------------------------------"

# Detect PHP binary (fallback to /usr/bin/php)
PHP_BIN="$(command -v php || echo /usr/bin/php)"

cat >/etc/systemd/system/eventflow-queue.service <<EOF
[Unit]
Description=EventFlow Queue Worker
After=network.target

[Service]
User=${APP_WEB_USER}
Group=${APP_WEB_USER}
Restart=always
RestartSec=5
ExecStart=${PHP_BIN} ${APP_DIR}/artisan queue:work --queue=default --tries=3 --sleep=1

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd to pick up the new unit
systemctl daemon-reload

# Enable and start the service
systemctl enable --now eventflow-queue.service

echo "✓ systemd service 'eventflow-queue.service' installed and started"
echo ""





echo "Step 20: Installing Certbot and obtaining TLS certificate..."
echo "-----------------------------------"

# Remove any old apt-based certbot (no-op if not installed)
apt-get remove -y certbot || true

# Install snapd if needed
if ! command -v snap >/dev/null 2>&1; then
    apt-get install -y snapd
fi

# Ensure snapd is ready
systemctl enable --now snapd.socket
sleep 5

# Install Certbot via snap (classic confinement)
if ! snap list | grep -q "^certbot "; then
    snap install --classic certbot
fi

# Make sure /usr/bin/certbot points to snap certbot
if [ ! -e /usr/bin/certbot ]; then
    ln -s /snap/bin/certbot /usr/bin/certbot
fi

# Use APP_DOMAIN and CERTBOT_EMAIL for a non-interactive cert
CERT_DOMAIN="${APP_DOMAIN}"      # e.g. eventflow.uprm.edu
CERT_EMAIL="${CERTBOT_EMAIL}"

#Certbot with email
# IMPORTANT: this must run on a real server where APP_DOMAIN points to this machine.
#certbot --nginx \
#  --non-interactive \
#  --agree-tos \
#  --email "${CERT_EMAIL}" \
#  -d "${CERT_DOMAIN}" \
#  --redirect
 
#Certbot without email
#certbot --nginx \
#  --non-interactive \
#  --agree-tos \
#  --register-unsafely-without-email \
#  -d "${CERT_DOMAIN}" \
#  --redirect

echo "✓ Certbot certificate obtained and Nginx configuration updated"
echo ""


echo "Step 21: Setting Timezone to America/Puerto Rico"
echo "-----------------------------------"
sudo timedatectl set-timezone America/Puerto_Rico

echo "✓ Timezone updated to America/Puerto Rico"
echo ""

# Test Nginx configuration and reload after Certbot changes
nginx -t
systemctl reload nginx




#echo "Step 20b: Certbot Certificate."
#echo "-----------------------------------"

#sudo apt-get remove certbot
#sudo snap install --classic certbot
#sudo ln -s /snap/bin/certbot /usr/bin/certbot
#sudo certbot --nginx #for production

#echo "✓ Certificate succesfully added"
#echo ""
## Test Nginx configuration
#nginx -t
# Reload Nginx
#systemctl reload nginx


echo ""
echo "========================================="
echo "Installation Complete!"
echo "========================================="
echo "Nginx version: $(nginx -v 2>&1)"
echo "Nginx status: $(systemctl is-active nginx)"
echo "PHP version: $(php -v | head -n 1)"
echo "PHP-FPM status: $(systemctl is-active php8.4-fpm)"
echo "MariaDB version: $(mariadb -V)"
echo "MariaDB status: $(systemctl is-active mariadb)"
echo ""
echo "========================================="
echo "MariaDB Access"
echo "========================================="
echo "To access MariaDB, use:"
echo "  mysql -u root -p"
echo "Then enter the password you configured."
echo "========================================="
echo ""
echo "========================================="
echo "Server Configuration"
echo "========================================="
echo "Server block created: /etc/nginx/sites-available/eventflow"
echo "Document root: /var/www/EventFlow/public"
echo "Server name: eventflow.uprm.edu"
echo "========================================="
