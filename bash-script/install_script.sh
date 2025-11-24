#!/bin/bash

# LEMP Stack Installation Script
# This script installs Nginx, PHP 8.4, MariaDB 12 and configures UFW firewall

set -e  # Exit on any error

# ========================================
# CONFIGURATION VARIABLES
# ========================================
# Set your MariaDB root password here, or leave empty to be prompted
MARIADB_ROOT_PASSWORD=""

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

# Install Nginx from official repository
echo "Step 3: Installing Nginx..."
echo "-----------------------------------"
apt install -y curl gnupg2 ca-certificates lsb-release ubuntu-keyring

# Add Nginx signing key
curl https://nginx.org/keys/nginx_signing.key | gpg --dearmor \
    | tee /usr/share/keyrings/nginx-archive-keyring.gpg >/dev/null

# Verify the key
gpg --dry-run --quiet --no-keyring --import --import-options import-show /usr/share/keyrings/nginx-archive-keyring.gpg

# Add Nginx stable repository
echo "deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] \
http://nginx.org/packages/ubuntu $(lsb_release -cs) nginx" \
    | tee /etc/apt/sources.list.d/nginx.list

# Set repository priority
echo -e "Package: *\nPin: origin nginx.org\nPin: release o=nginx\nPin-Priority: 900\n" \
    | tee /etc/apt/preferences.d/99nginx

# Update and install Nginx
apt update
apt install -y nginx

sudo sed -i "s/user.*;$/user www-data/g" nginx.conf

echo "✓ Nginx installed"
nginx -v
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

# Install MariaDB 12
echo "Step 8: Installing MariaDB 12..."
echo "-----------------------------------"
apt-get install -y apt-transport-https curl
mkdir -p /etc/apt/keyrings
curl -o /etc/apt/keyrings/mariadb-keyring.pgp 'https://mariadb.org/mariadb_release_signing_key.pgp'

# Add MariaDB repository configuration
cat > /etc/apt/sources.list.d/mariadb.sources << EOF
# MariaDB 12.0 repository list
# https://mariadb.org/download/
X-Repolib-Name: MariaDB
Types: deb
# deb.mariadb.org is a dynamic mirror if your preferred mirror goes offline. See https://mariadb.org/mirrorbits/ for details.
# URIs: https://deb.mariadb.org/12.0/ubuntu
URIs: https://ftp.osuosl.org/pub/mariadb/repo/12.0/ubuntu
Suites: $(lsb_release -cs)
Components: main main/debug
Signed-By: /etc/apt/keyrings/mariadb-keyring.pgp
EOF

apt-get update
apt-get install -y mariadb-server
echo "✓ MariaDB installed"
mariadb -V
echo ""

# Start and enable MariaDB
echo "Step 9: Starting MariaDB..."
echo "-----------------------------------"
systemctl start mariadb
systemctl enable mariadb
echo "✓ MariaDB started and enabled"
echo ""

# Secure MariaDB installation
echo "Step 10: Securing MariaDB installation..."
echo "-----------------------------------"
echo "Setting up secure defaults..."

# Run SQL commands directly to secure MariaDB
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${MARIADB_ROOT_PASSWORD}';"
mysql -e "DELETE FROM mysql.user WHERE User='';"
mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -e "DROP DATABASE IF EXISTS test;"
mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -e "FLUSH PRIVILEGES;"

echo "✓ MariaDB secured successfully"
echo ""

# Display firewall status
echo "Current UFW Status:"
echo "-----------------------------------"
ufw status

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
echo "Next steps:"
echo "- Configure your server blocks in /etc/nginx/conf.d/"
echo "- Create your web root directory"
echo "- Configure Nginx to work with PHP-FPM"
echo "========================================="

