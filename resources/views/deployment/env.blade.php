APP_NAME="{{ $name }}"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://{{ $domain }}

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE={{ $db_name }}
DB_USERNAME={{ $db_user }}
DB_PASSWORD={{ $db_password }}

# Facebook Credentials
FACEBOOK_APP_ID={{ $facebook_app_id }}
FACEBOOK_APP_SECRET={{ $facebook_app_secret }}
FACEBOOK_USER_TOKEN={{ $facebook_user_token }}

# Google Search Console Credentials
GOOGLE_CLIENT_ID={{ $google_client_id }}
GOOGLE_CLIENT_SECRET={{ $google_client_secret }}
GOOGLE_REFRESH_TOKEN={{ $google_refresh_token }}

# Monitoring Configuration
MONITOR_FACADE_URL="{{ $facade_url }}"
MONITOR_TOKEN="{{ $monitoring_token }}"

# Multi-tenant Identifiers
PROJECT_NAME="{{ $name }}"
DEPLOYMENT_NAME="{{ $subdomain }}"
